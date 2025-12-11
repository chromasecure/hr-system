import 'package:flutter/material.dart';
import '../services/api_service.dart';

class TodayAttendanceScreen extends StatefulWidget {
  final ApiService api;
  const TodayAttendanceScreen({super.key, required this.api});

  @override
  State<TodayAttendanceScreen> createState() => _TodayAttendanceScreenState();
}

class _TodayAttendanceScreenState extends State<TodayAttendanceScreen> {
  bool _loading = true;
  String _status = '';
  final String _date = DateTime.now().toIso8601String().substring(0, 10);
  List<_AttRow> _rows = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _status = 'Loading...';
    });
    try {
      final data = await widget.api.fetchTodayAttendance(_date);
      setState(() {
        _rows = data
            .map((e) => _AttRow(
                  code: e['code']?.toString() ?? '',
                  name: e['name']?.toString() ?? '',
                  status: e['status']?.toString() ?? 'absent',
                  originalStatus: e['status']?.toString() ?? 'absent',
                  remark: e['remark']?.toString() ?? '',
                ))
            .toList();
        _status = 'Loaded';
      });
    } catch (e) {
      setState(() => _status = 'Error: $e');
    } finally {
      setState(() => _loading = false);
    }
  }

  Future<void> _save() async {
    setState(() {
      _loading = true;
      _status = 'Saving...';
    });
    try {
      for (final row in _rows) {
        if (row.status != row.originalStatus && row.remark.trim().isEmpty) {
          throw Exception('Please add remarks for changes (e.g. ${row.code}).');
        }
      }
      final records = _rows
          .map((r) =>
              {'employee_code': r.code, 'status': r.status, 'remark': r.remark})
          .toList();
      await widget.api.updateTodayAttendance(date: _date, records: records);
      setState(() => _status = 'Saved');
    } catch (e) {
      setState(() => _status = 'Error: $e');
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Today Attendance'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loading ? null : _load),
          IconButton(
              icon: const Icon(Icons.verified),
              tooltip: 'Approve & Save',
              onPressed: _loading ? null : _save),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(8.0),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Date: $_date'),
                      Flexible(
                          child:
                              Text(_status, overflow: TextOverflow.ellipsis)),
                    ],
                  ),
                ),
                if (_rows.isEmpty)
                  const Expanded(
                      child: Center(child: Text('No employees found.')))
                else
                  Expanded(
                    child: ListView.builder(
                      itemCount: _rows.length,
                      itemBuilder: (_, i) {
                        final r = _rows[i];
                        return Card(
                          margin: const EdgeInsets.symmetric(
                              horizontal: 12, vertical: 6),
                          child: Padding(
                            padding: const EdgeInsets.all(8),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text('${r.code} • ${r.name}',
                                        style: Theme.of(context)
                                            .textTheme
                                            .titleMedium),
                                    _StatusChip(status: r.status),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                DropdownButton<String>(
                                  value: r.status,
                                  items: const [
                                    DropdownMenuItem(
                                        value: 'in', child: Text('Present')),
                                    DropdownMenuItem(
                                        value: 'out', child: Text('Out')),
                                    DropdownMenuItem(
                                        value: 'absent',
                                        child: Text('Absent / Not scanned')),
                                  ],
                                  onChanged: (v) {
                                    setState(() =>
                                        r.status = v ?? r.originalStatus);
                                  },
                                ),
                                TextField(
                                  controller: r.remarkCtrl,
                                  decoration: const InputDecoration(
                                      hintText:
                                          'Remarks (required when changing)'),
                                  onChanged: (v) => r.remark = v,
                                  maxLines: 2,
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ),
              ],
            ),
    );
  }
}

class _AttRow {
  final String code;
  final String name;
  String status;
  final String originalStatus;
  String remark;
  final TextEditingController remarkCtrl;
  _AttRow({
    required this.code,
    required this.name,
    required this.status,
    required this.originalStatus,
    required this.remark,
  }) : remarkCtrl = TextEditingController(text: remark);
}

class _StatusChip extends StatelessWidget {
  final String status;
  const _StatusChip({required this.status});

  Color _color() {
    switch (status) {
      case 'in':
        return Colors.green;
      case 'out':
        return Colors.orange;
      default:
        return Colors.red;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Chip(
      label: Text(status.toUpperCase()),
      backgroundColor: _color().withOpacity(0.15),
      labelStyle: TextStyle(color: _color()),
    );
  }
}
