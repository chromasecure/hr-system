// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'offline_log.dart';

class OfflineLogAdapter extends TypeAdapter<OfflineLog> {
  @override
  final int typeId = 2;

  @override
  OfflineLog read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return OfflineLog(
      employeeId: fields[0] as int,
      capturedAt: fields[1] as String,
      eventType: fields[2] as String,
      meta: (fields[3] as Map).cast<String, dynamic>(),
    );
  }

  @override
  void write(BinaryWriter writer, OfflineLog obj) {
    writer
      ..writeByte(4)
      ..writeByte(0)
      ..write(obj.employeeId)
      ..writeByte(1)
      ..write(obj.capturedAt)
      ..writeByte(2)
      ..write(obj.eventType)
      ..writeByte(3)
      ..write(obj.meta);
  }
}
