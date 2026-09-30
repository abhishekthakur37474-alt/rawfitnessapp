class ApiResponse<T> {
  const ApiResponse({
    required this.status,
    required this.message,
    this.data,
  });

  final bool status;
  final String message;
  final T? data;

  factory ApiResponse.fromJson(
    Map<String, dynamic> json, [
    T Function(dynamic json)? parse,
  ]) {
    final raw = json['data'];
    return ApiResponse<T>(
      status: json['status'] == true || json['status'] == 'success',
      message: (json['message'] as String?) ?? '',
      data: raw == null ? null : (parse != null ? parse(raw) : raw as T?),
    );
  }
}
