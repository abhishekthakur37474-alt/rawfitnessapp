import 'package:flutter/material.dart';

class QrView extends StatelessWidget {
  const QrView({
    super.key,
    required this.data,
    this.size = 120,
    this.background = Colors.white,
    this.foreground = const Color(0xFF0B0F14),
  });

  final String data;
  final double size;
  final Color background;
  final Color foreground;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      padding: const EdgeInsets.all(6),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(12),
      ),
      child: CustomPaint(
        size: Size.square(size - 12),
        painter: _QrPainter(
          data: data,
          background: background,
          foreground: foreground,
        ),
      ),
    );
  }
}

class _QrPainter extends CustomPainter {
  _QrPainter({
    required this.data,
    required this.background,
    required this.foreground,
  });

  final String data;
  final Color background;
  final Color foreground;

  static const int _modules = 25;

  @override
  void paint(Canvas canvas, Size size) {
    final cell = size.width / _modules;
    final bg = Paint()..color = background;
    final fg = Paint()..color = foreground;
    canvas.drawRect(Offset.zero & size, bg);

    final reserved = List.generate(_modules, (_) => List.filled(_modules, false));

    void reserve(int startRow, int startCol) {
      for (var i = -1; i <= 7; i++) {
        for (var j = -1; j <= 7; j++) {
          final r = startRow + i;
          final c = startCol + j;
          if (r >= 0 && r < _modules && c >= 0 && c < _modules) {
            reserved[r][c] = true;
          }
        }
      }
    }

    void drawFinder(int row, int col) {
      canvas.drawRect(
        Rect.fromLTWH(col * cell, row * cell, 7 * cell, 7 * cell),
        fg,
      );
      canvas.drawRect(
        Rect.fromLTWH((col + 1) * cell, (row + 1) * cell, 5 * cell, 5 * cell),
        bg,
      );
      canvas.drawRect(
        Rect.fromLTWH((col + 2) * cell, (row + 2) * cell, 3 * cell, 3 * cell),
        fg,
      );
    }

    reserve(0, 0);
    reserve(0, _modules - 7);
    reserve(_modules - 7, 0);

    drawFinder(0, 0);
    drawFinder(0, _modules - 7);
    drawFinder(_modules - 7, 0);

    var state = 0x811c9dc5;
    for (final unit in data.codeUnits) {
      state ^= unit;
      state = (state * 0x01000193) & 0xFFFFFFFF;
    }
    if (state == 0) state = 0x9e3779b9;

    int next() {
      state ^= (state << 13) & 0xFFFFFFFF;
      state ^= state >> 17;
      state ^= (state << 5) & 0xFFFFFFFF;
      return state & 0xFFFFFFFF;
    }

    for (var r = 0; r < _modules; r++) {
      for (var c = 0; c < _modules; c++) {
        if (reserved[r][c]) continue;
        if (next() % 2 == 0) {
          canvas.drawRect(
            Rect.fromLTWH(c * cell, r * cell, cell, cell),
            fg,
          );
        }
      }
    }
  }

  @override
  bool shouldRepaint(covariant _QrPainter oldDelegate) =>
      oldDelegate.data != data ||
      oldDelegate.background != background ||
      oldDelegate.foreground != foreground;
}
