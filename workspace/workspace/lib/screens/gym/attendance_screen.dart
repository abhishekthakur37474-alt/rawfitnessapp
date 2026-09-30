import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../providers/gym_provider.dart';
import '../../services/attendance_service.dart';
import '../../widgets/branch_picker.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/shimmer_list.dart';
import '../../widgets/status_chip.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  static const _face = AttendanceService();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final gym = context.read<GymProvider>();
      await gym.loadBranches();
      await gym.loadAttendance(force: true);
    });
  }

  @override
  Widget build(BuildContext context) {
    final gym = context.watch<GymProvider>();
    return Scaffold(
      appBar: AppBar(title: const Text('Attendance')),
      body: Column(
        children: [
          const SizedBox(height: 8),
          const BranchChipBar(),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: InfoCard(
              child: Row(
                children: [
                  const Icon(Icons.face_retouching_natural, color: AppColors.info),
                  const SizedBox(width: 12),
                  Expanded(child: Text(_face.comingSoonMessage, style: const TextStyle(height: 1.4))),
                ],
              ),
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => gym.loadAttendance(force: true),
              child: _body(gym),
            ),
          ),
        ],
      ),
    );
  }

  Widget _body(GymProvider gym) {
    if (gym.loadingAttendance && gym.attendance.isEmpty) {
      return const ShimmerList(itemCount: 6, height: 72);
    }
    if (gym.attendanceError != null && gym.attendance.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(height: MediaQuery.of(context).size.height * 0.2),
          ErrorState(message: gym.attendanceError!, onRetry: () => gym.loadAttendance(force: true)),
        ],
      );
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        _MonthHeader(
          month: gym.attendanceMonth,
          onPrev: () => gym.setAttendanceMonth(
            DateTime(gym.attendanceMonth.year, gym.attendanceMonth.month - 1),
          ),
          onNext: () => gym.setAttendanceMonth(
            DateTime(gym.attendanceMonth.year, gym.attendanceMonth.month + 1),
          ),
        ),
        const SizedBox(height: 12),
        _MonthCalendar(month: gym.attendanceMonth, marked: gym.attendanceDays),
        const SizedBox(height: 20),
        Text('History', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
        const SizedBox(height: 12),
        if (gym.attendance.isEmpty)
          const EmptyState(
            title: 'No visits this month',
            subtitle: 'Face check-in coming soon. Manual visits appear here.',
            icon: Icons.event_busy_outlined,
          )
        else
          ...gym.attendance.map(
            (row) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: InfoCard(
                padding: const EdgeInsets.all(14),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(Formatters.dateTime(row.checkIn), style: const TextStyle(fontWeight: FontWeight.w600)),
                          const SizedBox(height: 4),
                          Text(
                            row.checkOut == null
                                ? 'Checked in'
                                : 'Out ${Formatters.dateTime(row.checkOut!)}',
                            style: const TextStyle(color: AppColors.muted, fontSize: 12),
                          ),
                          if ((row.branchName ?? '').isNotEmpty) ...[
                            const SizedBox(height: 4),
                            Text(row.branchName!, style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                          ],
                        ],
                      ),
                    ),
                    StatusChip(
                      label: row.source == 'face_machine' ? 'FACE' : 'MANUAL',
                      tone: row.source == 'face_machine' ? StatusTone.success : StatusTone.info,
                    ),
                  ],
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class _MonthHeader extends StatelessWidget {
  const _MonthHeader({required this.month, required this.onPrev, required this.onNext});

  final DateTime month;
  final VoidCallback onPrev;
  final VoidCallback onNext;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        IconButton(onPressed: onPrev, icon: const Icon(Icons.chevron_left)),
        Expanded(
          child: Text(
            DateFormat('MMMM yyyy').format(month),
            textAlign: TextAlign.center,
            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16),
          ),
        ),
        IconButton(onPressed: onNext, icon: const Icon(Icons.chevron_right)),
      ],
    );
  }
}

class _MonthCalendar extends StatelessWidget {
  const _MonthCalendar({required this.month, required this.marked});

  final DateTime month;
  final Set<DateTime> marked;

  @override
  Widget build(BuildContext context) {
    final first = DateTime(month.year, month.month, 1);
    final daysInMonth = DateTime(month.year, month.month + 1, 0).day;
    final lead = first.weekday % 7;
    final today = DateTime.now();
    final cells = lead + daysInMonth;

    return InfoCard(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 8),
      child: Column(
        children: [
          Row(
            children: [
              for (final d in const ['S', 'M', 'T', 'W', 'T', 'F', 'S'])
                Expanded(
                  child: Text(
                    d,
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: AppColors.muted, fontSize: 12, fontWeight: FontWeight.w600),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 8),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: cells,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 7,
              mainAxisExtent: 36,
            ),
            itemBuilder: (_, i) {
              if (i < lead) return const SizedBox.shrink();
              final day = i - lead + 1;
              final date = DateTime(month.year, month.month, day);
              final isMarked = marked.contains(date);
              final isToday = today.year == date.year && today.month == date.month && today.day == date.day;
              return Center(
                child: Container(
                  width: 32,
                  height: 32,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: isMarked ? AppColors.primary.withValues(alpha: 0.22) : Colors.transparent,
                    border: Border.all(
                      color: isToday ? AppColors.primary : (isMarked ? AppColors.primary : Colors.transparent),
                    ),
                    borderRadius: BorderRadius.circular(AppTheme.radiusFull),
                  ),
                  child: Text(
                    '$day',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: isMarked || isToday ? FontWeight.w700 : FontWeight.w500,
                      color: isMarked ? AppColors.primary : AppColors.foreground,
                    ),
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}
