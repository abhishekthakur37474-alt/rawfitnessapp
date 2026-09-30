class GymBranch {
  const GymBranch({
    required this.id,
    required this.name,
    this.address,
    this.city,
    this.phone,
    this.whatsapp,
    this.email,
    this.lat,
    this.lng,
    this.description,
    this.coverUrl,
    this.timings = const [],
    this.facilities = const [],
    this.parking,
    this.photos = const [],
  });

  final int id;
  final String name;
  final String? address;
  final String? city;
  final String? phone;
  final String? whatsapp;
  final String? email;
  final double? lat;
  final double? lng;
  final String? description;
  final String? coverUrl;
  final List<BranchTiming> timings;
  final List<GymFacility> facilities;
  final ParkingInfo? parking;
  final List<GymPhoto> photos;

  String get subtitle {
    final parts = [if ((city ?? '').isNotEmpty) city, if ((address ?? '').isNotEmpty) address];
    return parts.join(' · ');
  }

  bool get hasCoords => lat != null && lng != null;

  factory GymBranch.fromJson(Map<String, dynamic> json) {
    return GymBranch(
      id: _asInt(json['id']),
      name: (json['name'] as String?) ?? '',
      address: json['address'] as String?,
      city: json['city'] as String?,
      phone: json['phone'] as String?,
      whatsapp: json['whatsapp'] as String?,
      email: json['email'] as String?,
      lat: json['lat'] == null ? null : _asDouble(json['lat']),
      lng: json['lng'] == null ? null : _asDouble(json['lng']),
      description: json['description'] as String?,
      coverUrl: json['cover_url'] as String?,
      timings: ((json['timings'] as List?) ?? const [])
          .map((e) => BranchTiming.fromJson((e as Map).cast<String, dynamic>()))
          .toList(),
      facilities: ((json['facilities'] as List?) ?? const [])
          .map((e) => GymFacility.fromJson((e as Map).cast<String, dynamic>()))
          .toList(),
      parking: json['parking'] is Map
          ? ParkingInfo.fromJson((json['parking'] as Map).cast<String, dynamic>())
          : null,
      photos: ((json['photos'] as List?) ?? const [])
          .map((e) => GymPhoto.fromJson((e as Map).cast<String, dynamic>()))
          .toList(),
    );
  }

  static int _asInt(dynamic v) {
    if (v is int) return v;
    return int.tryParse('$v') ?? 0;
  }

  static double _asDouble(dynamic v) {
    if (v is num) return v.toDouble();
    return double.tryParse('$v') ?? 0;
  }
}

class BranchTiming {
  const BranchTiming({
    required this.dayOfWeek,
    required this.day,
    this.openTime,
    this.closeTime,
    this.isClosed = false,
  });

  final int dayOfWeek;
  final String day;
  final String? openTime;
  final String? closeTime;
  final bool isClosed;

  String get label {
    if (isClosed) return 'Closed';
    final open = (openTime ?? '').length >= 5 ? openTime!.substring(0, 5) : openTime;
    final close = (closeTime ?? '').length >= 5 ? closeTime!.substring(0, 5) : closeTime;
    if ((open ?? '').isEmpty && (close ?? '').isEmpty) return '—';
    return '${open ?? '—'} – ${close ?? '—'}';
  }

  factory BranchTiming.fromJson(Map<String, dynamic> json) {
    return BranchTiming(
      dayOfWeek: GymBranch._asInt(json['day_of_week']),
      day: (json['day'] as String?) ?? '',
      openTime: json['open_time'] as String?,
      closeTime: json['close_time'] as String?,
      isClosed: json['is_closed'] == true || json['is_closed'] == 1,
    );
  }
}

class GymFacility {
  const GymFacility({required this.id, required this.name, this.icon});

  final int id;
  final String name;
  final String? icon;

  factory GymFacility.fromJson(Map<String, dynamic> json) {
    return GymFacility(
      id: GymBranch._asInt(json['id']),
      name: (json['name'] as String?) ?? '',
      icon: json['icon'] as String?,
    );
  }
}

class ParkingInfo {
  const ParkingInfo({
    this.hasParking = false,
    this.twoWheeler = false,
    this.fourWheeler = false,
    this.notes,
  });

  final bool hasParking;
  final bool twoWheeler;
  final bool fourWheeler;
  final String? notes;

  factory ParkingInfo.fromJson(Map<String, dynamic> json) {
    return ParkingInfo(
      hasParking: json['has_parking'] == true || json['has_parking'] == 1,
      twoWheeler: json['two_wheeler'] == true || json['two_wheeler'] == 1,
      fourWheeler: json['four_wheeler'] == true || json['four_wheeler'] == 1,
      notes: json['notes'] as String?,
    );
  }
}

class GymPhoto {
  const GymPhoto({required this.id, this.imageUrl});

  final int id;
  final String? imageUrl;

