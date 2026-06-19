<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Require admin role
requireRole('admin');

// Get current config
$stmt = $db->prepare("SELECT * FROM receipts LIMIT 1");
$stmt->execute();
$receipt_config = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_payment_methods') {
        $cash_enabled = $_POST['cash_enabled'] ?? 0;
        $mpesa_enabled = $_POST['mpesa_enabled'] ?? 0;
        $card_enabled = $_POST['card_enabled'] ?? 0;

        // Store in app config (could be .env or config table)
        // For now, we'll just validate that at least one is enabled
        if (!($cash_enabled || $mpesa_enabled || $card_enabled)) {
            $error = "At least one payment method must be enabled";
        } else {
            // Update config
            file_put_contents(__DIR__ . '/../.payment_config', json_encode([
                'cash_enabled' => $cash_enabled,
                'mpesa_enabled' => $mpesa_enabled,
                'card_enabled' => $card_enabled,
                'updated_at' => date('Y-m-d H:i:s')
            ]));
            $message = "Payment methods updated successfully";
        }
    }

    if ($action === 'update_tax_settings') {
        $tax_rate = floatval($_POST['tax_rate'] ?? 0);
        
        if ($tax_rate < 0 || $tax_rate > 100) {
            $error = "Tax rate must be between 0 and 100";
        } else {
            file_put_contents(__DIR__ . '/../.tax_config', json_encode([
                'tax_rate' => $tax_rate,
                'updated_at' => date('Y-m-d H:i:s')
            ]));
            $message = "Tax settings updated successfully";
        }
    }
}

// Load current settings
$payment_config = json_decode(file_get_contents(__DIR__ . '/../.payment_config') ?? '{}', true);
$tax_config = json_decode(file_get_contents(__DIR__ . '/../.tax_config') ?? '{}', true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Configuration - MUSA Beauty Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .config-container {
            max-width: 800px;
            margin: 2rem auto;
        }
        .config-section {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .config-section h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #8b5fbf;
            padding-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 0.5rem;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
        }
        .btn {
            background-color: #8b5fbf;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #7a4fa3;
        }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background-color: #f0f8ff;
            border-left: 4px solid #8b5fbf;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            color: #555;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="config-container">
        <h1>Payment Configuration</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Payment Methods Configuration -->
        <div class="config-section">
            <h3>Enabled Payment Methods</h3>
            
            <div class="info-box">
                <p>Select which payment methods customers can use when booking appointments.</p>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="update_payment_methods">

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="cash_enabled" value="1" 
                               <?= ($payment_config['cash_enabled'] ?? 1) ? 'checked' : '' ?>>
                        <span><strong>Cash Payment</strong> - Direct payment at salon</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="mpesa_enabled" value="1"
                               <?= ($payment_config['mpesa_enabled'] ?? 1) ? 'checked' : '' ?>>
                        <span><strong>M-Pesa Payment</strong> - Mobile money via Daraja STK Push</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="card_enabled" value="1"
                               <?= ($payment_config['card_enabled'] ?? 0) ? 'checked' : '' ?>>
                        <span><strong>Card Payment</strong> - Credit/Debit card (Stripe/Pesapal)</span>
                    </label>
                </div>

                <button type="submit" class="btn">Update Payment Methods</button>
            </form>
        </div>

        <!-- Tax Settings -->
        <div class="config-section">
            <h3>Tax Settings</h3>

            <div class="info-box">
                <p>Configure tax rates for invoices and receipts.</p>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="update_tax_settings">

                <div class="form-group">
                    <label for="tax_rate">Tax Rate (%):</label>
                    <input type="number" id="tax_rate" name="tax_rate" 
                           step="0.1" min="0" max="100" 
                           value="<?= $tax_config['tax_rate'] ?? 16 ?>">
                </div>

                <div class="info-box">
                    <p>Current tax rate: <strong><?= number_format($tax_config['tax_rate'] ?? 16, 2) ?>%</strong></p>
                </div>

                <button type="submit" class="btn">Update Tax Settings</button>
            </form>
        </div>

        <!-- M-Pesa Configuration -->
        <div class="config-section">
            <h3>M-Pesa Configuration</h3>

            <div class="info-box">
                <p>M-Pesa credentials are stored in your <code>.env</code> file for security. Update the following environment variables:</p>
            </div>

            <div style="background: #f5f5f5; padding: 1rem; border-radius: 4px; margin: 1rem 0; font-family: monospace; font-size: 0.9rem; line-height: 1.6;">
                MPESA_ENVIRONMENT=sandbox<br>
                MPESA_CONSUMER_KEY=your_consumer_key<br>
                MPESA_CONSUMER_SECRET=your_consumer_secret<br>
                MPESA_SHORTCODE=174379<br>
                MPESA_PASSKEY=your_passkey<br>
                MPESA_CALLBACK_URL=http://localhost:8000/client/mpesa_callback.php
            </div>

            <p><small><strong>Note:</strong> After updating <code>.env</code>, restart the server for changes to take effect.</small></p>
        </div>

        <!-- Stripe Configuration -->
        <div class="config-section">
            <h3>Stripe Configuration</h3>

            <div class="info-box">
                <p>Add Stripe credentials to your <code>.env</code> file to enable card payments:</p>
            </div>

            <div style="background: #f5f5f5; padding: 1rem; border-radius: 4px; margin: 1rem 0; font-family: monospace; font-size: 0.9rem; line-height: 1.6;">
                STRIPE_PUBLIC_KEY=pk_test_...<br>
                STRIPE_SECRET_KEY=sk_test_...
            </div>

            <p><small><strong>Note:</strong> Get your keys from <a href="https://dashboard.stripe.com" target="_blank">Stripe Dashboard</a>. Use test keys for development.</small></p>
        </div>

        <!-- Receipt Settings -->
        <div class="config-section">
            <h3>Receipt Settings</h3>

            <div class="info-box">
                <p>Receipts are automatically generated with the format: <code>RCP-YYYYMMDD-XXXXX</code></p>
                <p style="margin-top: 0.5rem;">All receipts are stored in the database and can be reprinted or emailed to customers.</p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
