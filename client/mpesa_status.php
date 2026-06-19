<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$appointments = $pdo->prepare('SELECT a.id, a.payment_status, a.payment_method, a.amount_paid, a.mpesa_checkout_request_id, a.mpesa_receipt_number, s.name AS service_name FROM appointments a JOIN services s ON s.id = a.service_id WHERE a.client_id = :client_id ORDER BY a.id DESC');
$appointments->execute([':client_id' => $_SESSION['user']['id']]);
$appointments = $appointments->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<section class="section">
  <div class="container">
    <h1 class="section-title">MPesa Payment Status</h1>
    <div class="card form-card">
      <p>Track MPesa payment confirmations for your salon bookings.</p>
      <table class="table">
        <thead>
          <tr><th>Service</th><th>Method</th><th>Status</th><th>Amount</th><th>Receipt</th></tr>
        </thead>
        <tbody>
          <?php foreach ($appointments as $appointment): ?>
            <tr>
              <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
              <td><?php echo htmlspecialchars($appointment['payment_method']); ?></td>
              <td><?php echo htmlspecialchars($appointment['payment_status']); ?></td>
              <td>KES <?php echo number_format((int) $appointment['amount_paid']); ?></td>
              <td><?php echo htmlspecialchars($appointment['mpesa_receipt_number'] ?: 'Pending'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
