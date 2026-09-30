import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import '../../widgets/shimmer_list.dart';

class PhotoViewerScreen extends StatefulWidget {
  const PhotoViewerScreen({
    super.key,
    required this.urls,
    this.initialIndex = 0,
  });

  final List<String> urls;
  final int initialIndex;

  @override
  State<PhotoViewerScreen> createState() => _PhotoViewerScreenState();
}

class _PhotoViewerScreenState extends State<PhotoViewerScreen> {
  late final PageController _pages;
  late int _index;

  @override
  void initState() {
    super.initState();
    _index = widget.initialIndex.clamp(0, widget.urls.isEmpty ? 0 : widget.urls.length - 1);
    _pages = PageController(initialPage: _index);
  }

  @override
  void dispose() {
    _pages.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        title: Text('${_index + 1} / ${widget.urls.length}'),
      ),
      body: widget.urls.isEmpty
          ? const Center(child: Icon(Icons.image_outlined, color: AppColors.muted, size: 48))
          : PageView.builder(
              controller: _pages,
              onPageChanged: (i) => setState(() => _index = i),
              itemCount: widget.urls.length,
              itemBuilder: (_, i) {
                return InteractiveViewer(
                  minScale: 1,
                  maxScale: 4,
                  child: Center(
                    child: AppNetworkImage(
                      url: widget.urls[i],
                      fit: BoxFit.contain,
                      borderRadius: BorderRadius.zero,
                    ),
                  ),
                );
              },
            ),
    );
  }
}
