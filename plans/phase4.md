PHASE 4: Gym info + Multiple Branches + Trainers + Events

Build:
1. MySQL: complete branches (address, lat/lng, phone, whatsapp, email), branch_timings, branch_photos, facilities, branch_facilities, parking_info, trainers, trainer_certifications, events, announcements, banners, attendance (user_id, branch_id, check_in, check_out, source manual/face_machine).
2. Backend: GET branches, GET branches/{id} (details, timings, facilities, parking, photos), GET trainers, GET trainers/{id}, GET events, GET announcements, GET banners, GET attendance (history), POST attendance/checkin (stub, device-key protected, for future face machine).
3. Admin CRUD: Branches (timings, photos, facilities, parking), Facilities, Trainers + certifications, Events, Announcements, Banners; manual attendance entry; member attendance view.
4. Flutter: Branch selector (all gym data per branch), Gym details (address, map/directions via url_launcher, timings, facilities, parking info, photo gallery grid + fullscreen viewer), Trainer list + profile + certifications, Events/Announcements, Home banners, Contact Gym (call, WhatsApp, email), Attendance History (list + monthly calendar, note "Face check-in coming soon").
Do not break earlier phases. End with test checklist.