<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/sms.php';

$now = new DateTimeImmutable('now');
$windowStart = $now->modify('+24 hours')->format('Y-m-d H:i:s');
$windowEnd = $now->modify('+25 hours')->format('Y-m-d H:i:s');

try {
    $query = 'SELECT a.id, a.appointment_date, s.name AS service_name, stylist.name AS stylist_name, client.phone AS client_phone
              FROM appointments a
              JOIN services s ON a.service_id = s.id
              JOIN users client ON a.client_id = client.id
              JOIN users stylist ON a.stylist_id = stylist.id
              WHERE a.status = "booked"
                AND a.reminder_sent = 0
                AND a.appointment_date BETWEEN :start AND :end';
    $stmt = $pdo->prepare($query);
    $stmt->execute([':start' => $windowStart, ':end' => $windowEnd]);
} catch (PDOException $e) {
    if (stripos($e->getMessage(), 'Unknown column') !== false) {
        $pdo->exec('ALTER TABLE appointments ADD COLUMN reminder_sent TINYINT(1) NOT NULL DEFAULT 0');
        $stmt = $pdo->prepare($query);
        $stmt->execute([':start' => $windowStart, ':end' => $windowEnd]);
    } else {
        throw $e;
    }
}

$appointments = $stmt->fetchAll();
$sent = 0;

foreach ($appointments as $appointment) {
    if (empty($appointment['client_phone'])) {
        continue;
    }

    $formattedDate = date('M j, Y H:i', strtotime($appointment['appointment_date']));
    $message = sprintf(
        'Reminder: your appointment for %s with %s is scheduled for %s. See you at MUSA Beauty!',
        $appointment['service_name'],
        $appointment['stylist_name'],
        $formattedDate
    );

    if (sendSMS($appointment['client_phone'], $message)) {
        $update = $pdo->prepare('UPDATE appointments SET reminder_sent = 1 WHERE id = :id');
        $update->execute([':id' => $appointment['id']]);
        $sent++;
    }
}

echo "Appointment reminder script completed. Sent {$sent} reminder(s).\n";
