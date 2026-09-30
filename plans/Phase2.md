PHASE 2: Home + Membership + Packages + Payment UI (Coming Soon)

Build:
1. MySQL: branches (minimal for now), packages (name, duration_days, price, discount, description, branch_id, is_active), memberships (user_id, package_id, start_date, end_date, amount, paid_amount, due_amount, status), payments (membership_id, amount, mode, txn_ref, status, receipt_no).
2. Backend endpoints: GET packages, GET membership/current, GET membership/history, POST membership/renew (creates pending request), GET payments/history, GET receipts/{id}, POST payments/initiate (stub returns status "coming_soon"). Status logic: active / expiring (<=7 days) / expired / none.
3. Admin panel (PHP + Bootstrap 5): secure login (password_hash, session, CSRF), sidebar layout, dashboard stats (total, active, expiring in 7 days, expired members), Packages CRUD, Members list/search/view (with Government ID image, Approve/Reject ID with reason), assign/renew membership manually, record offline payment (cash/UPI/card) + printable receipt.
4. Flutter:
   - Home screen: if NO active membership -> show "Get Membership" pop-up/bottom sheet with dynamic packages. It must be DISMISSIBLE (close button + tap outside), never blocks app; after dismiss show persistent "Get your membership" card on Home; pop-up shows again next app launch if still no membership.
   - If membership exists -> membership card (plan, member ID with QR, validity, days left, due amount, status chip).
   - Membership Details, Fees & Due Amount, Validity/Expiry, Renewal flow (choose plan -> summary -> payment method screen).
   - Payment screens (UPI/Cards/Net Banking UI) where Pay button shows a "Coming Soon" dialog. PaymentService abstraction so gateway can be plugged later.
   - Payment history + Receipt/Invoice screen.
Do not break Phase 1. End with test checklist.