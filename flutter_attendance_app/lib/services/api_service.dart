import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/employee.dart';
import '../models/offline_log.dart';
import '../services/storage_service.dart';

class ApiService {
  String baseUrl;
  String apiPrefix;
  ApiService({required String baseUrl, String apiPrefix = '/api'})
    : baseUrl = _normalizeBase(baseUrl),
      apiPrefix = _normalizePrefix(apiPrefix);


  void updateBaseUrl(String url) {
    baseUrl = _normalizeBase(url);
  }

  void updateApiPrefix(String prefix) {
    apiPrefix = _normalizePrefix(prefix);
  }

  static String _normalizeBase(String url) =>
      url.endsWith('/') ? url.substring(0, url.length - 1) : url;

  static String _normalizePrefix(String prefix) {
    if (prefix.isEmpty) return '';
    var p = prefix;
    if (!p.startsWith('/')) p = '/$p';
    if (p.endsWith('/')) p = p.substring(0, p.length - 1);
    return p;
  }

  Uri _uri(String path) => Uri.parse('$baseUrl$apiPrefix$path');
  Uri _loginUri() {
    final b = baseUrl.endsWith('/') ? baseUrl.substring(0, baseUrl.length - 1) : baseUrl;
    return Uri.parse('$b/api/web/login');
  }

  Map<String, String> _headers() {
    final deviceToken = StorageService.getDeviceToken();
    final authToken = StorageService.getAuthToken();
    final headers = <String, String>{
      'Content-Type': 'application/json',
    };
    if (deviceToken != null && deviceToken.isNotEmpty) {
      headers['X-Device-Token'] = deviceToken;
    }
    if (authToken != null && authToken.isNotEmpty) {
      headers['Authorization'] = 'Bearer $authToken';
    }
    return headers;
  }

  Future<void> loginManager({
  required String email,
  required String password,
}) async {
  final url = _uri('/api/web/login');
  final resp = await http.post(
    url,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: jsonEncode({'email': email, 'password': password}),
  );

  if (resp.statusCode == 200) {
    final data = jsonDecode(resp.body);
    final token = data['token'] as String?;
    final branch = data['branch_code'] as String?;
    if (token == null) {
      throw Exception('No token returned from $url: ${resp.body}');
    }
    await StorageService.setAuthToken(token);
    await StorageService.setManagerEmail(email);
    if (branch != null) {
      await StorageService.setBranchCode(branch);
    }
  } else {
    throw Exception(
      'HTTP ${resp.statusCode} calling $url: ${resp.body}',
    );
  }
}


  Future<Employee> registerEmployee({
    required String name,
    required String employeeCode,
    required String faceTemplate,
    required String faceImageBase64,
  }) async {
    final url = _uri('/employee/register');
    final body = {
      'name': name,
      'employee_code': employeeCode,
      'face_template_hash': faceTemplate,
      'face_image_base64': faceImageBase64,
    };
    try {
      final resp =
          await http.post(url, headers: _headers(), body: jsonEncode(body));
      if (resp.statusCode == 200 || resp.statusCode == 201) {
        final data = jsonDecode(resp.body);
        final empJson = (data['employee'] ?? data) as Map<String, dynamic>;
        final emp = Employee.fromJson(empJson);
        await _persistEmployee(emp);
        return emp;
      }
      throw Exception('Register employee failed: ${resp.body}');
    } catch (_) {
      // offline or server rejected: still keep locally for branch review
      final tempId = DateTime.now().millisecondsSinceEpoch * -1;
      final emp = Employee(
        id: tempId,
        name: name,
        employeeCode: employeeCode,
        faceTemplate: faceTemplate,
        approvalStatus: 'pending',
      );
      await _persistEmployee(emp);
      return emp;
    }
  }

  Future<Employee> attachFaceToEmployeeCode({
    required String employeeCode,
    required String faceTemplate,
    required String faceImageBase64,
  }) async {
    final url = _uri('/employee/attach-face');
    final body = {
      'employee_code': employeeCode,
      'face_template_hash': faceTemplate,
      'face_image_base64': faceImageBase64,
    };
    try {
      final resp =
          await http.post(url, headers: _headers(), body: jsonEncode(body));
      if (resp.statusCode == 200 || resp.statusCode == 201) {
        final data = jsonDecode(resp.body);
        final empJson = (data['employee'] ?? data) as Map<String, dynamic>;
        final emp = Employee.fromJson(empJson);
        await _persistEmployee(emp);
        return emp;
      }
      throw Exception('Attach face failed: ${resp.body}');
    } catch (_) {
      final tempId = DateTime.now().millisecondsSinceEpoch * -1;
      final emp = Employee(
        id: tempId,
        name: employeeCode,
        employeeCode: employeeCode,
        faceTemplate: faceTemplate,
        approvalStatus: 'pending',
      );
      await _persistEmployee(emp);
      return emp;
    }
  }

