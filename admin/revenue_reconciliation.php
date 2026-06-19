<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/classes/Payment.php';

// Require admin role
requireRole('admin');

$payment = new Payment($db);

// Get date filter
$selected_date = $_GET['date'] ?? date('Y-m-d');
$date_range = $_GET['range'] ?? 'day'; // day, week, month

$message = '';
$error = '';

// Handle reconciliation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reconcile_day') {
        $reconciliation_date = $_POST['date'] ?? date('Y-m-d');
        $opening_balance = intval($_POST['opening_balance'] ?? 0);
        $cash_received = intval($_POST['cash_received'] ?? 0);
        $cash_withdrawn = intval($_POST['cash_withdrawn'] ?? 0);
        $closing_balance = intval($_POST['closing_balance'] ?? 0);
        $notes = $_POST['notes'] ?? '';

        // Calculate expected vs actual
        $expected_balance = $opening_balance + $cash_received - $cash_withdrawn;
        $discrepancy = $closing_balance - $expected_balance;

        try {
            // Insert or update reconciliation
            $stmt = $db->prepare("
                INSERT INTO daily_cash_reconciliation 
                (reconciliation_date, opening_balance, cash_received, cash_withdrawn, closing_balance, discrepancy, reconciled_by, reconciled_at, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE 
                opening_balance = ?, cash_received = ?, cash_withdrawn = ?, closing_balance = ?, discrepancy = ?, reconciled_by = ?, reconciled_at = NOW(), notes = ?
            ");
            $stmt->execute([
                $reconciliation_date, $opening_balance, $cash_received, $cash_withdrawn, 
                $closing_balance, $discrepancy, $_SESSION['user_id'], $notes,
                $opening_balance, $cash_received, $cash_withdrawn, 
                $closing_balance, $discrepancy, $_SESSION['user_id'], $notes
            ]);

            $message = "Reconciliation saved successfully" . (abs($discrepancy) > 0 ? " (Discrepancy: KES " . abs($discrepancy) . ")" : "");
        } catch (PDOException $e) {
            $error = "Failed to save reconciliation: " . $e->getMessage();
        }
    }
}

// Get revenue data for selected period
$revenue_data = [];
if ($date_range === 'day') {
    $revenue_data = $payment->getDailyRevenue($selected_date, 'all');
} elseif ($date_range === 'week') {
    $start_date = date('Y-m-d', strtotime('monday this week', strtotime($selected_date)));
    $end_date = date('Y-m-d', strtotime('sunday this week', strtotime($selected_date)));
    $revenue_data = $payment->getRevenueRange($start_date, $end_date);
} elseif ($date_range === 'month') {
    $start_date = date('Y-m-01', strtotime($selected_date));
    $end_date = date('Y-m-t', strtotime($selected_date));
    $revenue_data = $payment->getRevenueRange($start_date, $end_date);
}

// Calculate totals
$total_revenue = 0;
$method_breakdown = [];
foreach ($revenue_data as $row) {
    if (isset($row['daily_total'])) {
        $total_revenue += $row['daily_total'];
    } elseif (isset($row['total'])) {
        $total_revenue += $row['total'];
    }
    
    $method = $row['payment_method'] ?? 'unknown';
    if (!isset($method_breakdown[$method])) {
        $method_breakdown[$method] = ['total' => 0, 'count' => 0];
    }
    $method_breakdown[$method]['total'] += $row['total'] ?? $row['daily_total'] ?? 0;
    $method_breakdown[$method]['count'] += $row['transaction_count'] ?? $row['count'] ?? 1;
}

