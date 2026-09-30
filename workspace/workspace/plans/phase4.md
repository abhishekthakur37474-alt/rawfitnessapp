PHASE 4: Gym info + Multiple Branches + Trainers + Events — COMPLETED

Build:
1. MySQL: complete branches (address, lat/lng, phone, whatsapp, email), branch_timings, branch_photos, facilities, branch_facilities, parking_info, trainers, trainer_certifications, events, announcements, banners, attendance (user_id, branch_id, check_in, check_out, source manual/face_machine).
2. Backend: GET branches, GET branches/{id} (details, timings, facilities, parking, photos), GET trainers, GET trainers/{id}, GET events, GET announcements, GET banners, GET attendance (history), POST attendance/checkin (stub, device-key protected, for future face machine).
3. Admin CRUD: Branches (timings, photos, facilities, parking), Facilities, Trainers + certifications, Events, Announcements, Banners; manual attendance entry; member attendance view.
4. Flutter: Branch selector (all gym data per branch), Gym details (address, map/directions via copied maps link, timings, facilities, parking info, photo gallery grid + fullscreen viewer), Trainer list + profile + certifications, Events/Announcements, Home banners, Contact Gym (call, WhatsApp, email as copied links — no extra packages), Attendance History (list + monthly calendar, note "Face check-in coming soon").
Do not break earlier phases. End with test checklist.

## Phase 4 test checklist

- `GET /api/health` returns phase 4
- `GET /api/branches` lists active branches; `GET /api/branches/{id}` returns timings, facilities, parking, photos
- `GET /api/trainers` and `GET /api/trainers/{id}` (certs) honor optional `?branch_id=`
- `GET /api/events`, `/announcements`, `/banners` honor optional `?branch_id=` (null = all branches)
- `GET /api/attendance` returns history + `face_checkin_available: false` and coming-soon note; `?month=YYYY-MM` filters
- `POST /api/attendance/checkin` requires `X-DEVICE-KEY` / `ATTENDANCE_DEVICE_KEY`; open session checks out, else checks in as `face_machine`
- Admin: Branches (timings, photos, facilities, parking), Facilities, Trainers + certs, Events, Announcements, Banners CRUD; manual attendance entry
- Flutter Home: branch chips, banners, gym shortcuts, upcoming event/announcement
- Flutter: gym details, photo grid + fullscreen, trainers + profile, events/announcements, attendance list + calendar
- Contact actions copy tel / WhatsApp / email / maps links (no `url_launcher`)
- Face check-in remains a coming-soon stub
- Phase 1–3 flows still work
