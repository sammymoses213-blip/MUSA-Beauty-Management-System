<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

// Prepare report data
$filesToInclude = [];

// Summary counts
$summary = [];
$summary['total_appointments'] = $pdo->query('SELECT COUNT(*) FROM appointments')->fetchColumn();
$summary['completed'] = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed'")->fetchColumn();
$summary['booked'] = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'booked'")->fetchColumn();
$summary['cancelled'] = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'cancelled'")->fetchColumn();

// Generate CSV exports
function array_to_csv_string($rows, $headers = []) {
    $fh = fopen('php://temp', 'r+');
    if (!empty($headers)) {
        fputcsv($fh, $headers);
    }
    foreach ($rows as $r) {
        fputcsv($fh, $r);
    }
    rewind($fh);
    $contents = stream_get_contents($fh);
    fclose($fh);
    return $contents;
}

$users = $pdo->query('SELECT id, name, email, role FROM users')->fetchAll();
$userRows = array_map(function($u){ return [$u['id'],$u['name'],$u['email'],$u['role']]; }, $users);
$services = $pdo->query('SELECT id, name, price FROM services')->fetchAll();
$serviceRows = array_map(function($s){ return [$s['id'],$s['name'],$s['price']]; }, $services);
$appointments = $pdo->query('SELECT id, client_id, stylist_id, service_id, appointment_date, status FROM appointments')->fetchAll();
$appointmentRows = array_map(function($a){ return [$a['id'],$a['client_id'],$a['stylist_id'],$a['service_id'],$a['appointment_date'],$a['status']]; }, $appointments);

$csvUsers = array_to_csv_string($userRows, ['id','name','email','role']);
$csvServices = array_to_csv_string($serviceRows, ['id','name','price']);
$csvAppointments = array_to_csv_string($appointmentRows, ['id','client_id','stylist_id','service_id','appointment_date','status']);

$summaryText = "MUSA Beauty - Reports Summary\nGenerated: " . date('c') . "\n\n";
foreach ($summary as $k => $v) { $summaryText .= strtoupper($k) . ": " . $v . "\n"; }

// Fall back if ZipArchive is not available
if (!class_exists('ZipArchive')) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="musa-reports-' . date('Ymd-His') . '.txt"');
    echo $summaryText;
    echo "\n--- USERS ---\n";
    echo $csvUsers;
    echo "\n--- SERVICES ---\n";
    echo $csvServices;
    echo "\n--- APPOINTMENTS ---\n";
    echo $csvAppointments;
    exit;
}

// Create ZIP in temp file
$zip = new ZipArchive();
$tmpZip = tempnam(sys_get_temp_dir(), 'musa_reports_');
if ($zip->open($tmpZip, ZipArchive::OVERWRITE) !== true) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Failed to create archive';
    exit;
}

$zip->addFromString('summary.txt', $summaryText);
$zip->addFromString('users.csv', $csvUsers);
$zip->addFromString('services.csv', $csvServices);
$zip->addFromString('appointments.csv', $csvAppointments);

// Include migrations SQL files if present
$migrationsDir = __DIR__ . '/../migrations';
if (is_dir($migrationsDir)) {
    $migrations = glob($migrationsDir . '/*.sql');
    foreach ($migrations as $m) {
        $zip->addFile($m, 'migrations/' . basename($m));
    }
}

// Include logs if present (limit to reasonable size)
$logsDir = __DIR__ . '/../logs';
if (is_dir($logsDir)) {
    $logFiles = glob($logsDir . '/*');
    foreach ($logFiles as $lf) {
        if (is_file($lf)) {
            $zip->addFile($lf, 'logs/' . basename($lf));
        }
    }
}

$zip->close();

// Stream ZIP to client
$filename = 'musa-reports-' . date('Ymd-His') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpZip));
readfile($tmpZip);
@unlink($tmpZip);
exit;

?>
