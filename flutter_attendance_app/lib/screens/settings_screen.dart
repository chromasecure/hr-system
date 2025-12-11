import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../services/sync_service.dart';

class SettingsScreen extends StatefulWidget {
  final ApiService api;
  final SyncService syncService;
  final VoidCallback? onLogout;
  const SettingsScreen(
      {super.key, required this.api, required this.syncService, this.onLogout});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final _baseUrlCtrl = TextEditingController();
  final _prefixCtrl = TextEditingController();
  bool _busy = false;
  String _status = '';
  String? _managerEmail;

  @override
  void initState() {
    super.initState();
    _baseUrlCtrl.text = StorageService.getBaseUrl() ?? 'http://localhost';
    _prefixCtrl.text = StorageService.getApiPrefix() ?? '/api';
    _managerEmail = StorageService.getManagerEmail();
  }

  Future<void> _saveBaseUrl() async {
    setState(() {
      _busy = true;
      _status = 'Saving...';
    });
    final url = _baseUrlCtrl.text.trim();
    final prefix = _prefixCtrl.text.trim();
    await StorageService.setBaseUrl(url);
    await StorageService.setApiPrefix(prefix);
    widget.api.updateBaseUrl(url);
    widget.api.updateApiPrefix(prefix);
    try {
      await widget.syncService.refreshEmployees();
      _status = 'Saved. Employees refreshed.';
    } catch (e) {
      _status = 'Saved. Refresh failed: $e';
    } finally {
      setState(() => _busy = false);
    }
  }

  Future<void> _sync() async {
    setState(() {
      _busy = true;
      _status = 'Syncing...';
    });
    try {
      await widget.syncService.syncOffline();
      _status = 'Sync complete';
    } catch (e) {
      _status = 'Sync error: $e';
    } finally {
      setState(() => _busy = false);
    }
  }

  @override
  void dispose() {
    _baseUrlCtrl.dispose();
    _prefixCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Settings')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TextField(
              controller: _baseUrlCtrl,
              decoration: const InputDecoration(labelText: 'API Base URL'),
            ),
            TextField(
              controller: _prefixCtrl,
              decoration: const InputDecoration(labelText: 'API Prefix (e.g. /api)'),
            ),
            if (_managerEmail != null) ...[
              const SizedBox(height: 12),
              Text('Logged in as: $_managerEmail'),
            ],
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _busy ? null : _saveBaseUrl,
              child: const Text('Save API base URL'),
            ),
            ElevatedButton(
              onPressed: _busy ? null : _sync,
              child: const Text('Manual sync offline logs'),
            ),
            const SizedBox(height: 12),
            ElevatedButton(
              onPressed: widget.onLogout,
              style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
              child: const Text('Logout manager'),
            ),
            const SizedBox(height: 16),
            Text(_status),
          ],
        ),
      ),
    );
  }
}
