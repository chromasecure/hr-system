import 'dart:convert';
import 'dart:io';
import 'dart:math';
import 'dart:typed_data';
import 'dart:ui' show Rect;

import 'package:image/image.dart' as img;

import '../models/employee.dart';

class FaceRecognitionService {
  // In production, you would load a TFLite model to produce a robust embedding.
  // Here we compute a lightweight, deterministic luminance-based embedding that
  // can still be used for duplicate checks and quick local matching.

  List<double> decodeEmbedding(String base64Json) {
    if (base64Json.isEmpty) return [];
    try {
      final jsonStr = utf8.decode(base64Decode(base64Json));
      final list = (jsonDecode(jsonStr) as List).map((e) => (e as num).toDouble()).toList();
      return list;
    } catch (_) {
      return [];
    }
  }

  String encodeEmbedding(List<double> embedding) =>
      base64Encode(utf8.encode(jsonEncode(embedding)));

  Future<List<double>> embeddingFromFile(String path, {Rect? crop}) async {
    final bytes = await File(path).readAsBytes();
    return embeddingFromBytes(bytes, crop: crop);
  }

  Future<List<double>> embeddingFromBytes(Uint8List bytes, {Rect? crop}) async {
    final img.Image? decoded = img.decodeImage(bytes);
    if (decoded == null) return [];
    img.Image working = decoded;

    if (crop != null) {
      final int left = crop.left.floor().clamp(0, decoded.width - 1);
      final int top = crop.top.floor().clamp(0, decoded.height - 1);
      final int width = max(1, min(decoded.width - left, crop.width.ceil()));
      final int height = max(1, min(decoded.height - top, crop.height.ceil()));
      working = img.copyCrop(decoded, x: left, y: top, width: width, height: height);
    }

    // Normalize size and color space to create a compact embedding.
    final img.Image resized =
        img.copyResize(working, width: 64, height: 64, interpolation: img.Interpolation.average);
    final img.Image gray = img.grayscale(resized);

    final stepX = max(1, gray.width ~/ 16);
    final stepY = max(1, gray.height ~/ 16);
    final vector = <double>[];

    for (int y = 0; y < gray.height; y += stepY) {
      for (int x = 0; x < gray.width; x += stepX) {
        final pixel = gray.getPixel(x, y);
        vector.add(pixel.luminanceNormalized.toDouble());
      }
    }

    return _l2Normalize(vector);
  }

  double similarity(List<double> a, List<double> b) => _cosineSimilarity(a, b);

  bool isDuplicate(List<double> probe, List<Employee> employees,
          {double threshold = 0.9}) =>
      matchEmbedding(probe, employees, threshold: threshold) != null;

  Employee? matchEmbedding(List<double> probe, List<Employee> employees,
      {double threshold = 0.85}) {
    double best = 0.0;
    Employee? matched;
    for (final emp in employees) {
      final emb = decodeEmbedding(emp.faceTemplate);
      final sim = _cosineSimilarity(probe, emb);
      if (sim > best && sim >= threshold) {
        best = sim;
        matched = emp;
      }
    }
    return matched;
  }

  List<double> _l2Normalize(List<double> vec) {
    double norm = 0;
    for (final v in vec) {
      norm += v * v;
    }
    norm = sqrt(norm);
    if (norm == 0) return vec;
    return vec.map((e) => e / norm).toList();
  }

  double _cosineSimilarity(List<double> a, List<double> b) {
    if (a.isEmpty || b.isEmpty || a.length != b.length) return 0.0;
    double dot = 0, normA = 0, normB = 0;
    for (int i = 0; i < a.length; i++) {
      dot += a[i] * b[i];
      normA += a[i] * a[i];
      normB += b[i] * b[i];
    }
    final denom = sqrt(normA) * sqrt(normB);
    if (denom == 0) return 0.0;
    return dot / denom;
  }
}
