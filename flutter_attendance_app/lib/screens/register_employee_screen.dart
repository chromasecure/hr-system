import 'dart:convert';
import 'dart:io';

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';

import '../services/api_service.dart';
import '../services/face_recognition_service.dart';
import '../services/face_storage_service.dart';
import '../services/sync_service.dart';

class RegisterEmployeeScreen extends StatefulWidget {
  final List<CameraDescription> cameras;
  final SyncService syncService;
  final ApiService api;
  const RegisterEmployeeScreen({
    super.key,
    required this.cameras,
    required this.syncService,
    required this.api,
  });

  @override
  State<RegisterEmployeeScreen> createState() => _RegisterEmployeeScreenState();
}

class _RegisterEmployeeScreenState extends State<RegisterEmployeeScreen> {
  final _codeCtrl = TextEditingController();
  final _faceService = FaceRecognitionService();
  late final FaceDetector _detector;

  CameraController? _controller;
  int _cameraIndex = 0;
  XFile? _capture;
  List<double>? _embedding;
  String _status = 'Capture a clear single face to continue.';
  bool _busy = false;
  String? _resolvedName;

  @override
  void initState() {
    super.initState();
    _detector = FaceDetector(
        options: FaceDetectorOptions(
      enableClassification: true,
      enableLandmarks: true,
    ));
    _initCamera();
  }

  void _resolveName(String code) {
    String? foundName;
    for (final e in widget.syncService.cachedEmployees()) {
      if (e.employeeCode == code.trim()) {
        foundName = e.name;
        break;
      }
    }
    setState(() => _resolvedName = foundName);
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
    if (mounted) setState(() {});
  }

  Future<void> _switchCamera() async {
    if (widget.cameras.length < 2) return;
    _cameraIndex = (_cameraIndex + 1) % widget.cameras.length;
    await _initCamera();
  }

  Future<void> _captureFace() async {
    if (_controller == null) return;
    setState(() {
      _busy = true;
      _status = 'Capturing...';
    });
    try {
      final shot = await _controller!.takePicture();
      final faces =
          await _detector.processImage(InputImage.fromFilePath(shot.path));
      if (faces.isEmpty) {
        setState(() => _status = 'No face found. Try again.');
        return;
      }
      if (faces.length > 1) {
        setState(() => _status = 'Multiple faces detected. Only one person allowed.');
        return;
      }
      final face = faces.first;
      final emb =
          await _faceService.embeddingFromFile(shot.path, crop: face.boundingBox);
      if (emb.isEmpty) {
        setState(() => _status = 'Could not read face. Try again.');
        return;
      }

      final duplicate = _faceService.matchEmbedding(
        emb,
        widget.syncService.cachedEmployees(),
        threshold: 0.90,
      );
      if (duplicate != null) {
        setState(() => _status =
            'Face matches existing employee (${duplicate.employeeCode}). Not allowed.');
        return;
      }

      setState(() {
        _capture = shot;
        _embedding = emb;
        _status = 'Face captured. Submit to register.';
      });
    } catch (e) {
      setState(() => _status = 'Capture failed: $e');
    } finally {
      setState(() => _busy = false);
    }
  }

  Future<void> _submit() async {
    if (_embedding == null || _capture == null) {
      setState(() => _status = 'Capture a face before submitting.');
      return;
    }
    if (_codeCtrl.text.trim().isEmpty) {
      setState(() => _status = 'Employee code is required.');
      return;
    }
    setState(() {
      _busy = true;
      _status = 'Attaching face to employee...';
    });
    try {
      final bytes = await _capture!.readAsBytes();
      final faceTemplate = _faceService.encodeEmbedding(_embedding!);
      final emp = await widget.syncService.registerEmployee(
        employeeCode: _codeCtrl.text.trim(),
        faceTemplate: faceTemplate,
        faceImageBase64: base64Encode(bytes),
      );
      final savedPath = await FaceStorageService.saveFaceBytes(
        bytes,
        employeeId: emp.id,
      );
      emp.localImagePath = savedPath;
      await widget.syncService.upsertEmployee(emp);

      if (!mounted) return;
      setState(() =>
          _status = 'Face saved. Awaiting admin approval if required.');
      Navigator.of(context).pop(emp);
    } catch (e) {
      setState(() => _status = 'Registration failed: $e');
    } finally {
      setState(() => _busy = false);
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    _detector.close();
    _codeCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Attach Face to Employee'),
        actions: [
          IconButton(
            icon: const Icon(Icons.cameraswitch),
            onPressed: widget.cameras.length < 2 ? null : _switchCamera,
          )
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TextField(
              controller: _codeCtrl,
              decoration: const InputDecoration(labelText: 'Employee code'),
              onChanged: _resolveName,
            ),
            if (_resolvedName != null)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text('Employee: $_resolvedName'),
              ),
            const SizedBox(height: 12),
            AspectRatio(
              aspectRatio: 3 / 4,
              child: Container(
                decoration: BoxDecoration(
                  color: Colors.black,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: _controller != null && _controller!.value.isInitialized
                    ? ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: CameraPreview(_controller!),
                      )
                    : const Center(
                        child: CircularProgressIndicator(),
                      ),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                ElevatedButton.icon(
                  onPressed: _busy ? null : _captureFace,
                  icon: const Icon(Icons.camera_alt),
                  label: Text(_busy ? 'Working...' : 'Capture face'),
                ),
                const SizedBox(width: 12),
                ElevatedButton.icon(
                  onPressed: _busy ? null : _submit,
                  icon: const Icon(Icons.cloud_upload),
                  label: const Text('Submit'),
                ),
              ],
            ),
            const SizedBox(height: 12),
            if (_capture != null)
              SizedBox(
                height: 160,
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: Image.file(
                    File(_capture!.path),
                    fit: BoxFit.cover,
                  ),
                ),
              ),
            const SizedBox(height: 8),
            Text(
              _status,
              style: TextStyle(
                color: _status.toLowerCase().contains('failed') ||
                        _status.toLowerCase().contains('not allowed')
                    ? Colors.red
                    : Colors.black87,
              ),
            )
          ],
        ),
      ),
    );
  }
}
