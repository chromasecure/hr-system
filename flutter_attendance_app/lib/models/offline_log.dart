import 'package:hive/hive.dart';

part 'offline_log.g.dart';

@HiveType(typeId: 2)
class OfflineLog extends HiveObject {
  @HiveField(0)
  int employeeId;

  @HiveField(1)
  String capturedAt;

  @HiveField(2)
  String eventType;

  @HiveField(3)
  Map<String, dynamic> meta;

  OfflineLog({
    required this.employeeId,
    required this.capturedAt,
    this.eventType = 'auto',
    this.meta = const {},
  });

  Map<String, dynamic> toJson() => {
        'employee_id': employeeId,
        'captured_at': capturedAt,
        'event_type': eventType,
        'meta': meta,
      };
}
