import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../services/sync_service.dart';
import '../services/storage_service.dart';

class LoginScreen extends StatefulWidget {
  final ApiService api;
  final SyncService syncService;
  final VoidCallback onLoggedIn;
  const LoginScreen(
      {super.key,
      required this.api,
      required this.syncService,
      required this.onLoggedIn});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _busy = false;
  String _status = 'Sign in with branch manager account.';
  String _baseUrl = StorageService.getBaseUrl() ?? 'http://localhost';
  String _apiPrefix = StorageService.getApiPrefix() ?? '/api';
  String get _resolvedLoginUrl => '$_baseUrl/api/web/login';

  Future<void> _login() async {
    if (_emailCtrl.text.isEmpty || _passCtrl.text.isEmpty) {
      setState(() => _status = 'Email and password are required.');
      return;
    }
    setState(() {
      _busy = true;
      _status = 'Signing in...';
    });
    try {
      widget.api.updateBaseUrl(_baseUrl);
      widget.api.updateApiPrefix(_apiPrefix);
      await widget.api.loginManager(
          email: _emailCtrl.text.trim(), password: _passCtrl.text.trim());
      await widget.syncService.refreshEmployees();
      widget.onLoggedIn();
    } catch (e) {
      setState(() => _status = 'Login failed: $e');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _changeBaseUrl() async {
    final baseCtrl = TextEditingController(text: _baseUrl);
    final prefixCtrl = TextEditingController(text: _apiPrefix);
    final result = await showDialog<Map<String, String>?>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('API Base URL'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
                controller: baseCtrl,
                decoration:
                    const InputDecoration(hintText: 'e.g. http://192.168.0.10')),
            const SizedBox(height: 12),
            TextField(
              controller: prefixCtrl,
              decoration: const InputDecoration(hintText: 'Prefix e.g. /api'),
            ),
          ],
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
          ElevatedButton(
              onPressed: () =>
                  Navigator.pop(context, {'base': baseCtrl.text.trim(), 'prefix': prefixCtrl.text.trim()}),
              child: const Text('Save')),
        ],
      ),
    );
    if (result != null) {
      await StorageService.setBaseUrl(result['base'] ?? _baseUrl);
      await StorageService.setApiPrefix(result['prefix'] ?? _apiPrefix);
      setState(() {
        _baseUrl = result['base'] ?? _baseUrl;
        _apiPrefix = result['prefix'] ?? _apiPrefix;
        _status = 'Using $_resolvedLoginUrl';
      });
    }
  }

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 24),
              Text('Branch Manager Login',
                  style: Theme.of(context).textTheme.headlineSmall),
              const SizedBox(height: 24),
              TextField(
                controller: _emailCtrl,
                decoration: const InputDecoration(labelText: 'Email / Username'),
              ),
              TextField(
                controller: _passCtrl,
                obscureText: true,
                decoration: const InputDecoration(labelText: 'Password'),
              ),
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'API: $_resolvedLoginUrl',
                      style: Theme.of(context).textTheme.bodySmall,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  TextButton(
                      onPressed: _busy ? null : _changeBaseUrl,
                      child: const Text('Change API URL')),
                ],
              ),
              const SizedBox(height: 8),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _busy ? null : _login,
                  child: Text(_busy ? 'Signing in...' : 'Login'),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                _status,
                style: TextStyle(
                    color: _status.startsWith('Login failed')
                        ? Colors.red
                        : Colors.black87),
              )
            ],
          ),
        ),
      ),
    );
  }
}
