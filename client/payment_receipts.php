<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/classes/Payment.php';
require_once __DIR__ . '/../includes/classes/PaymentProcessor.php';

requireRole('client');

$payment_service = new Payment($db);
$payment_id = $_GET['payment_id'] ?? null;

$payment = null;
$error = '';

if ($payment_id) {
    $payment = $payment_service->getPayment($payment_id);
    
    // Ensure client owns this payment
    if ($payment && $payment['client_id'] != $_SESSION['user']['id']) {
        $error = 'You do not have permission to view this payment';
        $payment = null;
    }
} else {
    $error = 'Payment ID not provided';
}

// Get all client payments with appointments
$stmt = $db->prepare("
    SELECT p.*, 
           a.appointment_date, a.status as appointment_status,
           s.name as service_name, s.price as service_price,
           u.name as stylist_name,
           r.receipt_number
    FROM payments p
    LEFT JOIN appointments a ON p.appointment_id = a.id
    LEFT JOIN services s ON a.service_id = s.id
    LEFT JOIN users u ON a.stylist_id = u.id
    LEFT JOIN receipts r ON p.id = r.payment_id
    WHERE a.client_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user']['id']]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipts - MUSA Beauty</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .payment-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .payment-detail {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #8b5fbf;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .payment-header h2 {
            margin: 0;
            color: #333;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-refunded {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .payment-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-item {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 4px;
            border-left: 4px solid #8b5fbf;
        }
        .info-label {
            font-size: 0.85rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }
        .info-value {
            font-size: 1.1rem;
            font-weight: bold;
            color: #333;
        }
        .payment-list {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
        }
        .payment-list h3 {
            margin-top: 0;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .payment-row:last-child {
            border-bottom: none;
        }
        .payment-date {
            flex: 1;
        }
        .payment-date strong {
            display: block;
            color: #333;
        }
        .payment-date small {
            color: #999;
        }
        .payment-amount {
            flex: 0 0 auto;
            margin-left: 1rem;
        }
        .payment-amount strong {
            color: #8b5fbf;
            font-size: 1.1rem;
        }
        .payment-method {
            flex: 0 0 auto;
            margin-left: 2rem;
            text-align: center;
        }
        .payment-method-badge {
            display: inline-block;
            background: #f0f8ff;
            border: 1px solid #8b5fbf;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #8b5fbf;
            font-weight: bold;
        }
        .payment-status {
            flex: 0 0 auto;
            margin-left: 1rem;
        }
        .action-links {
            flex: 0 0 auto;
            margin-left: 1rem;
        }
        .action-links a {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            background: #8b5fbf;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }
        .action-links a:hover {
            background: #7a4fa3;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }
        .receipt-detail {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .receipt-row strong {
            color: #333;
        }
        .receipt-total {
            border-top: 2px solid #8b5fbf;
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 1.2rem;
        }
        @media print {
            body {
                background: white;
            }
            .payment-container {
                margin: 0;
            }
            .action-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="payment-container">
        <h1>Payment & Receipt History</h1>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- All Payments List -->
        <?php if (!empty($payments)): ?>
            <div class="payment-list">
                <h3>Your Payments</h3>
                
                <?php foreach ($payments as $p): ?>
                    <div class="payment-row">
                        <div class="payment-date">
                            <strong><?= htmlspecialchars($p['service_name'] ?? 'Service') ?></strong>
                            <small><?= date('M d, Y H:i', strtotime($p['appointment_date'] ?? $p['created_at'])) ?></small>
                        </div>
                        
                        <div class="payment-amount">
                            <strong>KES <?= number_format($p['amount_paid'] ?? $p['amount']) ?></strong>
                        </div>
                        
                        <div class="payment-method">
                            <span class="payment-method-badge"><?= ucfirst($p['payment_method']) ?></span>
                        </div>
                        
                        <div class="payment-status">
                            <span class="status-badge status-<?= str_replace('_', '-', $p['payment_status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $p['payment_status'])) ?>
                            </span>
                        </div>

                        <div class="action-links">
                            <a href="?payment_id=<?= $p['id'] ?>" onclick="window.print(); return false;">Print Receipt</a>
                            <a href="?payment_id=<?= $p['id'] ?>">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No payment records found.</p>
                <p><a href="/client/book_appointment.php" style="color: #8b5fbf; text-decoration: underline;">Book an appointment</a> to get started.</p>
            </div>
        <?php endif; ?>

        <!-- Detailed Payment View -->
        <?php if ($payment && !$error): ?>
            <div class="payment-detail">
                <div class="payment-header">
                    <div>
                        <h2>Payment Receipt</h2>
                        <small style="color: #999;">Receipt #<?= htmlspecialchars($payment['receipt_number'] ?? 'N/A') ?></small>
                    </div>
                    <span class="status-badge status-<?= str_replace('_', '-', $payment['payment_status']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $payment['payment_status'])) ?>
                    </span>
                </div>

                <div class="payment-info">
                    <div class="info-item">
                        <div class="info-label">Service</div>
                        <div class="info-value"><?= htmlspecialchars($payment['service_name'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Stylist</div>
                        <div class="info-value"><?= htmlspecialchars($payment['stylist_name'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Appointment Date</div>
                        <div class="info-value"><?= date('M d, Y H:i', strtotime($payment['appointment_date'] ?? 'now')) ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Payment Method</div>
                        <div class="info-value"><?= ucfirst($payment['payment_method']) ?></div>
                    </div>
                </div>

                <div class="receipt-detail">
                    <div class="receipt-row">
                        <span>Service Amount:</span>
                        <strong>KES <?= number_format($payment['amount']) ?></strong>
                    </div>

                    <?php if ($payment['discount_amount'] > 0): ?>
                        <div class="receipt-row">
                            <span>Discount:</span>
                            <strong style="color: #51cf66;">-KES <?= number_format($payment['discount_amount']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <?php if ($payment['tax_amount'] > 0): ?>
                        <div class="receipt-row">
                            <span>Tax:</span>
                            <strong>KES <?= number_format($payment['tax_amount']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <div class="receipt-row receipt-total">
                        <span>Total Paid:</span>
                        <strong>KES <?= number_format($payment['amount_paid'] ?? $payment['amount']) ?></strong>
                    </div>

                    <?php if ($payment['reference_id']): ?>
                        <div class="receipt-row" style="margin-top: 1rem; font-size: 0.9rem; color: #999;">
                            <span>Transaction Reference:</span>
                            <span><?= htmlspecialchars($payment['reference_id']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($payment['paid_at']): ?>
                        <div class="receipt-row" style="font-size: 0.9rem; color: #999;">
                            <span>Paid on:</span>
                            <span><?= date('M d, Y H:i', strtotime($payment['paid_at'])) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                    <button onclick="window.print()" style="background-color: #8b5fbf; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                        Print Receipt
                    </button>
                    <button onclick="history.back()" style="background-color: #6c757d; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-left: 0.5rem;">
                        Back
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
