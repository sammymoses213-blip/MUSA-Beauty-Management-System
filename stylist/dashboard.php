<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('stylist');

$stylistStmt = $pdo->prepare('SELECT s.user_id, s.specialization, u.name FROM stylists s JOIN users u ON s.user_id = u.id WHERE u.id = :user_id LIMIT 1');
$stylistStmt->execute([':user_id' => $_SESSION['user']['id']]);
$profile = $stylistStmt->fetch();

$appointmentsStmt = $pdo->prepare('SELECT a.*, s.name AS service_name, u.name AS client_name FROM appointments a JOIN services s ON a.service_id = s.id JOIN users u ON a.client_id = u.id WHERE a.stylist_id = :stylist_id AND a.status = "booked" ORDER BY a.appointment_date LIMIT 5');
$appointmentsStmt->execute([':stylist_id' => $profile['user_id']]);
$appointments = $appointmentsStmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="section section-light">
    <div class="container">
        <h1 class="section-title">Stylist Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($profile['name']); ?>. Update your schedule and view upcoming bookings here.</p>
        <div class="card-grid info-grid" style="margin-top:2rem;">
            <div class="report-card card"><h3>Specialization</h3><p><?php echo htmlspecialchars($profile['specialization'] ?: 'Beauty Specialist'); ?></p></div>
            <div class="report-card card"><h3>Next appointments</h3><p><?php echo count($appointments) ?: 'None yet'; ?></p></div>
            <div class="report-card card"><h3>Client focus</h3><p>Keep bookings consistent and update availability.</p></div>
        </div>
        <div class="card-grid services-grid" style="margin-top:2rem;">
            <a href="/stylist/schedule.php" class="feature-card card">Manage schedule</a>
            <a href="/stylist/appointments.php" class="feature-card card">View appointments</a>
        </div>
        <div class="section-head" style="margin-top:2rem;">
            <h2 class="section-title">Upcoming bookings</h2>
        </div>
        <div class="card table-card">
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Client</th><th>Service</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (count($appointments) === 0): ?>
                            <tr><td colspan="4">No booked appointments scheduled yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                <td><span class="badge badge-booked">Booked</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
