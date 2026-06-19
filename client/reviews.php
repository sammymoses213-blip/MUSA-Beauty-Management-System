<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

$message = '';
$stylists = $pdo->prepare('SELECT s.id, u.name AS stylist_name FROM stylists s JOIN users u ON s.user_id = u.id ORDER BY u.name');
$stylists->execute();
$stylists = $stylists->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stylistId = (int) ($_POST['stylist_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = sanitize($_POST['comment'] ?? '');
    if ($stylistId && $rating >= 1 && $rating <= 5 && $comment) {
        $stmt = $pdo->prepare('INSERT INTO reviews (client_id, stylist_id, rating, comment) VALUES (:client_id, :stylist_id, :rating, :comment)');
        $stmt->execute([
            ':client_id' => $_SESSION['user']['id'],
            ':stylist_id' => $stylistId,
            ':rating' => $rating,
            ':comment' => $comment,
        ]);
        $message = 'Review submitted successfully.';
    } else {
        $message = 'Please select a stylist, choose a rating, and write a comment.';
    }
}

$reviews = $pdo->prepare('SELECT r.*, u.name AS client_name, us.name AS stylist_name FROM reviews r JOIN users u ON r.client_id = u.id JOIN users us ON r.stylist_id = us.id ORDER BY r.id DESC');
$reviews->execute();
$reviews = $reviews->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="section">
    <div class="container">
        <h1 class="section-title">Reviews</h1>
        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <div class="card form-card">
            <form method="post">
                <div class="form-group">
                    <label for="stylist_id">Choose stylist</label>
                    <select id="stylist_id" name="stylist_id" required>
                        <option value="">Select stylist</option>
                        <?php foreach ($stylists as $stylist): ?>
                            <option value="<?php echo $stylist['id']; ?>"><?php echo htmlspecialchars($stylist['stylist_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="rating">Rating</label>
                    <select id="rating" name="rating" required>
                        <option value="">Select rating</option>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?php echo $i; ?>"><?php echo str_repeat('★', $i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="comment">Comment</label>
                    <textarea id="comment" name="comment" required></textarea>
                </div>
                <button type="submit" class="primary-btn">Submit review</button>
            </form>
        </div>
        <div class="section-head" style="margin-top:2rem;">
            <h2 class="section-title">Latest feedback</h2>
        </div>
        <div class="card-grid services-grid">
            <?php if (count($reviews) === 0): ?>
                <div class="card">No reviews yet. Be the first to share your experience.</div>
            <?php endif; ?>
            <?php foreach ($reviews as $review): ?>
                <article class="review-card card">
                    <p><strong><?php echo htmlspecialchars($review['stylist_name']); ?></strong> rated <?php echo htmlspecialchars($review['rating']); ?>/5</p>
                    <p><?php echo htmlspecialchars($review['comment']); ?></p>
                    <p class="service-meta">by <?php echo htmlspecialchars($review['client_name']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
