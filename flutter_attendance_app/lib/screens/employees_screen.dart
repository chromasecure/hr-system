import 'dart:io';

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';

import '../models/employee.dart';
import '../services/api_service.dart';
import '../services/sync_service.dart';
import 'register_employee_screen.dart';

class EmployeesScreen extends StatefulWidget {
  final SyncService syncService;
  final ApiService api;
  final List<CameraDescription> cameras;
  const EmployeesScreen(
      {super.key,
      required this.syncService,
      required this.api,
      required this.cameras});

  @override
  State<EmployeesScreen> createState() => _EmployeesScreenState();
}

class _EmployeesScreenState extends State<EmployeesScreen> {
  bool _loading = false;

  Future<void> _refresh() async {
    setState(() => _loading = true);
    try {
      await widget.syncService.refreshEmployees();
    } finally {
      setState(() => _loading = false);
    }
  }

  Future<void> _openRegister() async {
    await Navigator.of(context).push(MaterialPageRoute(
        builder: (_) => RegisterEmployeeScreen(
              cameras: widget.cameras,
              syncService: widget.syncService,
              api: widget.api,
            )));
    setState(() {}); // refresh list from hive
  }

  @override
  Widget build(BuildContext context) {
    final emps = widget.syncService.cachedEmployees();
    return Scaffold(
      appBar: AppBar(
        title: const Text('Employees'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loading ? null : _refresh,
          ),
          IconButton(
            icon: const Icon(Icons.person_add_alt),
            onPressed: _openRegister,
          )
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openRegister,
        icon: const Icon(Icons.person_add),
        label: const Text('Attach Face'),
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: ListView.builder(
          itemCount: emps.length,
          itemBuilder: (_, i) {
            final Employee e = emps[i];
            return ListTile(
              leading: e.localImagePath != null &&
                      File(e.localImagePath!).existsSync()
                  ? CircleAvatar(
                      backgroundImage: FileImage(File(e.localImagePath!)),
                    )
                  : const CircleAvatar(child: Icon(Icons.person)),
              title: Text(e.name),
              subtitle: Text('${e.employeeCode} • ${e.approvalStatus}'),
              trailing: const Icon(Icons.face_retouching_natural),
            );
          },
        ),
      ),
    );
  }
}
