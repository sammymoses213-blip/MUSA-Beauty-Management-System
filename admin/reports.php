<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$totalAppointments = $pdo->query('SELECT COUNT(*) FROM appointments')->fetchColumn();
$completed = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed'")->fetchColumn();
$booked = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'booked'")->fetchColumn();
$cancelled = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'cancelled'")->fetchColumn();
$recent = $pdo->prepare('SELECT a.*, u.name AS client_name, us.name AS stylist_name, s.name AS service_name FROM appointments a JOIN users u ON a.client_id = u.id JOIN users us ON a.stylist_id = us.id JOIN services s ON a.service_id = s.id ORDER BY a.appointment_date DESC LIMIT 10');
$recent->execute();
$recentAppointments = $recent->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="section section-light">
    <div class="container">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
            <h1 class="section-title" style="margin:0;">Reports</h1>
            <div style="display:flex; gap:0.5rem;">
                <button type="button" class="primary-btn" onclick="window.print();">Print Report</button>
                <a href="/admin/download_reports.php" class="primary-btn" style="display:inline-block;">Download All Reports</a>
            </div>
        </div>
        <div class="card-grid info-grid">
            <div class="report-card card"><h3>Total appointments</h3><p><?php echo $totalAppointments; ?></p></div>
            <div class="report-card card"><h3>Completed</h3><p><?php echo $completed; ?></p></div>
            <div class="report-card card"><h3>Upcoming</h3><p><?php echo $booked; ?></p></div>
            <div class="report-card card"><h3>Cancelled</h3><p><?php echo $cancelled; ?></p></div>
        </div>
        <div class="section-head" style="margin-top:2rem;">
            <h2 class="section-title">Recent appointments</h2>
        </div>
        <div class="card table-card">
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Client</th><th>Stylist</th><th>Service</th><th>Date</th><th>Status</th></tr></thead>
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
