<?php
require_once 'config.php';
requireLogin();
requireAdmin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price_per_liter = (float)$_POST['price_per_liter'];
    $service_fee = (float)$_POST['service_fee'];
    $tax_rate = (float)$_POST['tax_rate'];
    $company_name = $db->real_escape_string($_POST['company_name']);
    $company_address = $db->real_escape_string($_POST['company_address']);
    $company_phone = $db->real_escape_string($_POST['company_phone']);
    $company_email = $db->real_escape_string($_POST['company_email']);
    $low_water_alert = (float)$_POST['low_water_alert_level'];
    $high_usage_alert = (float)$_POST['high_usage_alert_level'];

    // Update settings
    $settings = [
        'price_per_liter' => $price_per_liter,
        'service_fee' => $service_fee,
        'tax_rate' => $tax_rate,
        'company_name' => $company_name,
        'company_address' => $company_address,
        'company_phone' => $company_phone,
        'company_email' => $company_email,
        'low_water_alert_level' => $low_water_alert,
        'high_usage_alert_level' => $high_usage_alert
    ];

    $updated = 0;
    foreach ($settings as $key => $value) {
        $value_str = (string)$value;
        $result = $db->query("SELECT id FROM settings WHERE setting_key = '$key'");
        if ($result->num_rows > 0) {
            $db->query("UPDATE settings SET setting_value = '$value_str' WHERE setting_key = '$key'");
        } else {
            $db->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value_str')");
        }
        $updated++;
    }

    $message = 'Settings updated successfully';
    $message_type = 'success';
    logAction('UPDATE', 'settings', 0, $settings);
}

// Get current settings
$pricePerLiter = getSetting('price_per_liter', 0.5);
$serviceFee = getSetting('service_fee', 5000);
$taxRate = getSetting('tax_rate', 10);
$companyName = getSetting('company_name', 'JEMA Water Management');
$companyAddress = getSetting('company_address', 'Mogadishu, Somalia');
$companyPhone = getSetting('company_phone', '+252 61 XXX XXXX');
$companyEmail = getSetting('company_email', 'info@jema.so');
$lowWaterAlert = getSetting('low_water_alert_level', 20000);
$highUsageAlert = getSetting('high_usage_alert_level', 500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - JEMA Water Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-bar">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                    <h2>System Settings</h2>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo escape($_SESSION['full_name']); ?></span>
                    </div>
                </div>
            </header>

            <div class="content">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo escape($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" class="row">
                    <!-- Company Information -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Company Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" class="form-control" name="company_name" value="<?php echo escape($companyName); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" name="company_address" value="<?php echo escape($companyAddress); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="company_phone" value="<?php echo escape($companyPhone); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="company_email" value="<?php echo escape($companyEmail); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Settings -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Billing Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Price per Liter (SOS)</label>
                                    <input type="number" class="form-control" name="price_per_liter" value="<?php echo escape($pricePerLiter); ?>" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Monthly Service Fee (SOS)</label>
                                    <input type="number" class="form-control" name="service_fee" value="<?php echo escape($serviceFee); ?>" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tax Rate (%)</label>
                                    <input type="number" class="form-control" name="tax_rate" value="<?php echo escape($taxRate); ?>" step="0.01" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Thresholds -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Alert Thresholds</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Low Water Level Alert (Liters)</label>
                                    <input type="number" class="form-control" name="low_water_alert_level" value="<?php echo escape($lowWaterAlert); ?>" step="1" required>
                                    <small class="form-text text-muted">Alert triggered when available water is below this level</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">High Usage Alert (Liters)</label>
                                    <input type="number" class="form-control" name="high_usage_alert_level" value="<?php echo escape($highUsageAlert); ?>" step="1" required>
                                    <small class="form-text text-muted">Alert triggered when customer usage exceeds this level</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>System Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Application Name</label>
                                    <input type="text" class="form-control" value="<?php echo escape(APP_NAME); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Version</label>
                                    <input type="text" class="form-control" value="<?php echo escape(APP_VERSION); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Application URL</label>
                                    <input type="text" class="form-control" value="<?php echo escape(APP_URL); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Current Date & Time</label>
                                    <input type="text" class="form-control" value="<?php echo date('Y-m-d H:i:s'); ?>" disabled>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="col-12 mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
