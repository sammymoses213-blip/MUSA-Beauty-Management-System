<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$stats = [];
$stats['clients'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$stats['stylists'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'stylist'")->fetchColumn();
$stats['services'] = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$stats['appointments'] = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$recentStmt = $pdo->prepare("SELECT a.*, u.name AS client_name, us.name AS stylist_name, s.name AS service_name FROM appointments a JOIN users u ON a.client_id = u.id JOIN users us ON a.stylist_id = us.id JOIN services s ON a.service_id = s.id ORDER BY a.appointment_date DESC LIMIT 5");
$recentStmt->execute();
$recentAppointments = $recentStmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="section section-light">
    <div class="container">
        <h1 class="section-title">Admin Dashboard</h1>
        <div class="card-grid info-grid">
            <div class="report-card card"><h3>Clients</h3><p><?php echo $stats['clients']; ?></p></div>
            <div class="report-card card"><h3>Stylists</h3><p><?php echo $stats['stylists']; ?></p></div>
            <div class="report-card card"><h3>Services</h3><p><?php echo $stats['services']; ?></p></div>
            <div class="report-card card"><h3>Appointments</h3><p><?php echo $stats['appointments']; ?></p></div>
        </div>
        <div class="section-head" style="margin-top:2rem;">
            <h2 class="section-title">Quick links</h2>
        </div>
        <div class="card-grid services-grid">
            <a href="/admin/manage_users.php" class="feature-card card">Manage Users</a>
            <a href="/admin/manage_services.php" class="feature-card card">Manage Services</a>
            <a href="/admin/reports.php" class="feature-card card">View Reports</a>
        </div>
        <div class="section-head" style="margin-top:2rem;">
            <h2 class="section-title">Latest appointments</h2>
        </div>
        <div class="card table-card">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr><th>Client</th><th>Stylist</th><th>Service</th><th>Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAppointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['stylist_name']); ?></td>
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
