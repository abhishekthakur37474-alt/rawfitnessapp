import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../models/payment.dart';
import '../../providers/membership_provider.dart';
import '../../widgets/error_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/section_header.dart';
import '../../widgets/status_chip.dart';

class ReceiptScreen extends StatefulWidget {
  const ReceiptScreen({super.key, required this.paymentId});

  final int paymentId;

  @override
  State<ReceiptScreen> createState() => _ReceiptScreenState();
}

class _ReceiptScreenState extends State<ReceiptScreen> {
  Receipt? _receipt;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final receipt = await context.read<MembershipProvider>().fetchReceipt(widget.paymentId);
      if (!mounted) return;
      setState(() => _receipt = receipt);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Receipt')),
      body: SafeArea(child: _buildBody(context)),
    );
  }

  Widget _buildBody(BuildContext context) {
    if (_loading && _receipt == null) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null && _receipt == null) {
      return ErrorState(message: _error!, onRetry: _load);
    }

    final receipt = _receipt;
    if (receipt == null) {
      return const Center(child: Text('Receipt not found'));
    }

    final payment = receipt.payment;
    final memberName = (receipt.memberName ?? '').trim();

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
      children: [
        InfoCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      receipt.gymName,
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                  ),
                  StatusChip(
                    label: payment.status.toUpperCase(),
                    tone: payment.isSuccess ? StatusTone.success : StatusTone.danger,
                  ),
                ],
              ),
              const SizedBox(height: 4),
              const Text(
                'Payment receipt',
                style: TextStyle(color: AppColors.muted, fontSize: 12),
              ),
              const Divider(height: 24),
              _row('Receipt no.', payment.receiptNo ?? '—'),
              _row(
                'Date',
                payment.createdAt == null ? '—' : Formatters.dateTime(payment.createdAt!),
              ),
              _row('Payment mode', payment.mode.toUpperCase()),
              if ((payment.txnRef ?? '').isNotEmpty)
                _row('Transaction ref', payment.txnRef!),
            ],
          ),
        ),
        const SizedBox(height: 20),
        const SectionHeader(title: 'Member'),
        const SizedBox(height: 12),
        InfoCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _row('Name', memberName.isEmpty ? '—' : memberName),
              _row('Member ID', (receipt.memberId ?? '').isEmpty ? '—' : receipt.memberId!),
              _row('Mobile', (receipt.memberMobile ?? '').isEmpty ? '—' : receipt.memberMobile!),
            ],
          ),
        ),
        const SizedBox(height: 20),
        const SectionHeader(title: 'Plan'),
        const SizedBox(height: 12),
        InfoCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _row(
                'Package',
                (payment.packageName == null || payment.packageName!.isEmpty)
                    ? 'Membership'
                    : payment.packageName!,
              ),
              if (payment.startDate != null)
                _row('Start date', Formatters.date(payment.startDate!)),
              if (payment.endDate != null)
                _row('End date', Formatters.date(payment.endDate!)),
              const Divider(height: 24),
              Row(
                children: [
                  const Expanded(
                    child: Text(
                      'Amount paid',
                      style: TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                  Text(
                    Formatters.currency(payment.amount),
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: 20),
        const Center(
          child: Text(
            'This is a computer generated receipt.',
            style: TextStyle(color: AppColors.muted, fontSize: 12),
          ),
        ),
      ],
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Text(label, style: const TextStyle(color: AppColors.muted)),
          ),
          const SizedBox(width: 12),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }
}
