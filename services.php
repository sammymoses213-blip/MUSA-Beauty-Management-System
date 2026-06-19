<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/service_functions.php';

$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$services = fetchServices($pdo, $category ?: null);
$categories = fetchServiceCategories($pdo);
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<section class="section section-light">
    <div class="container">
        <h1 class="section-title">Service Menu</h1>
        <p>Browse our salon services and book the perfect treatment for your next appointment.</p>
        <div class="category-filter" style="margin:2rem 0; display:flex; flex-wrap:wrap; gap:0.75rem;">
            <a href="/services.php" class="secondary-btn <?php echo $category === '' ? 'active' : ''; ?>">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="/services.php?category=<?php echo urlencode($cat); ?>" class="secondary-btn <?php echo $category === $cat ? 'active' : ''; ?>"><?php echo htmlspecialchars($cat); ?></a>
            <?php endforeach; ?>
        </div>
        <div class="services-grid card-grid">
            <?php if (count($services) === 0): ?>
                <div class="card">No services found for this category.</div>
            <?php endif; ?>
            <?php foreach ($services as $service): ?>
                <article class="service-card card">
                    <?php if (!empty($service['image'])): ?>
                        <img src="<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" style="border-radius:20px; max-height:180px; object-fit:cover; width:100%;">
                    <?php else: ?>
                        <div style="height:180px; border-radius:20px; background: linear-gradient(135deg, rgba(248, 187, 208, 0.8), rgba(255, 245, 225, 0.8)); display:flex; align-items:center; justify-content:center; color: var(--deep-plum); font-weight:700;"><?php echo htmlspecialchars($service['category']); ?></div>
                    <?php endif; ?>
                    <div>
                        <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                        <p class="service-meta"><?php echo htmlspecialchars($service['duration']); ?> • <?php echo htmlspecialchars($service['category']); ?></p>
                        <p><?php echo htmlspecialchars($service['description']); ?></p>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-top:1rem;">
                        <strong>KES <?php echo number_format($service['price']); ?></strong>
                        <a href="/client/book_appointment.php?service_id=<?php echo $service['id']; ?>" class="secondary-btn">Book Now</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
