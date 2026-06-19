<?php
require_once __DIR__ . '/includes/auth.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$query = "SELECT * FROM services";
$params = [];
if ($search !== '') {
    $query .= " WHERE name LIKE :search OR description LIKE :search";
    $params[':search'] = "%$search%";
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$services = $stmt->fetchAll();

$stylistStmt = $pdo->prepare("SELECT s.*, u.name AS stylist_name FROM stylists s JOIN users u ON s.user_id = u.id LIMIT 4");
$stylistStmt->execute();
$stylists = $stylistStmt->fetchAll();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<section class="hero">
    <div class="container hero-grid">
        <div>
            <h1 class="hero-title">MUSA Beauty System for modern salons.</h1>
            <p class="hero-copy">Book appointments, manage stylists, and run your salon operations with a soft, intuitive platform crafted for beauty professionals.</p>
            <div class="hero-actions">
                <?php if ($user): ?>
                    <a href="/client/dashboard.php" class="primary-btn">Go to Dashboard</a>
                <?php else: ?>
                    <a href="/login.php" class="primary-btn">Login to book</a>
                    <a href="/register.php" class="secondary-btn">Create account</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-visual"></div>
    </div>
</section>

<section class="section section-light" id="services">
    <div class="container">
        <div class="section-head">
            <h2 class="section-title">Services tailored for every client.</h2>
            <p>Browse premium salon experiences and book the service that fits your look.</p>
        </div>
        <form method="get" class="search-bar">
            <input type="text" name="search" placeholder="Search services or stylist specialties" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="secondary-btn">Search</button>
        </form>
        <div class="services-grid card-grid">
            <?php if (count($services) === 0): ?>
                <div class="card">
                    <p>No services found. Try a different keyword.</p>
                </div>
            <?php endif; ?>
            <?php foreach ($services as $service): ?>
                <article class="service-card card">
                    <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p class="service-meta"><?php echo htmlspecialchars($service['description']); ?></p>
                    <p><strong>KES <?php echo number_format($service['price']); ?></strong></p>
                    <a href="<?php echo $user ? '/client/book_appointment.php' : '/login.php'; ?>" class="secondary-btn">Book now</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2 class="section-title">Featured stylists.</h2>
            <p>Discover our talented stylists with curated skills and specialties.</p>
        </div>
        <div class="card-grid services-grid">
            <?php foreach ($stylists as $stylist): ?>
                <article class="stylist-card card">
                    <h3><?php echo htmlspecialchars($stylist['stylist_name']); ?></h3>
                    <p class="stylist-meta">Specialty: <?php echo htmlspecialchars($stylist['specialization'] ?: 'Hair & Beauty'); ?></p>
                    <p>Connect with an expert stylist and book an appointment in minutes.</p>
                    <a href="<?php echo $user ? '/client/book_appointment.php' : '/login.php'; ?>" class="secondary-btn">Book this stylist</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
