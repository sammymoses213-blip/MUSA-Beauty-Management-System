<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $deleteId = (int) $_POST['delete_id'];
        $pdo->prepare('DELETE FROM appointments WHERE client_id = :id OR stylist_id = :id')->execute([':id' => $deleteId]);
        $pdo->prepare('DELETE FROM reviews WHERE client_id = :id OR stylist_id = :id')->execute([':id' => $deleteId]);
        $pdo->prepare('DELETE FROM stylists WHERE user_id = :id')->execute([':id' => $deleteId]);
        $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $deleteId]);
    }
    if (isset($_POST['role_id']) && isset($_POST['new_role'])) {
        $userId = (int) $_POST['role_id'];
        $newRole = $_POST['new_role'] === 'stylist' ? 'stylist' : 'client';
        $pdo->prepare('UPDATE users SET role = :role WHERE id = :id')->execute([':role' => $newRole, ':id' => $userId]);
        if ($newRole === 'stylist') {
            $checkStylist = $pdo->prepare('SELECT id FROM stylists WHERE user_id = :id LIMIT 1');
            $checkStylist->execute([':id' => $userId]);
            if (!$checkStylist->fetch()) {
                $pdo->prepare('INSERT INTO stylists (user_id, specialization) VALUES (:user_id, :specialization)')->execute([':user_id' => $userId, ':specialization' => 'General beauty']);
            }
        }
    }
}

$users = $pdo->query('SELECT u.id, u.name, u.email, u.role, s.specialization FROM users u LEFT JOIN stylists s ON u.id = s.user_id ORDER BY u.role, u.name')->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="section section-light">
    <div class="container">
        <h1 class="section-title">Manage Users</h1>
        <div class="card table-card">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Role</th><th>Specialization</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $userRow): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($userRow['name']); ?></td>
                                <td><?php echo htmlspecialchars($userRow['email']); ?></td>
                                <td><?php echo htmlspecialchars($userRow['role']); ?></td>
                                <td><?php echo htmlspecialchars($userRow['specialization'] ?: '-'); ?></td>
                                <td>
                                    <form method="post" style="display:inline-flex; gap:.5rem;">
                                        <input type="hidden" name="delete_id" value="<?php echo $userRow['id']; ?>">
                                        <button type="submit" class="secondary-btn">Delete</button>
                                    </form>
                                    <form method="post" style="display:inline-flex; gap:.5rem;">
                                        <input type="hidden" name="role_id" value="<?php echo $userRow['id']; ?>">
                                        <select name="new_role" style="padding:.5rem;border-radius:12px;">
                                            <option value="client" <?php echo $userRow['role'] === 'client' ? 'selected' : ''; ?>>Client</option>
                                            <option value="stylist" <?php echo $userRow['role'] === 'stylist' ? 'selected' : ''; ?>>Stylist</option>
                                        </select>
                                        <button type="submit" class="secondary-btn">Update</button>
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
