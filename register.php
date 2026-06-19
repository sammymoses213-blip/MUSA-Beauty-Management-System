<?php
require_once __DIR__ . '/includes/auth.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone = preg_replace('/[^0-9+]/', '', $_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['client', 'stylist'], true) ? $_POST['role'] : 'client';
    $specialization = sanitize($_POST['specialization'] ?? '');

    if ($name && $email && $phone && $password) {
        $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existing->execute([':email' => $email]);
        if ($existing->fetch()) {
            $message = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (:name, :email, :phone, :password, :role)');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':password' => $hash,
                ':role' => $role,
            ]);
            $userId = $pdo->lastInsertId();
            if ($role === 'stylist') {
                $stylistStmt = $pdo->prepare('INSERT INTO stylists (user_id, specialization) VALUES (:user_id, :specialization)');
                $stylistStmt->execute([
                    ':user_id' => $userId,
                    ':specialization' => $specialization,
                ]);
            }
            $_SESSION['user'] = [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'role' => $role,
            ];
            redirectDashboard();
        }
    } else {
        $message = 'Please fill in all required fields.';
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<section class="section">
    <div class="container">
        <div class="form-card card">
            <h2 class="section-title">Create your MUSA account</h2>
            <?php if ($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+254700000000" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Account type</label>
                    <select id="role" name="role" required>
                        <option value="client">Client</option>
                        <option value="stylist">Stylist</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="specialization">Stylist specialization (optional)</label>
                    <input type="text" id="specialization" name="specialization" placeholder="Hair, Makeup, Nails, etc.">
                </div>
                <button type="submit" class="primary-btn">Register</button>
            </form>
            <p style="margin-top:1rem;">Already a member? <a href="/login.php">Login here</a>.</p>
        </div>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
