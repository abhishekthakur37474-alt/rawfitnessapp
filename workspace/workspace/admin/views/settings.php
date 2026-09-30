<h1 class="h3 mb-3">Settings</h1>
<form method="post" action="<?= htmlspecialchars($base) ?>/settings" class="row g-3">
    <?= Csrf::field() ?>
    <div class="col-lg-6">
        <div class="card-panel p-4 h-100">
            <h2 class="h5 mb-3">App</h2>
            <div class="mb-3">
                <label class="form-label">App name</label>
                <input class="form-control" name="app_name" value="<?= htmlspecialchars((string) ($settings['app_name'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Contact phone</label>
                <input class="form-control" name="contact_phone" value="<?= htmlspecialchars((string) ($settings['contact_phone'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">WhatsApp</label>
                <input class="form-control" name="contact_whatsapp" value="<?= htmlspecialchars((string) ($settings['contact_whatsapp'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Contact email</label>
                <input class="form-control" name="contact_email" value="<?= htmlspecialchars((string) ($settings['contact_email'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Terms URL</label>
                <input class="form-control" name="terms_url" value="<?= htmlspecialchars((string) ($settings['terms_url'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Privacy URL</label>
                <input class="form-control" name="privacy_url" value="<?= htmlspecialchars((string) ($settings['privacy_url'] ?? '')) ?>">
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-panel p-4 mb-3">
            <h2 class="h5 mb-3">OneSignal</h2>
            <div class="mb-3">
                <label class="form-label">App ID</label>
                <input class="form-control" name="onesignal_app_id" value="<?= htmlspecialchars((string) ($settings['onesignal_app_id'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">REST API Key</label>
                <input class="form-control" name="onesignal_rest_api_key" value="<?= htmlspecialchars((string) ($settings['onesignal_rest_api_key'] ?? '')) ?>">
            </div>
        </div>
        <div class="card-panel p-4">
            <h2 class="h5 mb-3">OTP (apitxt)</h2>
            <div class="mb-3">
                <label class="form-label">Provider</label>
                <input class="form-control" name="otp_provider" value="<?= htmlspecialchars((string) ($settings['otp_provider'] ?? 'apitxt')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Base URL</label>
                <input class="form-control" name="otp_apitxt_base_url" value="<?= htmlspecialchars((string) ($settings['otp_apitxt_base_url'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">API key</label>
                <input class="form-control" name="otp_apitxt_api_key" value="<?= htmlspecialchars((string) ($settings['otp_apitxt_api_key'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Sender</label>
                <input class="form-control" name="otp_apitxt_sender" value="<?= htmlspecialchars((string) ($settings['otp_apitxt_sender'] ?? '')) ?>">
            </div>
        </div>
    </div>
    <div class="col-12">
        <button class="btn btn-warning" type="submit">Save settings</button>
    </div>
</form>
