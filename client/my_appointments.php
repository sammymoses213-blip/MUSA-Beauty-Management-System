<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sms.php';
requireRole('client');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_id'])) {
        $cancelId = (int) $_POST['cancel_id'];
        $detailStmt = $pdo->prepare('SELECT a.appointment_date, s.name AS service_name, client.name AS client_name, stylist.name AS stylist_name, client.phone AS client_phone, stylist.phone AS stylist_phone FROM appointments a JOIN services s ON a.service_id = s.id JOIN users client ON a.client_id = client.id JOIN users stylist ON a.stylist_id = stylist.id WHERE a.id = :id AND a.client_id = :client_id LIMIT 1');
        $detailStmt->execute([':id' => $cancelId, ':client_id' => $_SESSION['user']['id']]);
        $details = $detailStmt->fetch();

        if ($details) {
            $stmt = $pdo->prepare('UPDATE appointments SET status = "cancelled" WHERE id = :id AND client_id = :client_id');
            $stmt->execute([':id' => $cancelId, ':client_id' => $_SESSION['user']['id']]);
            $formattedDate = date('M j, Y H:i', strtotime($details['appointment_date']));

            if (!empty($details['client_phone'])) {
                sendSMS($details['client_phone'], "Your appointment for {$details['service_name']} with {$details['stylist_name']} on {$formattedDate} has been cancelled. Contact MUSA Beauty if you want to book another slot.");
            }
            if (!empty($details['stylist_phone'])) {
                sendSMS($details['stylist_phone'], "Appointment cancelled: {$details['service_name']} for {$details['client_name']} on {$formattedDate}.");
            }

            $message = 'Appointment cancelled successfully. A cancellation SMS has been sent when the phone number is available.';
        }
    }
    if (isset($_POST['reschedule_id'])) {
        $rescheduleId = (int) $_POST['reschedule_id'];
        $date = $_POST['new_date'] ?? '';
        $time = $_POST['new_time'] ?? '';
        $newDateTime = $date && $time ? date('Y-m-d H:i:s', strtotime("$date $time")) : null;

        if ($newDateTime) {
            $checkStmt = $pdo->prepare('SELECT a.stylist_id, s.name AS service_name, client.name AS client_name, stylist.name AS stylist_name, client.phone AS client_phone, stylist.phone AS stylist_phone FROM appointments a JOIN services s ON a.service_id = s.id JOIN users client ON a.client_id = client.id JOIN users stylist ON a.stylist_id = stylist.id WHERE a.id = :id AND a.client_id = :client_id LIMIT 1');
            $checkStmt->execute([':id' => $rescheduleId, ':client_id' => $_SESSION['user']['id']]);
            $appointment = $checkStmt->fetch();
            if ($appointment) {
                $busy = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE stylist_id = :stylist_id AND appointment_date = :appointment_date AND status != "cancelled" AND id != :id');
                $busy->execute([':stylist_id' => $appointment['stylist_id'], ':appointment_date' => $newDateTime, ':id' => $rescheduleId]);
                if ($busy->fetchColumn() > 0) {
                    $message = 'That time is unavailable. Choose another slot.';
                } else {
                    $updateStmt = $pdo->prepare('UPDATE appointments SET appointment_date = :appointment_date, reminder_sent = 0 WHERE id = :id');
                    $updateStmt->execute([':appointment_date' => $newDateTime, ':id' => $rescheduleId]);
                    $formattedDate = date('M j, Y H:i', strtotime($newDateTime));

                    if (!empty($appointment['client_phone'])) {
                        sendSMS($appointment['client_phone'], "Your appointment for {$appointment['service_name']} has been rescheduled to {$formattedDate}. See you at MUSA Beauty.");
                    }
                    if (!empty($appointment['stylist_phone'])) {
                        sendSMS($appointment['stylist_phone'], "Appointment rescheduled: {$appointment['service_name']} for {$appointment['client_name']} is now set for {$formattedDate}.");
                    }

                    $message = 'Appointment rescheduled. A confirmation SMS has been sent when the phone number is available.';
                }
            }
        } else {
            $message = 'Please choose both a new date and time.';
        }
    }
}

$appointments = $pdo->prepare('SELECT a.*, s.name AS service_name, u.name AS stylist_name FROM appointments a JOIN services s ON a.service_id = s.id JOIN users u ON a.stylist_id = u.id WHERE a.client_id = :client_id ORDER BY a.appointment_date DESC');
$appointments->execute([':client_id' => $_SESSION['user']['id']]);
$appointments = $appointments->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="section section-light">
    <div class="container">
        <h1 class="section-title">My Appointments</h1>
        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <div class="card table-card">
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Service</th><th>Stylist</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (count($appointments) === 0): ?>
                            <tr><td colspan="5">No appointment history yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['stylist_name']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                <td><span class="badge badge-<?php echo htmlspecialchars($appointment['status']); ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                                <td>
                                    <?php if ($appointment['status'] === 'booked'): ?>
                                        <form method="post" style="display:inline-block; margin-right:.5rem;">
                                            <input type="hidden" name="cancel_id" value="<?php echo $appointment['id']; ?>">
                                            <button type="submit" class="secondary-btn">Cancel</button>
                                        </form>
                                        <details style="display:inline-block;">
                                            <summary class="secondary-btn" style="border:none; padding:.75rem 1rem;">Reschedule</summary>
                                            <form method="post" style="margin-top:.75rem; display:grid; gap:.75rem;">
                                                <input type="hidden" name="reschedule_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="date" name="new_date" required>
                                                <input type="time" name="new_time" required>
                                                <button type="submit" class="secondary-btn">Save</button>
                                            </form>
                                        </details>
                                    <?php else: ?>
                                        <span style="color: rgba(74,44,42,0.65);">No actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
