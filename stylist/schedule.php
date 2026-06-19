<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('stylist');

$message = '';
$profileStmt = $pdo->prepare('SELECT s.*, u.id AS user_id FROM stylists s JOIN users u ON s.user_id = u.id WHERE u.id = :user_id LIMIT 1');
$profileStmt->execute([':user_id' => $_SESSION['user']['id']]);
$profile = $profileStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $specialization = sanitize($_POST['specialization'] ?? '');
    if ($specialization) {
        $update = $pdo->prepare('UPDATE stylists SET specialization = :specialization WHERE id = :id');
        $update->execute([':specialization' => $specialization, ':id' => $profile['id']]);
        $message = 'Availability and specialization updated.';
        $profile['specialization'] = $specialization;
    } else {
        $message = 'Please enter your availability description.';
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="section section-light">
    <div class="container">
        <h1 class="section-title">Manage Your Schedule</h1>
        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <div class="card form-card">
            <p>Update your availability notes and specialist focus. Clients will see your latest details when booking.</p>
            <form method="post">
                <div class="form-group">
                    <label for="specialization">Specialization or schedule note</label>
                    <textarea id="specialization" name="specialization" required><?php echo htmlspecialchars($profile['specialization'] ?: 'Available for appointments Tuesday to Saturday'); ?></textarea>
                </div>
                <button type="submit" class="primary-btn">Save schedule details</button>
            </form>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
