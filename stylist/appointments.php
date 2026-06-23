<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('stylist');

$message = '';
$profileStmt = $pdo->prepare('SELECT s.user_id FROM stylists s JOIN users u ON s.user_id = u.id WHERE u.id = :user_id LIMIT 1');
$profileStmt->execute([':user_id' => $_SESSION['user']['id']]);
$stylistProfile = $profileStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = isset($_POST['appointment_id']) ? (int) $_POST['appointment_id'] : 0;
    $newStatus = $_POST['status'] ?? '';
    $newDate = trim($_POST['appointment_date'] ?? '');

    if ($appointmentId <= 0 || !in_array($newStatus, ['booked', 'completed', 'cancelled'], true)) {
        $message = 'Invalid appointment details provided.';
    } else {
        $checkStmt = $pdo->prepare('SELECT * FROM appointments WHERE id = :id AND stylist_id = :stylist_id LIMIT 1');
        $checkStmt->execute([':id' => $appointmentId, ':stylist_id' => $stylistProfile['user_id']]);
        $appointment = $checkStmt->fetch();

        if (!$appointment) {
            $message = 'Appointment not found or you are not assigned to this booking.';
        } else {
            $updateFields = ['status = :status'];
            $updateParams = [':status' => $newStatus, ':id' => $appointmentId];

            if ($newDate !== '') {
                $dateTime = date_create($newDate);
                if (!$dateTime) {
                    $message = 'The appointment date is invalid.';
                } else {
                    $formattedDate = $dateTime->format('Y-m-d H:i:s');
                    $conflictStmt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE stylist_id = :stylist_id AND appointment_date = :appointment_date AND status != "cancelled" AND id != :id');
                    $conflictStmt->execute([
                        ':stylist_id' => $stylistProfile['user_id'],
                        ':appointment_date' => $formattedDate,
                        ':id' => $appointmentId,
                    ]);
                    if ($conflictStmt->fetchColumn() > 0) {
                        $message = 'The stylist already has another appointment at that time.';
                    } else {
                        $updateFields[] = 'appointment_date = :appointment_date';
                        $updateFields[] = 'reminder_sent = 0';
                        $updateParams[':appointment_date'] = $formattedDate;
                    }
                }
            }

            if ($message === '') {
                $pdo->prepare('UPDATE appointments SET ' . implode(', ', $updateFields) . ' WHERE id = :id')
                    ->execute($updateParams);
                $message = 'Appointment updated successfully.';
            }
        }
    }
}

$appointmentsStmt = $pdo->prepare('SELECT a.*, s.name AS service_name, u.name AS client_name FROM appointments a JOIN services s ON a.service_id = s.id JOIN users u ON a.client_id = u.id WHERE a.stylist_id = :stylist_id ORDER BY a.appointment_date DESC');
$appointmentsStmt->execute([':stylist_id' => $stylistProfile['user_id']]);
$appointments = $appointmentsStmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="section">
    <div class="container">
        <h1 class="section-title">Assigned Appointments</h1>
        <div class="card table-card">
            <div class="table-wrapper">
                <?php if ($message): ?>
                    <div class="alert"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <table class="table">
                    <thead><tr><th>Client</th><th>Service</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (count($appointments) === 0): ?>
                            <tr><td colspan="5">No appointment records found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                <td><span class="badge badge-<?php echo htmlspecialchars($appointment['status']); ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                                <td>
                                    <form method="post" style="display:inline-block; margin:0;">
                                        <input type="hidden" name="appointment_id" value="<?php echo (int)$appointment['id']; ?>">
                                        <input type="hidden" name="appointment_date" value="<?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($appointment['appointment_date']))); ?>">
                                        <button type="submit" name="status" value="completed" class="secondary-btn">Mark Completed</button>
                                        <button type="submit" name="status" value="cancelled" class="danger-btn">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="5">
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="appointment_id" value="<?php echo (int)$appointment['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($appointment['status']); ?>">
                                        <label>
                                            Reschedule:
                                            <input type="datetime-local" name="appointment_date" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($appointment['appointment_date']))); ?>">
                                        </label>
                                        <button type="submit" class="secondary-btn">Update date</button>
                                    </form>
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
