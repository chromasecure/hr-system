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
          IconButton(icon: const Icon(Icons.save), onPressed: _loading ? null : _save),
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
                      Text(_status),
                    ],
                  ),
                ),
                Expanded(
                  child: ListView.builder(
                    itemCount: _rows.length,
                    itemBuilder: (_, i) {
                      final r = _rows[i];
                      return ListTile(
                        title: Text('${r.code} • ${r.name}'),
                        subtitle: TextField(
                          controller: r.remarkCtrl,
                          decoration: const InputDecoration(hintText: 'Remark'),
                          onChanged: (v) => r.remark = v,
                        ),
                        trailing: DropdownButton<String>(
                          value: r.status,
                          items: const [
                            DropdownMenuItem(value: 'in', child: Text('Present')),
                            DropdownMenuItem(value: 'out', child: Text('Out')),
                            DropdownMenuItem(value: 'absent', child: Text('Absent')),
                          ],
                          onChanged: (v) {
                            setState(() => r.status = v ?? 'absent');
                          },
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
  String remark;
  final TextEditingController remarkCtrl;
  _AttRow({
    required this.code,
    required this.name,
    required this.status,
    required this.remark,
  }) : remarkCtrl = TextEditingController(text: remark);
}
