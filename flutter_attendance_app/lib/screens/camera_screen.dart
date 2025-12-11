import 'dart:async';
import 'dart:convert';
import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';
import '../services/face_recognition_service.dart';
import '../services/face_storage_service.dart';
import '../services/sync_service.dart';
import '../models/employee.dart';
import '../services/api_service.dart';

class CameraScreen extends StatefulWidget {
  final List<CameraDescription> cameras;
  final SyncService syncService;
  final ApiService api;
  const CameraScreen(
      {super.key,
      required this.cameras,
      required this.syncService,
      required this.api});

  @override
  State<CameraScreen> createState() => _CameraScreenState();
}

class _CameraScreenState extends State<CameraScreen> {
  CameraController? _controller;
  bool _isDetecting = false;
  bool _showSuccess = false;
  Timer? _refreshTimer;
  final _faceService = FaceRecognitionService();
  late final FaceDetector _faceDetector;
  bool _isCapturing = false;
  int _cameraIndex = 0;
  DateTime _lastCapture = DateTime.fromMillisecondsSinceEpoch(0);

  @override
  void initState() {
    super.initState();
    _faceDetector = FaceDetector(
      options: FaceDetectorOptions(
        enableContours: true,
        enableClassification: true,
      ),
    );
    _initCamera();
    _startAutoRefresh();
  }

  Future<void> _initCamera() async {
    if (widget.cameras.isEmpty) return;
    _controller?.dispose();
    _controller = CameraController(
      widget.cameras[_cameraIndex],
      ResolutionPreset.medium,
      enableAudio: false,
    );
    await _controller!.initialize();
    await _controller!.startImageStream(_processCameraImage);
    setState(() {});
  }

  void _startAutoRefresh() {
    _refreshTimer?.cancel();
    _refreshTimer = Timer.periodic(const Duration(minutes: 10), (_) {
      widget.syncService.refreshEmployees();
    });
  }

  Future<void> _processCameraImage(CameraImage image) async {
    if (_isDetecting) return;
    final now = DateTime.now();
    if (now.difference(_lastCapture) < const Duration(seconds: 2)) return;
    _isDetecting = true;
    try {
      final faces = await _detect(image);
      if (faces.isNotEmpty && !_isCapturing) {
        _lastCapture = now;
        unawaited(_captureAndIdentify());
      }
    } finally {
      _isDetecting = false;
    }
  }

  Future<List<Face>> _detect(CameraImage image) async {
    final WriteBuffer allBytes = WriteBuffer();
    for (Plane plane in image.planes) {
      allBytes.putUint8List(plane.bytes);
    }
    final Uint8List bytes = allBytes.done().buffer.asUint8List();
    final Size imageSize = Size(image.width.toDouble(), image.height.toDouble());
    final camera = _controller!.description;
    final rotation = InputImageRotationValue.fromRawValue(camera.sensorOrientation) ??
        InputImageRotation.rotation0deg;
    final format =
        InputImageFormatValue.fromRawValue(image.format.raw) ?? InputImageFormat.nv21;

    final metadata = InputImageMetadata(
      size: imageSize,
      rotation: rotation,
      format: format,
      bytesPerRow: image.planes.first.bytesPerRow,
    );

    final inputImage = InputImage.fromBytes(bytes: bytes, metadata: metadata);
    return _faceDetector.processImage(inputImage);
  }

  Future<void> _captureAndIdentify() async {
    if (_controller == null || _isCapturing) return;
    _isCapturing = true;
    try {
      if (_controller!.value.isStreamingImages) {
        await _controller!.stopImageStream();
      }
      final shot = await _controller!.takePicture();
      final bytes = await shot.readAsBytes();
      final base64Image = base64Encode(bytes);
      final faces =
          await _faceDetector.processImage(InputImage.fromFilePath(shot.path));
      if (faces.isEmpty) {
        return;
      }

      final embedding = await _faceService.embeddingFromFile(
        shot.path,
        crop: faces.first.boundingBox,
      );
      if (embedding.isEmpty) return;

      final employees = widget.syncService.cachedEmployees();
      var match =
          _faceService.matchEmbedding(embedding, employees, threshold: 0.88);
      if (match == null) {
        await widget.syncService.refreshEmployees();
      }
      match ??= _faceService.matchEmbedding(
          embedding, widget.syncService.cachedEmployees(),
          threshold: 0.88);

      if (match != null) {
        // Cache face locally for fast future matching
        match.localImagePath ??= await FaceStorageService.saveFaceBytes(
          bytes,
          employeeId: match.id,
        );
        await widget.syncService.upsertEmployee(match);
        await _onMatch(match, base64Image: base64Image);
      }
    } finally {
      if (_controller != null &&
          _controller!.value.isInitialized &&
          !_controller!.value.isStreamingImages) {
        await _controller!.startImageStream(_processCameraImage);
      }
      _isCapturing = false;
    }
  }

  Future<void> _onMatch(Employee emp, {String? base64Image}) async {
    if (emp.approvalStatus != 'approved') {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
            content: Text('Employee pending approval. Cannot mark attendance.')));
      }
      return;
    }
    setState(() => _showSuccess = true);
    Timer(const Duration(seconds: 1), () {
      if (mounted) setState(() => _showSuccess = false);
    });
    await widget.api.markAttendance(
      employeeCode: emp.employeeCode,
      timestamp: DateTime.now().toIso8601String(),
      status: 'in',
      faceImageBase64: base64Image,
    );
  }

  Future<void> _switchCamera() async {
    if (widget.cameras.length < 2) return;
    _cameraIndex = (_cameraIndex + 1) % widget.cameras.length;
    _isCapturing = false;
    _isDetecting = false;
    await _initCamera();
  }

  Future<void> _manualScan() async {
    _lastCapture = DateTime.fromMillisecondsSinceEpoch(0);
    await _captureAndIdentify();
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    _controller?.dispose();
    _faceDetector.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_controller == null || !_controller!.value.isInitialized) {
      return const Center(child: CircularProgressIndicator());
    }
    return Stack(
      children: [
        Positioned.fill(child: CameraPreview(_controller!)),
        if (_showSuccess)
          Container(
            color: const Color(0x664CAF50),
            child: const Center(
              child: Icon(Icons.check_circle, color: Colors.white, size: 120),
            ),
          ),
        SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Auto face attendance',
                      style: Theme.of(context)
                          .textTheme
                          .titleMedium
                          ?.copyWith(color: Colors.white, shadows: const [
                        Shadow(color: Colors.black54, blurRadius: 6)
                      ]),
                    ),
                    IconButton(
                      icon: const Icon(Icons.cameraswitch, color: Colors.white),
                      onPressed: widget.cameras.length < 2 ? null : _switchCamera,
                    ),
                  ],
                ),
                const Spacer(),
                Row(
                  children: [
                    ElevatedButton.icon(
                      onPressed: _isCapturing ? null : _manualScan,
                      icon: const Icon(Icons.verified),
                      label: Text(_isCapturing ? 'Scanning...' : 'Scan now'),
                    ),
                    const SizedBox(width: 12),
                    Text(
                      'Keep a single face in frame.\nSwitch camera if needed.',
                      style: Theme.of(context)
                          .textTheme
                          .bodySmall
                          ?.copyWith(color: Colors.white, shadows: const [
                        Shadow(color: Colors.black54, blurRadius: 6)
                      ]),
                    )
                  ],
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
