import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_theme.dart';
import '../core/utils/external_links.dart';
import '../models/gym.dart';
import 'shimmer_list.dart';

class BannerCarousel extends StatelessWidget {
  const BannerCarousel({super.key, required this.banners});

  final List<GymBanner> banners;

  @override
  Widget build(BuildContext context) {
    if (banners.isEmpty) return const SizedBox.shrink();
    return SizedBox(
      height: 160,
      child: PageView.builder(
        controller: PageController(viewportFraction: 0.92),
        itemCount: banners.length,
        itemBuilder: (context, index) {
          final banner = banners[index];
          return Padding(
            padding: const EdgeInsets.only(right: 10),
            child: Material(
              color: Colors.transparent,
              child: InkWell(
                onTap: () => _open(context, banner),
                borderRadius: BorderRadius.circular(AppTheme.radiusLg),
                child: Ink(
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(AppTheme.radiusLg),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      AppNetworkImage(
                        url: banner.imageUrl,
                        borderRadius: BorderRadius.circular(AppTheme.radiusLg),
                      ),
                      if ((banner.title ?? '').isNotEmpty)
                        Align(
                          alignment: Alignment.bottomLeft,
                          child: Container(
                            width: double.infinity,
                            padding: const EdgeInsets.fromLTRB(14, 24, 14, 12),
                            decoration: const BoxDecoration(
                              borderRadius: BorderRadius.vertical(
                                bottom: Radius.circular(AppTheme.radiusLg),
                              ),
                              gradient: LinearGradient(
                                begin: Alignment.topCenter,
                                end: Alignment.bottomCenter,
                                colors: [Colors.transparent, Color(0xCC0B0F14)],
                              ),
                            ),
                            child: Text(
                              banner.title!,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 15,
                              ),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Future<void> _open(BuildContext context, GymBanner banner) async {
    final type = (banner.linkType ?? '').toLowerCase();
    final value = (banner.linkValue ?? '').trim();
    if (type == 'event' && value.isNotEmpty) {
      context.push('/gym/events/$value');
      return;
    }
    if (type == 'announcement' && value.isNotEmpty) {
      context.push('/gym/announcements/$value');
      return;
    }
    if ((type == 'url' || type.isEmpty) && value.isNotEmpty) {
      final copied = await ExternalLinks.url(value);
      if (copied != null && context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Link copied')),
        );
      }
    }
  }
}
