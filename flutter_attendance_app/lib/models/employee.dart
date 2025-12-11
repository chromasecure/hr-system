import 'package:hive/hive.dart';

part 'employee.g.dart';

@HiveType(typeId: 1)
class Employee extends HiveObject {
  @HiveField(0)
  int id;

  @HiveField(1)
  String name;

  @HiveField(2)
  String employeeCode;

  @HiveField(3)
  String faceTemplate; // base64 / serialized embedding

  @HiveField(4)
  String approvalStatus; // approved | pending | rejected

  @HiveField(5)
  String? localImagePath; // on-device cached face image

  Employee({
    required this.id,
    required this.name,
    required this.employeeCode,
    required this.faceTemplate,
    this.approvalStatus = 'approved',
    this.localImagePath,
  });

  factory Employee.fromJson(Map<String, dynamic> json) => Employee(
        id: json['id'] as int,
        name: json['name'] as String,
        employeeCode: json['employee_code'] as String,
        faceTemplate: (json['face_template_hash'] ?? '') as String,
        approvalStatus: (json['approval_status'] ?? 'approved') as String,
        localImagePath: json['face_image_path'] as String?,
      );
}