  Future<Employee?> identifyFace(String faceImageBase64) async {
    final url = _uri('/attendance/identify');
    final resp = await http.post(url,
        headers: _headers(),
        body: jsonEncode({'face_image_base64': faceImageBase64}));
    if (resp.statusCode == 200) {
      final data = jsonDecode(resp.body);
      final empJson = (data['employee'] ?? data) as Map<String, dynamic>;
      final emp = Employee.fromJson(empJson);
      await _persistEmployee(emp);
      return emp;
    }
    return null;
  }

  Future<void> approveEmployee(int employeeId) async {
    final url = _uri('/admin/employee/$employeeId/approve');
    try {
      final resp = await http.post(url, headers: _headers());
      if (resp.statusCode != 200 && resp.statusCode != 204) {
        throw Exception('Approve failed: ${resp.body}');
      }
    } finally {
      final box = StorageService.employees();
      Employee? emp;
      for (final e in box.values) {
        if (e.id == employeeId) {
          emp = e;
          break;
        }
      }
      if (emp != null) {
        emp.approvalStatus = 'approved';
        await emp.save();
      }
    }
  }

  Future<void> _persistEmployee(Employee emp) async {
    final box = StorageService.employees();
    await box.put(emp.id, emp);
  }

  Future<void> registerDevice({
    required String branchCode,
    required String deviceName,
    required String registrationSecret,
  }) async {
    final url = _uri('/device/register-or-login');
    final resp = await http.post(url,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'branch_code': branchCode,
          'device_name': deviceName,
          'registration_secret': registrationSecret,
        }));
    if (resp.statusCode == 200) {
      final data = jsonDecode(resp.body);
      final token = data['device_token'] as String?;
      if (token != null) {
        await StorageService.setDeviceToken(token);
        await StorageService.setBranchCode(branchCode);
        await StorageService.setDeviceName(deviceName);
      } else {
        throw Exception('No device_token returned');
      }
    } else {
      throw Exception('Register failed: ${resp.body}');
    }
  }

  Future<List<Employee>> fetchEmployees() async {
    final url = _uri('/api/web/employees');
    final resp = await http.get(url, headers: _headers());
    if (resp.statusCode == 200) {
      final data = jsonDecode(resp.body);
      final list = (data['employees'] as List)
          .map((e) => Employee.fromJson(e as Map<String, dynamic>))
          .toList();
      final box = StorageService.employees();
      await box.clear();
      for (final emp in list) {
        final cachedPath = StorageService.getFacePath(emp.id);
        if (cachedPath != null) {
          emp.localImagePath = cachedPath;
        }
        await box.put(emp.id, emp);
      }
      return list;
    } else {
      throw Exception('Failed to load employees: ${resp.body}');
    }
  }

  Future<void> markAttendance({
    required int employeeId,
    String? employeeCode,
    required String capturedAt,
    required double confidence,
    String? faceImageBase64,
  }) async {
    final url = _uri('/attendance/mark');
    final body = {
      'employee_id': employeeId,
      'employee_code': employeeCode,
      'captured_at': capturedAt,
      'event_type': 'auto',
      'meta': {
        'confidence': confidence,
        'device': StorageService.getDeviceName() ?? '',
        'face_image_base64': faceImageBase64,
      }
    };
    try {
      final resp =
          await http.post(url, headers: _headers(), body: jsonEncode(body));
      if (resp.statusCode != 200) {
        throw Exception('Server rejected: ${resp.body}');
      }
    } catch (_) {
      // offline: store locally
      final log = OfflineLog(
          employeeId: employeeId,
          capturedAt: capturedAt,
          meta: {
            'confidence': confidence,
            'employee_code': employeeCode,
          });
      await StorageService.offlineLogs().add(log);
    }
  }

  Future<void> syncOffline() async {
    final logsBox = StorageService.offlineLogs();
    if (logsBox.isEmpty) return;
    final logs = logsBox.values.map((e) => e.toJson()).toList();
    final url = _uri('/attendance/sync-offline');
    final resp = await http.post(url,
        headers: _headers(), body: jsonEncode({'logs': logs}));
    if (resp.statusCode == 200) {
      await logsBox.clear();
    } else {
      throw Exception('Sync failed: ${resp.body}');
    }
  }
}
