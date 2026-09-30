import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_theme.dart';

class ShimmerList extends StatelessWidget {
  const ShimmerList({
    super.key,
    this.itemCount = 6,
    this.height = 88,
  });

  final int itemCount;
  final double height;

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: itemCount,
      separatorBuilder: (_, _) => const SizedBox(height: 12),
      itemBuilder: (_, _) => Shimmer.fromColors(
        baseColor: AppColors.surfaceHigh,
        highlightColor: AppColors.border,
        child: Container(
          height: height,
          decoration: BoxDecoration(
            color: AppColors.surfaceHigh,
            borderRadius: BorderRadius.circular(AppTheme.radiusLg),
          ),
        ),
      ),
    );
  }
}

class AppNetworkImage extends StatelessWidget {
  const AppNetworkImage({
    super.key,
    required this.url,
    this.width,
    this.height,
    this.fit = BoxFit.cover,
    this.borderRadius,
  });

  final String? url;
  final double? width;
  final double? height;
  final BoxFit fit;
  final BorderRadius? borderRadius;

  @override
  Widget build(BuildContext context) {
    final radius = borderRadius ?? BorderRadius.circular(AppTheme.radiusMd);
    Widget fallback = Container(
      width: width,
      height: height,
      color: AppColors.surfaceHigh,
      child: const Icon(Icons.image_outlined, color: AppColors.muted),
    );

    if (url == null || url!.isEmpty) {
      return ClipRRect(borderRadius: radius, child: fallback);
    }

    return ClipRRect(
      borderRadius: radius,
      child: Image.network(
        url!,
        width: width,
        height: height,
        fit: fit,
        errorBuilder: (_, _, _) => fallback,
        loadingBuilder: (context, child, progress) {
          if (progress == null) return child;
          return SizedBox(
            width: width,
            height: height,
            child: const Center(
              child: SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(strokeWidth: 2),
              ),
            ),
          );
        },
      ),
    );
  }
}
