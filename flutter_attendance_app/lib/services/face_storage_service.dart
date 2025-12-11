import 'dart:io';
import 'dart:typed_data';

import 'package:path_provider/path_provider.dart';

import 'storage_service.dart';

class FaceStorageService {
  static Future<String> saveFaceBytes(
      Uint8List bytes, {
      required int employeeId,
      String? fileName,
    }) async {
    final dir = await getApplicationDocumentsDirectory();
    final facesDir = Directory('${dir.path}/faces');
    if (!facesDir.existsSync()) {
      facesDir.createSync(recursive: true);
    }
    final name =
        fileName ?? 'emp_${employeeId}_${DateTime.now().millisecondsSinceEpoch}.jpg';
    final file = File('${facesDir.path}/$name');
    await file.writeAsBytes(bytes, flush: true);
    await StorageService.setFacePath(employeeId, file.path);
    return file.path;
  }

  static String? facePath(int employeeId) => StorageService.getFacePath(employeeId);

  static Future<Uint8List?> readFaceBytes(int employeeId) async {
    final path = facePath(employeeId);
    if (path == null) return null;
    final file = File(path);
    if (!file.existsSync()) return null;
    return file.readAsBytes();
  }

  static Future<void> deleteForEmployee(int employeeId) async {
    final path = facePath(employeeId);
    if (path == null) return;
    final file = File(path);
    if (file.existsSync()) {
      await file.delete();
    }
    await StorageService.settings().delete('face_path_$employeeId');
  }
}
