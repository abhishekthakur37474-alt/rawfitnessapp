enum AttendanceSource { manual, faceMachine }

class AttendanceService {
  const AttendanceService();

  bool get faceCheckInAvailable => false;

  String get comingSoonMessage => 'Face check-in coming soon';
}
