<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sms.php';
require_once __DIR__ . '/../includes/mpesa.php';
require_once __DIR__ . '/../includes/classes/Payment.php';
require_once __DIR__ . '/../includes/classes/PaymentProcessor.php';

requireRole('client');

$message = '';
$messageType = 'info'; // 'success', 'error', 'info'
$selectedServiceId = isset($_GET['service_id']) ? (int) $_GET['service_id'] : 0;
$selectedStylistId = isset($_GET['stylist_id']) ? (int) $_GET['stylist_id'] : 0;

// Use PDO instance from config
$services = $pdo->query('SELECT * FROM services WHERE is_active = 1 ORDER BY category, name')->fetchAll(PDO::FETCH_ASSOC);
$stylists = $pdo->query('SELECT u.id AS stylist_user_id, s.id AS stylist_id, u.name AS stylist_name, s.specialization FROM stylists s JOIN users u ON s.user_id = u.id WHERE u.role = "stylist" ORDER BY u.name')->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $selectedServiceId = $serviceId;
    $stylistId = (int) ($_POST['stylist_id'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $phone = trim($_POST['mpesa_phone'] ?? '');
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['appointment_time'] ?? '';
    $appointmentDate = $date && $time ? date('Y-m-d H:i:s', strtotime("$date $time")) : null;

    if ($serviceId && $stylistId && $appointmentDate) {
        $now = date('Y-m-d H:i:s');
        if ($appointmentDate <= $now) {
            $message = 'Please choose a future date and time.';
            $messageType = 'error';
        } else {
            // Check for double booking
            $check = $db->prepare('SELECT COUNT(*) as cnt FROM appointments WHERE stylist_id = ? AND appointment_date = ? AND status != "cancelled"');
            $check->execute([$stylistId, $appointmentDate]);
            $result = $check->fetch(PDO::FETCH_ASSOC);
            
            if ($result['cnt'] > 0) {
                $message = 'This stylist already has a booking at the chosen date and time. Please select another slot.';
                $messageType = 'error';
            } else {
                // Get service details
                $service = $db->prepare('SELECT price, name FROM services WHERE id = ?');
                $service->execute([$serviceId]);
                $serviceData = $service->fetch(PDO::FETCH_ASSOC);
                $amount = $serviceData['price'] ?? 0;

                // Create appointment first
                $stmt = $pdo->prepare(
                    'INSERT INTO appointments (client_id, stylist_id, service_id, appointment_date, status, payment_method, payment_status, amount_paid) VALUES (?, ?, ?, ?, "booked", ?, "unpaid", 0)'
                );
                $stmt->execute([
                    $_SESSION['user']['id'],
                    $stylistId,
                    $serviceId,
                    $appointmentDate,
                    $paymentMethod
                ]);

                $appointmentId = (int) $pdo->lastInsertId();

                // Now process payment using new PaymentProcessor
                $processor = new PaymentProcessor($pdo);
                $payment_result = null;

                if ($paymentMethod === 'mpesa') {
                    // Process M-Pesa payment
                    if (!$phone) {
                        $message = 'Phone number required for M-Pesa payment.';
                        $messageType = 'error';
                    } else {
                        $payment_result = $processor->processPayment(
                            $appointmentId,
                            $amount,
                            'mpesa',
                            ['phone' => $phone, 'user_id' => $_SESSION['user']['id']]
                        );
                    }
                } elseif ($paymentMethod === 'card') {
                    // Process Card payment
                    $payment_result = $processor->processPayment(
                        $appointmentId,
                        $amount,
                        'card',
                        ['processor' => 'stripe', 'user_id' => $_SESSION['user']['id']]
                    );
                } else {
                    // Cash payment - mark as pending until confirmed at salon
                    $payment_result = $processor->processPayment(
                        $appointmentId,
                        $amount,
                        'cash',
                        ['user_id' => $_SESSION['user']['id']]
                    );
                }

                // Handle payment result
                if ($payment_result && $payment_result['ok']) {
                    // Get payment ID for reference
                        $payment = new Payment($pdo);
                    $payment_details = $payment->getPaymentByAppointment($appointmentId);
                    
                    if ($paymentMethod === 'mpesa') {
                        $message = 'Appointment booked! M-Pesa payment request has been sent to ' . $phone . '. Please complete the payment prompt. Your booking will be confirmed once payment is received.';
                        $messageType = 'success';
                    } elseif ($paymentMethod === 'card') {
                        $message = 'Appointment booked! You will be redirected to complete card payment.';
                        $messageType = 'success';
                    } else {
                        $message = 'Appointment booked successfully! Please pay KES ' . number_format($amount) . ' at the salon counter.';
                        $messageType = 'success';
                    }
                } else {
                    $message = 'Appointment created but payment processing failed. Please try again: ' . ($payment_result['message'] ?? 'Unknown error');
                    $messageType = 'error';
                }

                // Send notifications if booking succeeded
                if ($messageType === 'success') {
                    $detailStmt = $pdo->prepare(
                        'SELECT client.phone AS client_phone, client.name AS client_name, stylist.phone AS stylist_phone, s.name AS service_name, stylist.name AS stylist_name FROM users client JOIN services s ON s.id = ? JOIN users stylist ON stylist.id = ? WHERE client.id = ?'
                    );
                    $detailStmt->execute([$serviceId, $stylistId, $_SESSION['user']['id']]);
                    $details = $detailStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($details) {
                        $formattedDate = date('M j, Y H:i', strtotime($appointmentDate));
                        
                        if (!empty($details['client_phone'])) {
                            sendSMS($details['client_phone'], "Your appointment for {$details['service_name']} with {$details['stylist_name']} is confirmed for {$formattedDate}. Amount: KES " . number_format($amount) . ". See you at MUSA Beauty!");
                        }
                        if (!empty($details['stylist_phone'])) {
                            sendSMS($details['stylist_phone'], "New appointment: {$details['service_name']} for {$details['client_name']} on {$formattedDate}. Amount: KES " . number_format($amount) . ".");
                        }
                    }
                }
            }
        }
    } else {
        $message = 'Please select a service, stylist, date, and time.';
        $messageType = 'error';
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="section">
    <div class="container">
        <h1 class="section-title">Book an Appointment</h1>
        <?php if ($message): ?>
            <div class="alert" style="background-color: <?= 
                $messageType === 'success' ? '#d4edda' : 
                ($messageType === 'error' ? '#f8d7da' : '#d1ecf1') 
            ?>; color: <?= 
                $messageType === 'success' ? '#155724' : 
                ($messageType === 'error' ? '#721c24' : '#0c5460') 
            ?>; border: 1px solid <?= 
                $messageType === 'success' ? '#c3e6cb' : 
                ($messageType === 'error' ? '#f5c6cb' : '#bee5eb') 
            ?>; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <div class="card form-card">
            <form method="post">
                <div class="form-group">
                    <label for="service_id">Choose a service</label>
                    <select id="service_id" name="service_id" required>
                        <option value="">Select service</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo $service['id']; ?>" <?php echo $selectedServiceId === (int) $service['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($service['name']); ?> — KES <?php echo number_format($service['price']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="stylist_id">Choose a stylist</label>
                    <select id="stylist_id" name="stylist_id" required>
                        <option value="">Select stylist</option>
                        <?php foreach ($stylists as $stylist): ?>
                            <option value="<?php echo $stylist['stylist_user_id']; ?>" <?php echo $selectedStylistId === (int) $stylist['stylist_user_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($stylist['stylist_name']); ?> — <?php echo htmlspecialchars($stylist['specialization'] ?: 'Beauty specialist'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="appointment_date">Appointment date</label>
                    <input type="date" id="appointment_date" name="appointment_date" required>
                </div>
                <div class="form-group">
                    <label for="appointment_time">Appointment time</label>
                    <input type="time" id="appointment_time" name="appointment_time" required>
                </div>
                <div class="form-group">
                    <label for="payment_method">Payment method</label>
                    <select id="payment_method" name="payment_method" onchange="togglePaymentFields()">
                        <option value="cash">Cash at salon</option>
                        <option value="mpesa">M-Pesa (Mobile Money)</option>
                        <option value="card">Card Payment (Stripe)</option>
                    </select>
                </div>
                <div class="form-group" id="mpesaPhoneGroup" style="display: none;">
                    <label for="mpesa_phone">M-Pesa phone number</label>
                    <input type="tel" id="mpesa_phone" name="mpesa_phone" placeholder="254712345678">
                    <small style="color: #999; display: block; margin-top: 0.5rem;">Enter your phone number in format: 254XXXXXXXXX</small>
                </div>
                <button type="submit" class="primary-btn">Confirm booking</button>
            </form>
        </div>
    </div>
</section>
<script>
    function togglePaymentFields() {
        const paymentMethod = document.getElementById('payment_method').value;
        const mpesaGroup = document.getElementById('mpesaPhoneGroup');
        
        if (paymentMethod === 'mpesa') {
            mpesaGroup.style.display = 'block';
            document.getElementById('mpesa_phone').required = true;
        } else {
            mpesaGroup.style.display = 'none';
            document.getElementById('mpesa_phone').required = false;
        }
    }

    // Initial call
    document.addEventListener('DOMContentLoaded', togglePaymentFields);
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
