// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'employee.dart';

class EmployeeAdapter extends TypeAdapter<Employee> {
  @override
  final int typeId = 1;

  @override
  Employee read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return Employee(
      id: fields[0] as int,
      name: fields[1] as String,
      employeeCode: fields[2] as String,
      faceTemplate: fields[3] as String,
      approvalStatus: (fields[4] as String?) ?? 'approved',
      localImagePath: fields[5] as String?,
    );
  }

  @override
  void write(BinaryWriter writer, Employee obj) {
    writer
      ..writeByte(6)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.name)
      ..writeByte(2)
      ..write(obj.employeeCode)
      ..writeByte(3)
      ..write(obj.faceTemplate)
      ..writeByte(4)
      ..write(obj.approvalStatus)
      ..writeByte(5)
      ..write(obj.localImagePath);
  }
}
