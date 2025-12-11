import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'screens/camera_screen.dart';
import 'screens/employees_screen.dart';
import 'screens/login_screen.dart';
import 'screens/settings_screen.dart';
import 'services/storage_service.dart';
import 'services/api_service.dart';
import 'services/sync_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await StorageService.init();
  await Hive.initFlutter();

  final cameras = await availableCameras();
  final storedBaseUrl =
      StorageService.getBaseUrl() ?? 'http://localhost'; // set in settings
  final storedPrefix = StorageService.getApiPrefix() ?? '/api';
  final api = ApiService(baseUrl: storedBaseUrl, apiPrefix: storedPrefix);
  final syncService = SyncService(api);

  // auto refresh employees on startup
  try {
    await syncService.refreshEmployees();
  } catch (_) {}

  runApp(MyApp(
    cameras: cameras,
    api: api,
    syncService: syncService,
  ));
}

class MyApp extends StatefulWidget {
  final List<CameraDescription> cameras;
  final ApiService api;
  final SyncService syncService;
  const MyApp(
      {super.key,
      required this.cameras,
      required this.api,
      required this.syncService});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  int _index = 0;
  bool _loggedIn = StorageService.getAuthToken() != null;

  Future<void> _handleLoggedIn() async {
    try {
      await widget.syncService.refreshEmployees();
    } catch (_) {}
    setState(() => _loggedIn = true);
  }


  Future<void> _logout() async {
    await StorageService.clearAuth();
    await StorageService.employees().clear();
    setState(() => _loggedIn = false);
  }

  @override
  Widget build(BuildContext context) {
    final screens = [
      CameraScreen(
          cameras: widget.cameras,
          syncService: widget.syncService,
          api: widget.api),
      EmployeesScreen(
          syncService: widget.syncService,
          api: widget.api,
          cameras: widget.cameras),
      SettingsScreen(
          api: widget.api,
          syncService: widget.syncService,
          onLogout: _logout),
    ];
    return MaterialApp(
      title: 'Attendance',
      theme: ThemeData(primarySwatch: Colors.indigo),
      home: _loggedIn
          ? Scaffold(
              body: screens[_index],
              bottomNavigationBar: BottomNavigationBar(
                currentIndex: _index,
                onTap: (i) => setState(() => _index = i),
                items: const [
                  BottomNavigationBarItem(
                      icon: Icon(Icons.camera), label: 'Camera'),
                  BottomNavigationBarItem(
                      icon: Icon(Icons.people), label: 'Employees'),
                  BottomNavigationBarItem(
                      icon: Icon(Icons.settings), label: 'Settings'),
                ],
              ),
            )
          : LoginScreen(
              api: widget.api,
              syncService: widget.syncService,
              onLoggedIn: _handleLoggedIn,
            ),
    );
  }
}
