<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/service_functions.php';
requireRole('client');

$upcomingStmt = $pdo->prepare('SELECT a.*, s.name AS service_name, u.name AS stylist_name FROM appointments a JOIN services s ON a.service_id = s.id JOIN users u ON a.stylist_id = u.id WHERE a.client_id = :client_id AND a.status = "booked" ORDER BY a.appointment_date LIMIT 5');
$upcomingStmt->execute([':client_id' => $_SESSION['user']['id']]);
$upcomingAppointments = $upcomingStmt->fetchAll();
$serviceCount = $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
$stylistCount = $pdo->query('SELECT COUNT(*) FROM stylists')->fetchColumn();

// Get recommended stylists
$recommendedStylists = getTopStylistsByClientBookings($pdo, $_SESSION['user']['id'], 3);
if (empty($recommendedStylists)) {
    $recommendedStylists = getTopRatedStylists($pdo, 3);
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="section section-light">
    <div class="container">
        <h1 class="section-title">Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>.</h1>
        <p>Find your next service, check upcoming bookings, and leave feedback for your stylist.</p>
        <div class="info-grid card-grid" style="margin-top:2rem;">
            <div class="report-card card"><h3>Available services</h3><p><?php echo $serviceCount; ?></p></div>
            <div class="report-card card"><h3>Stylists on board</h3><p><?php echo $stylistCount; ?></p></div>
            <div class="report-card card"><h3>Next appointment</h3><p><?php echo count($upcomingAppointments) ? date('M j, Y H:i', strtotime($upcomingAppointments[0]['appointment_date'])) : 'No upcoming booking'; ?></p></div>
        </div>
        <div class="card-grid services-grid" style="margin-top:2rem;">
            <a href="/client/book_appointment.php" class="feature-card card">Book Appointment</a>
            <a href="/client/my_appointments.php" class="feature-card card">My Appointments</a>
            <a href="/client/reviews.php" class="feature-card card">Reviews</a>
        </div>
        <div class="section-head" style="margin-top:2rem;">
            <h2 class="section-title">Upcoming bookings</h2>
        </div>
        <div class="card table-card">
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Service</th><th>Stylist</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (count($upcomingAppointments) === 0): ?>
                            <tr><td colspan="4">You have no upcoming appointments yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($upcomingAppointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['stylist_name']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                <td><span class="badge badge-<?php echo htmlspecialchars($appointment['status']); ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="section-head" style="margin-top:2rem;">
            <h2 class="section-title">Recommended for You</h2>
        </div>
        <div class="card-grid">
            <?php if (count($recommendedStylists) === 0): ?>
                <div class="card">
                    <p>No recommendations available yet. Book your first appointment to get personalized suggestions!</p>
                </div>
            <?php else: ?>
                <?php foreach ($recommendedStylists as $stylist): ?>
                    <div class="card stylist-card">
                        <h3><?php echo htmlspecialchars($stylist['name']); ?></h3>
                        <p><strong>Specialty:</strong> <?php echo htmlspecialchars($stylist['specialization']); ?></p>
                        <p><strong>Average Rating:</strong> <?php echo number_format($stylist['avg_rating'], 1); ?>/5</p>
                        <?php if (isset($stylist['booking_count'])): ?>
                            <p><strong>Times Booked:</strong> <?php echo $stylist['booking_count']; ?></p>
                        <?php endif; ?>
                        <a href="/client/book_appointment.php?stylist_id=<?php echo $stylist['id']; ?>" class="secondary-btn">Book with <?php echo htmlspecialchars($stylist['name']); ?></a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
