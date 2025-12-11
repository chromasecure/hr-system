import 'dart:convert';
import 'dart:io';

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';

import '../services/api_service.dart';

class AddPendingEmployeeScreen extends StatefulWidget {
  final ApiService api;
  final List<CameraDescription> cameras;
  const AddPendingEmployeeScreen(
      {super.key, required this.api, required this.cameras});

  @override
  State<AddPendingEmployeeScreen> createState() =>
      _AddPendingEmployeeScreenState();
}

class _AddPendingEmployeeScreenState extends State<AddPendingEmployeeScreen> {
  final _nameCtrl = TextEditingController();
  final _codeCtrl = TextEditingController();
  final _contactCtrl = TextEditingController();
  final _designationCtrl = TextEditingController();
  final _salaryCtrl = TextEditingController();
  final _commissionCtrl = TextEditingController();
  DateTime _joinDate = DateTime.now();
  XFile? _capture;
  bool _busy = false;
  String _status = 'Fill details and capture face.';

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _joinDate,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (picked != null) setState(() => _joinDate = picked);
  }

  Future<void> _captureFace() async {
    final cameras = widget.cameras;
    if (cameras.isEmpty) return;
    final cam = cameras.first;
    final ctrl = CameraController(cam, ResolutionPreset.medium, enableAudio: false);
    await ctrl.initialize();
    final shot = await ctrl.takePicture();
    await ctrl.dispose();
    setState(() {
      _capture = shot;
      _status = 'Face captured.';
    });
  }

  Future<void> _submit() async {
    if (_capture == null) {
      setState(() => _status = 'Capture a face.');
      return;
    }
    if (_nameCtrl.text.trim().isEmpty || _codeCtrl.text.trim().isEmpty) {
      setState(() => _status = 'Name and employee code are required.');
      return;
    }
    setState(() {
      _busy = true;
      _status = 'Submitting...';
    });
    try {
      final bytes = await _capture!.readAsBytes();
      await widget.api.createPendingEmployee(
        name: _nameCtrl.text.trim(),
        employeeCode: _codeCtrl.text.trim(),
        contact: _contactCtrl.text.trim().isEmpty
            ? null
            : _contactCtrl.text.trim(),
        designationId: _designationCtrl.text.trim().isEmpty
            ? null
            : _designationCtrl.text.trim(),
        basicSalary: _salaryCtrl.text.trim().isEmpty
            ? null
            : _salaryCtrl.text.trim(),
        commission: _commissionCtrl.text.trim().isEmpty
            ? null
            : _commissionCtrl.text.trim(),
        joiningDate: _joinDate.toIso8601String().substring(0, 10),
        imageBase64: base64Encode(bytes),
      );
      if (!mounted) return;
      setState(() => _status = 'Employee sent to admin for approval.');
      Navigator.of(context).pop();
    } catch (e) {
      setState(() => _status = 'Error: $e');
    } finally {
      setState(() => _busy = false);
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _codeCtrl.dispose();
    _contactCtrl.dispose();
    _designationCtrl.dispose();
    _salaryCtrl.dispose();
    _commissionCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Add Employee')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TextField(
              controller: _nameCtrl,
              decoration: const InputDecoration(labelText: 'Name'),
            ),
            TextField(
              controller: _codeCtrl,
              decoration: const InputDecoration(labelText: 'Employee code'),
            ),
            TextField(
              controller: _contactCtrl,
              decoration: const InputDecoration(labelText: 'Contact'),
            ),
            TextField(
              controller: _designationCtrl,
              decoration:
                  const InputDecoration(labelText: 'Designation ID / text'),
            ),
            TextField(
              controller: _salaryCtrl,
              decoration: const InputDecoration(labelText: 'Basic salary'),
            ),
            TextField(
              controller: _commissionCtrl,
              decoration: const InputDecoration(labelText: 'Commission %'),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Text('Joining: ${_joinDate.toString().substring(0, 10)}'),
                TextButton(onPressed: _pickDate, child: const Text('Pick')),
              ],
            ),
            const SizedBox(height: 12),
            if (_capture != null)
              SizedBox(
                height: 160,
                child: Image.file(File(_capture!.path), fit: BoxFit.cover),
              ),
            Row(
              children: [
                ElevatedButton.icon(
                  onPressed: _busy ? null : _captureFace,
                  icon: const Icon(Icons.camera_alt),
                  label: const Text('Capture face'),
                ),
                const SizedBox(width: 12),
                ElevatedButton.icon(
                  onPressed: _busy ? null : _submit,
                  icon: const Icon(Icons.cloud_upload),
                  label: const Text('Submit for approval'),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              _status,
              style: TextStyle(
                color: _status.startsWith('Error') ? Colors.red : Colors.black87,
              ),
            )
          ],
        ),
      ),
    );
  }
}
