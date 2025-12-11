import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../models/employee.dart';

class SyncService {
  final ApiService api;
  SyncService(this.api);

  Future<void> refreshEmployees() async {
    await api.fetchEmployees();
  }

  Future<void> syncOffline() async {
    await api.syncOffline();
  }

  List<Employee> cachedEmployees() => StorageService.employees().values.toList();

  Future<void> upsertEmployee(Employee emp) async {
    await StorageService.employees().put(emp.id, emp);
  }
}