  factory GymPhoto.fromJson(Map<String, dynamic> json) {
    return GymPhoto(
      id: GymBranch._asInt(json['id']),
      imageUrl: json['image_url'] as String?,
    );
  }
}

class Trainer {
  const Trainer({
    required this.id,
    required this.name,
    this.branchId,
    this.branchName,
    this.imageUrl,
    this.role,
    this.bio,
    this.phone,
    this.certifications = const [],
  });

  final int id;
  final String name;
  final int? branchId;
  final String? branchName;
  final String? imageUrl;
  final String? role;
  final String? bio;
  final String? phone;
  final List<TrainerCert> certifications;

  factory Trainer.fromJson(Map<String, dynamic> json) {
    return Trainer(
      id: GymBranch._asInt(json['id']),
      name: (json['name'] as String?) ?? '',
      branchId: json['branch_id'] == null ? null : GymBranch._asInt(json['branch_id']),
      branchName: json['branch_name'] as String?,
      imageUrl: json['image_url'] as String?,
      role: json['role'] as String?,
      bio: json['bio'] as String?,
      phone: json['phone'] as String?,
      certifications: ((json['certifications'] as List?) ?? const [])
          .map((e) => TrainerCert.fromJson((e as Map).cast<String, dynamic>()))
          .toList(),
    );
  }
}

class TrainerCert {
  const TrainerCert({required this.id, required this.title, this.issuer, this.year});

  final int id;
  final String title;
  final String? issuer;
  final String? year;

  factory TrainerCert.fromJson(Map<String, dynamic> json) {
    return TrainerCert(
      id: GymBranch._asInt(json['id']),
      title: (json['title'] as String?) ?? '',
      issuer: json['issuer'] as String?,
      year: json['year'] as String?,
    );
  }
}

class GymEvent {
  const GymEvent({
    required this.id,
    required this.title,
    this.branchId,
    this.branchName,
    this.imageUrl,
    this.description,
    this.location,
    this.startsAt,
    this.endsAt,
  });

  final int id;
  final String title;
  final int? branchId;
  final String? branchName;
  final String? imageUrl;
  final String? description;
  final String? location;
  final DateTime? startsAt;
  final DateTime? endsAt;

  factory GymEvent.fromJson(Map<String, dynamic> json) {
    return GymEvent(
      id: GymBranch._asInt(json['id']),
      title: (json['title'] as String?) ?? '',
      branchId: json['branch_id'] == null ? null : GymBranch._asInt(json['branch_id']),
      branchName: json['branch_name'] as String?,
      imageUrl: json['image_url'] as String?,
      description: json['description'] as String?,
      location: json['location'] as String?,
      startsAt: _dt(json['starts_at']),
      endsAt: _dt(json['ends_at']),
    );
  }

  static DateTime? _dt(dynamic v) {
    if (v == null || '$v'.isEmpty) return null;
    return DateTime.tryParse('$v');
  }
}

class GymAnnouncement {
  const GymAnnouncement({
    required this.id,
    required this.title,
    this.body,
    this.branchName,
    this.publishedAt,
  });

  final int id;
  final String title;
  final String? body;
  final String? branchName;
  final DateTime? publishedAt;

  factory GymAnnouncement.fromJson(Map<String, dynamic> json) {
    return GymAnnouncement(
      id: GymBranch._asInt(json['id']),
      title: (json['title'] as String?) ?? '',
      body: json['body'] as String?,
      branchName: json['branch_name'] as String?,
      publishedAt: GymEvent._dt(json['published_at']),
    );
  }
}

class GymBanner {
  const GymBanner({
    required this.id,
    this.title,
    this.imageUrl,
    this.linkType,
    this.linkValue,
  });

  final int id;
  final String? title;
  final String? imageUrl;
  final String? linkType;
  final String? linkValue;

  factory GymBanner.fromJson(Map<String, dynamic> json) {
    return GymBanner(
      id: GymBranch._asInt(json['id']),
      title: json['title'] as String?,
      imageUrl: json['image_url'] as String?,
      linkType: json['link_type'] as String?,
      linkValue: json['link_value'] as String?,
    );
  }
}

class AttendanceRecord {
  const AttendanceRecord({
    required this.id,
    required this.checkIn,
    this.checkOut,
    this.branchId,
    this.branchName,
    this.source = 'manual',
  });

  final int id;
  final DateTime checkIn;
  final DateTime? checkOut;
  final int? branchId;
  final String? branchName;
  final String source;

  factory AttendanceRecord.fromJson(Map<String, dynamic> json) {
    return AttendanceRecord(
      id: GymBranch._asInt(json['id']),
      checkIn: GymEvent._dt(json['check_in']) ?? DateTime.now(),
      checkOut: GymEvent._dt(json['check_out']),
      branchId: json['branch_id'] == null ? null : GymBranch._asInt(json['branch_id']),
      branchName: json['branch_name'] as String?,
      source: (json['source'] as String?) ?? 'manual',
    );
  }
}
