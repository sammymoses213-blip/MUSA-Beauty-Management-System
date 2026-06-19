<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('stylist');

$profileStmt = $pdo->prepare('SELECT s.id FROM stylists s JOIN users u ON s.user_id = u.id WHERE u.id = :user_id LIMIT 1');
$profileStmt->execute([':user_id' => $_SESSION['user']['id']]);
$stylistProfile = $profileStmt->fetch();

$appointmentsStmt = $pdo->prepare('SELECT a.*, s.name AS service_name, u.name AS client_name FROM appointments a JOIN services s ON a.service_id = s.id JOIN users u ON a.client_id = u.id WHERE a.stylist_id = :stylist_id ORDER BY a.appointment_date DESC');
$appointmentsStmt->execute([':stylist_id' => $stylistProfile['id']]);
$appointments = $appointmentsStmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="section">
    <div class="container">
        <h1 class="section-title">Assigned Appointments</h1>
        <div class="card table-card">
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Client</th><th>Service</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (count($appointments) === 0): ?>
                            <tr><td colspan="4">No appointment records found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                <td><span class="badge badge-<?php echo htmlspecialchars($appointment['status']); ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
