import 'package:hive/hive.dart';
import 'package:path_provider/path_provider.dart';
import '../models/employee.dart';
import '../models/offline_log.dart';

class StorageService {
  static const employeesBox = 'employees';
  static const settingsBox = 'settings';
  static const offlineLogsBox = 'offline_logs';

  static Future<void> init() async {
    final dir = await getApplicationDocumentsDirectory();
    Hive.init(dir.path);
    Hive
      ..registerAdapter(EmployeeAdapter())
      ..registerAdapter(OfflineLogAdapter());
    await Future.wait([
      Hive.openBox<Employee>(employeesBox),
      Hive.openBox(settingsBox),
      Hive.openBox<OfflineLog>(offlineLogsBox),
    ]);
  }

  static Box<Employee> employees() => Hive.box<Employee>(employeesBox);
  static Box settings() => Hive.box(settingsBox);
  static Box<OfflineLog> offlineLogs() => Hive.box<OfflineLog>(offlineLogsBox);

  static String? getAuthToken() => settings().get('auth_token');
  static Future<void> setAuthToken(String token) =>
      settings().put('auth_token', token);

  static Future<void> clearAuth() async {
    await settings().delete('auth_token');
    await settings().delete('manager_email');
    await settings().delete('branch_code');
  }

  static Future<void> setManagerEmail(String email) =>
      settings().put('manager_email', email);
  static String? getManagerEmail() => settings().get('manager_email');

  static Future<void> setBaseUrl(String url) =>
      settings().put('base_url', url);
  static String? getBaseUrl() => settings().get('base_url');

  static Future<void> setApiPrefix(String prefix) =>
      settings().put('api_prefix', prefix);
  static String? getApiPrefix() => settings().get('api_prefix');

  static Future<void> setBranchCode(String code) =>
      settings().put('branch_code', code);
  static String? getBranchCode() => settings().get('branch_code');

  static String? getFacePath(int employeeId) =>
      settings().get('face_path_$employeeId');
  static Future<void> setFacePath(int employeeId, String path) =>
      settings().put('face_path_$employeeId', path);
}
