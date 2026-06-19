<?php
function fetchServices($pdo, $category = null) {
    if ($category) {
        $stmt = $pdo->prepare('SELECT * FROM services WHERE category = :category ORDER BY name');
        $stmt->execute([':category' => $category]);
        return $stmt->fetchAll();
    }

    $stmt = $pdo->query('SELECT * FROM services ORDER BY category, name');
    return $stmt->fetchAll();
}

function fetchServiceCategories($pdo) {
    $stmt = $pdo->query('SELECT DISTINCT category FROM services ORDER BY category');
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function fetchServiceById($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function addService($pdo, $data) {
    $stmt = $pdo->prepare('INSERT INTO services (name, category, price, duration, description, image) VALUES (:name, :category, :price, :duration, :description, :image)');
    return $stmt->execute([
        ':name' => $data['name'],
        ':category' => $data['category'],
        ':price' => $data['price'],
        ':duration' => $data['duration'],
        ':description' => $data['description'],
        ':image' => $data['image'] ?? null,
    ]);
}

function updateService($pdo, $id, $data) {
    $stmt = $pdo->prepare('UPDATE services SET name = :name, category = :category, price = :price, duration = :duration, description = :description, image = :image WHERE id = :id');
    return $stmt->execute([
        ':name' => $data['name'],
        ':category' => $data['category'],
        ':price' => $data['price'],
        ':duration' => $data['duration'],
        ':description' => $data['description'],
        ':image' => $data['image'] ?? null,
        ':id' => $id,
    ]);
}

function deleteService($pdo, $id) {
    $stmt = $pdo->prepare('DELETE FROM services WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

// Stylist recommendation functions
function getTopStylistsByClientBookings($pdo, $clientId, $limit = 3) {
    $stmt = $pdo->prepare('
        SELECT u.id, u.name, s.specialization, COUNT(a.id) AS booking_count,
               COALESCE(AVG(r.rating), 0) AS avg_rating
        FROM users u
        JOIN stylists s ON u.id = s.user_id
        LEFT JOIN appointments a ON u.id = a.stylist_id AND a.client_id = :client_id AND a.status = "completed"
        LEFT JOIN reviews r ON u.id = r.stylist_id
        WHERE u.role = "stylist"
        GROUP BY u.id, u.name, s.specialization
        HAVING booking_count > 0
        ORDER BY booking_count DESC, avg_rating DESC
        LIMIT :limit
    ');
    $stmt->bindValue(':client_id', $clientId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTopRatedStylists($pdo, $limit = 3) {
    $stmt = $pdo->prepare('
        SELECT u.id, u.name, s.specialization, COUNT(r.id) AS review_count,
               COALESCE(AVG(r.rating), 0) AS avg_rating
        FROM users u
        JOIN stylists s ON u.id = s.user_id
        LEFT JOIN reviews r ON u.id = r.stylist_id
        WHERE u.role = "stylist"
        GROUP BY u.id, u.name, s.specialization
        ORDER BY avg_rating DESC, review_count DESC
        LIMIT :limit
    ');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTopRatedStylistsForService($pdo, $serviceId, $limit = 3) {
    $stmt = $pdo->prepare('
        SELECT u.id, u.name, s.specialization, COUNT(r.id) AS review_count,
               COALESCE(AVG(r.rating), 0) AS avg_rating
        FROM users u
        JOIN stylists s ON u.id = s.user_id
        JOIN appointments a ON u.id = a.stylist_id AND a.service_id = :service_id AND a.status = "completed"
        LEFT JOIN reviews r ON u.id = r.stylist_id
        WHERE u.role = "stylist"
        GROUP BY u.id, u.name, s.specialization
        ORDER BY avg_rating DESC, review_count DESC
        LIMIT :limit
    ');
    $stmt->bindValue(':service_id', $serviceId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
