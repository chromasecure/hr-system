import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/employee.dart';
import '../services/storage_service.dart';

class ApiService {
  String baseUrl;
  String apiPrefix;
  ApiService({required String baseUrl, String apiPrefix = '/api'})
      : baseUrl = _normalizeBase(baseUrl),
        apiPrefix = _normalizePrefix(apiPrefix);

  void updateBaseUrl(String url) => baseUrl = _normalizeBase(url);
  void updateApiPrefix(String prefix) => apiPrefix = _normalizePrefix(prefix);

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
    final authToken = StorageService.getAuthToken();
    final headers = <String, String>{'Content-Type': 'application/json'};
    if (authToken != null && authToken.isNotEmpty) {
      headers['Authorization'] = 'Bearer $authToken';
    }
    return headers;
  }

  Future<void> loginManager({
    required String email,
    required String password,
  }) async {
    final url = _loginUri();
    final resp = await http.post(url,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}));
    if (resp.statusCode == 200) {
      final data = jsonDecode(resp.body);
      final token = data['token'] as String?;
      final manager = data['manager'] as Map<String, dynamic>?;
      if (token == null || manager == null) {
        throw Exception('No token returned');
      }
      await StorageService.setAuthToken(token);
      await StorageService.setManagerEmail(manager['username'] as String? ?? email);
      final branchId = manager['branch_id'];
      if (branchId != null) {
        await StorageService.setBranchCode(branchId.toString());
      }
    } else {
      throw Exception('Login failed: ${resp.body}');
    }
  }

  Future<List<Employee>> fetchEmployees() async {
    final url = _uri('/web/employees');
    final resp = await http.get(url, headers: _headers());
    if (resp.statusCode == 200) {
      final data = jsonDecode(resp.body);
      final list = (data['employees'] as List)
          .map((e) => Employee.fromJson(e as Map<String, dynamic>))
          .where((e) => e.employeeCode.isNotEmpty)
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
    required String employeeCode,
    required String timestamp,
    required String status,
    String? faceImageBase64,
  }) async {
    final url = _uri('/attendance/mark');
    final body = {
      'employee_code': employeeCode,
      'timestamp': timestamp,
      'status': status,
      'meta': {'face_image_base64': faceImageBase64}
    };
    final resp =
        await http.post(url, headers: _headers(), body: jsonEncode(body));
    if (resp.statusCode != 200) {
      throw Exception('Server rejected: ${resp.body}');
    }
  }

  Future<List<Map<String, dynamic>>> fetchTodayAttendance(String date) async {
    final url = _uri('/web/attendance/today?date=$date');
    final resp = await http.get(url, headers: _headers());
    if (resp.statusCode == 200) {
      dynamic data;
      try {
        data = jsonDecode(resp.body);
      } catch (_) {
        throw Exception('Invalid response: ${resp.body}');
      }
      final list = (data['employees'] as List)
          .map((e) => e as Map<String, dynamic>)
          .toList();
      return list;
    } else {
      throw Exception('Fetch attendance failed: ${resp.body}');
    }
  }

  Future<void> updateTodayAttendance({
    required String date,
    required List<Map<String, String>> records,
  }) async {
    final url = _uri('/web/attendance/update');
    final body = {'date': date, 'records': records};
    final resp =
        await http.post(url, headers: _headers(), body: jsonEncode(body));
    if (resp.statusCode != 200) {
      throw Exception('Update attendance failed: ${resp.body}');
    }
  }

  Future<void> syncOffline() async {
    final logsBox = StorageService.offlineLogs();
    if (logsBox.isEmpty) return;
    final logs = logsBox.values.map((e) => e.toJson()).toList();
    final url = _uri('/attendance/sync-offline');
    final resp =
        await http.post(url, headers: _headers(), body: jsonEncode({'logs': logs}));
    if (resp.statusCode == 200) {
      await logsBox.clear();
    } else {
      throw Exception('Sync failed: ${resp.body}');
    }
  }

  Future<void> createPendingEmployee({
    required String name,
    required String employeeCode,
    String? contact,
    String? designationId,
    String? basicSalary,
    String? commission,
    required String joiningDate,
    required String imageBase64,
  }) async {
    final url = _uri('/web/employees/create-pending');
    final body = {
      'name': name,
      'employee_code': employeeCode,
      'contact': contact,
      'designation_id': designationId,
      'basic_salary': basicSalary,
      'commission': commission,
      'joining_date': joiningDate,
      'image': imageBase64,
    };
    final resp =
        await http.post(url, headers: _headers(), body: jsonEncode(body));
    if (resp.statusCode != 200) {
      throw Exception('Create pending failed: ${resp.body}');
    }
  }

  Future<void> attachFace({
    required String employeeCode,
    required String faceImageBase64,
    String? faceTemplate,
  }) async {
    final url = _uri('/web/employees/attach-face');
    final body = {
      'employee_code': employeeCode,
      'image': faceImageBase64,
      if (faceTemplate != null) 'face_template': faceTemplate,
    };
    final resp =
        await http.post(url, headers: _headers(), body: jsonEncode(body));
    if (resp.statusCode != 200) {
      throw Exception('Attach face failed: ${resp.body}');
    }
  }
}
