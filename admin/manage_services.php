<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/service_functions.php';
requireRole('admin');

$message = '';
$categories = ['Hair', 'Nails', 'Makeup', 'Beauty', 'Spa', 'Grooming', 'Package'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
    $data = [
        'name' => sanitize($_POST['name'] ?? ''),
        'category' => sanitize($_POST['category'] ?? ''),
        'price' => filter_var($_POST['price'] ?? '', FILTER_VALIDATE_INT),
        'duration' => sanitize($_POST['duration'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'image' => sanitize($_POST['image'] ?? ''),
    ];

    if ($data['name'] && $data['category'] && $data['price'] !== false && $data['duration'] && $data['description']) {
        if ($serviceId > 0) {
            updateService($pdo, $serviceId, $data);
            $message = 'Service updated successfully.';
        } else {
            addService($pdo, $data);
            $message = 'Service added successfully.';
        }
    } else {
        $message = 'Please complete all service fields.';
    }
}

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    deleteService($pdo, $deleteId);
    header('Location: /admin/manage_services.php');
    exit;
}

$services = fetchServices($pdo);
$editService = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editService = fetchServiceById($pdo, $editId);
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="section section-light">
    <div class="container">
        <h1 class="section-title">Manage Services</h1>
        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <div class="card form-card">
            <h2><?php echo $editService ? 'Edit service' : 'Add service'; ?></h2>
            <form method="post">
                <input type="hidden" name="service_id" value="<?php echo $editService ? $editService['id'] : 0; ?>">
                <div class="form-group">
                    <label for="name">Service name</label>
                    <input type="text" id="name" name="name" value="<?php echo $editService ? htmlspecialchars($editService['name']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category; ?>" <?php echo $editService && $editService['category'] === $category ? 'selected' : ''; ?>><?php echo $category; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" id="price" name="price" value="<?php echo $editService ? htmlspecialchars($editService['price']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="duration">Duration</label>
                    <input type="text" id="duration" name="duration" value="<?php echo $editService ? htmlspecialchars($editService['duration']) : ''; ?>" placeholder="e.g. 45 mins" required>
                </div>
                <div class="form-group">
                    <label for="image">Image URL (optional)</label>
                    <input type="text" id="image" name="image" value="<?php echo $editService ? htmlspecialchars($editService['image']) : ''; ?>" placeholder="https://example.com/image.jpg">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required><?php echo $editService ? htmlspecialchars($editService['description']) : ''; ?></textarea>
                </div>
                <button type="submit" class="primary-btn"><?php echo $editService ? 'Update service' : 'Add service'; ?></button>
            </form>
        </div>
        <div class="card table-card" style="margin-top:2rem;">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr><th>Name</th><th>Category</th><th>Duration</th><th>Price</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                <td><?php echo htmlspecialchars($service['category']); ?></td>
                                <td><?php echo htmlspecialchars($service['duration']); ?></td>
                                <td>KES <?php echo number_format($service['price']); ?></td>
                                <td>
                                    <a href="/admin/manage_services.php?edit=<?php echo $service['id']; ?>" class="secondary-btn">Edit</a>
                                    <a href="/admin/manage_services.php?delete=<?php echo $service['id']; ?>" class="secondary-btn">Delete</a>
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