// Get reconciliation history for selected date
$stmt = $db->prepare("SELECT * FROM daily_cash_reconciliation WHERE reconciliation_date = ?");
$stmt->execute([$selected_date]);
$reconciliation = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Reconciliation - MUSA Beauty Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .reconciliation-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .filters {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #555;
        }
        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 150px;
        }
        .btn {
            background-color: #8b5fbf;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #7a4fa3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .revenue-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .revenue-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
        }
        .revenue-card h3 {
            margin: 0 0 0.5rem 0;
            color: #555;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .revenue-card .amount {
            font-size: 2rem;
            font-weight: bold;
            color: #8b5fbf;
        }
        .revenue-card .details {
            font-size: 0.85rem;
            color: #999;
            margin-top: 0.5rem;
        }
        .method-breakdown {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .method-breakdown h3 {
            margin-top: 0;
        }
        .method-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        .method-row:last-child {
            border-bottom: none;
        }
        .method-name {
            font-weight: 500;
            color: #333;
        }
        .method-amount {
            color: #8b5fbf;
            font-weight: bold;
        }
        .method-count {
            color: #999;
            font-size: 0.9rem;
            margin-left: 1rem;
        }
        .reconciliation-form {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .reconciliation-form h3 {
            margin-top: 0;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #555;
        }
        .form-group input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group textarea {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
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
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .calculation-box {
            background: #f5f5f5;
            border-left: 4px solid #8b5fbf;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
        }
        .calculation-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .calculation-row strong {
            color: #333;
        }
        .discrepancy {
            font-size: 1.2rem;
            font-weight: bold;
            margin-top: 1rem;
        }
        .discrepancy.warning {
            color: #ff6b6b;
        }
        .discrepancy.ok {
            color: #51cf66;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="reconciliation-container">
        <h1>Daily Revenue Reconciliation</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <form method="GET" class="filters">
            <div class="filter-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" value="<?= $selected_date ?>" required>
            </div>

            <div class="filter-group">
                <label for="range">Period:</label>
                <select id="range" name="range">
                    <option value="day" <?= $date_range === 'day' ? 'selected' : '' ?>>Daily</option>
                    <option value="week" <?= $date_range === 'week' ? 'selected' : '' ?>>Weekly</option>
                    <option value="month" <?= $date_range === 'month' ? 'selected' : '' ?>>Monthly</option>
                </select>
            </div>

            <button type="submit" class="btn">Load Data</button>
        </form>

        <!-- Revenue Summary Cards -->
        <div class="revenue-summary">
            <div class="revenue-card">
                <h3>Total Revenue</h3>
                <div class="amount">KES <?= number_format($total_revenue) ?></div>
                <div class="details"><?= count($revenue_data) ?> transactions</div>
            </div>

            <?php foreach ($method_breakdown as $method => $data): ?>
                <div class="revenue-card">
                    <h3><?= ucfirst($method) ?> Payments</h3>
                    <div class="amount">KES <?= number_format($data['total']) ?></div>
                    <div class="details"><?= $data['count'] ?> transactions</div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Payment Method Breakdown -->
        <?php if (!empty($method_breakdown)): ?>
            <div class="method-breakdown">
                <h3>Payment Method Breakdown</h3>
                <?php foreach ($method_breakdown as $method => $data): ?>
                    <div class="method-row">
                        <span class="method-name"><?= ucfirst($method) ?></span>
                        <span class="method-amount">KES <?= number_format($data['total']) ?></span>
                        <span class="method-count">(<?= $data['count'] ?> transactions)</span>
                    </div>
                <?php endforeach; ?>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #8b5fbf;">
                    <div class="method-row">
                        <strong class="method-name">Total</strong>
                        <strong class="method-amount">KES <?= number_format($total_revenue) ?></strong>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Daily Cash Reconciliation Form -->
        <div class="reconciliation-form">
            <h3>Cash Reconciliation for <?= date('F d, Y', strtotime($selected_date)) ?></h3>

            <?php if ($reconciliation): ?>
                <div class="alert alert-success">
                    This day has already been reconciled. You can update it if needed.
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="reconcile_day">
                <input type="hidden" name="date" value="<?= $selected_date ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="opening_balance">Opening Balance (KES):</label>
                        <input type="number" id="opening_balance" name="opening_balance" 
                               value="<?= $reconciliation['opening_balance'] ?? 0 ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="cash_received">Cash Received (KES):</label>
                        <input type="number" id="cash_received" name="cash_received" 
                               value="<?= $reconciliation['cash_received'] ?? ($method_breakdown['cash']['total'] ?? 0) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="cash_withdrawn">Cash Withdrawn (KES):</label>
                        <input type="number" id="cash_withdrawn" name="cash_withdrawn" 
                               value="<?= $reconciliation['cash_withdrawn'] ?? 0 ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="closing_balance">Closing Balance (KES):</label>
                        <input type="number" id="closing_balance" name="closing_balance" 
                               value="<?= $reconciliation['closing_balance'] ?? 0 ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes"><?= htmlspecialchars($reconciliation['notes'] ?? '') ?></textarea>
                </div>

                <div class="calculation-box">
                    <div class="calculation-row">
                        <strong>Expected Balance:</strong>
                        <span>Opening Balance + Cash Received - Cash Withdrawn</span>
                    </div>
                    <div class="calculation-row">
                        <strong id="expected_value">Calculating...</strong>
                    </div>
                    
                    <div class="calculation-row">
                        <strong>Actual Closing Balance:</strong>
                        <span id="actual_value">Calculating...</span>
                    </div>

                    <div class="discrepancy ok" id="discrepancy_display">
                        Discrepancy: <span id="discrepancy_value">KES 0</span>
                    </div>
                </div>

                <button type="submit" class="btn" style="margin-top: 1rem;">Save Reconciliation</button>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const openingBalance = document.getElementById('opening_balance');
        const cashReceived = document.getElementById('cash_received');
        const cashWithdrawn = document.getElementById('cash_withdrawn');
        const closingBalance = document.getElementById('closing_balance');
        const expectedValue = document.getElementById('expected_value');
        const actualValue = document.getElementById('actual_value');
        const discrepancyValue = document.getElementById('discrepancy_value');
        const discrepancyDisplay = document.getElementById('discrepancy_display');

        function updateCalculations() {
            const opening = parseInt(openingBalance.value) || 0;
            const received = parseInt(cashReceived.value) || 0;
            const withdrawn = parseInt(cashWithdrawn.value) || 0;
            const actual = parseInt(closingBalance.value) || 0;

            const expected = opening + received - withdrawn;
            const discrepancy = actual - expected;

            expectedValue.textContent = `KES ${expected.toLocaleString()}`;
            actualValue.textContent = `KES ${actual.toLocaleString()}`;
            
            const discrepancyText = discrepancy === 0 
                ? 'Balanced ✓' 
                : (discrepancy > 0 ? 'Over' : 'Under') + ` KES ${Math.abs(discrepancy).toLocaleString()}`;
            
            discrepancyValue.textContent = discrepancyText;
            
            discrepancyDisplay.className = 'discrepancy ' + (discrepancy === 0 ? 'ok' : 'warning');
        }

        openingBalance.addEventListener('change', updateCalculations);
        cashReceived.addEventListener('change', updateCalculations);
        cashWithdrawn.addEventListener('change', updateCalculations);
        closingBalance.addEventListener('change', updateCalculations);

        // Initial calculation
        updateCalculations();
    </script>
</body>
</html>
