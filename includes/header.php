<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$currentPath = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MUSA Beauty Management System</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="/index.php">MUSA Beauty</a>
            <button class="nav-toggle" id="navToggle">Menu</button>
            <nav class="site-nav" id="siteNav">
                <a href="/index.php" class="<?php echo $currentPath === 'index.php' ? 'active' : ''; ?>">Home</a>
                <a href="/services.php" class="<?php echo $currentPath === 'services.php' ? 'active' : ''; ?>">Services</a>
                <?php if ($user): ?>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="/admin/dashboard.php">Dashboard</a>
                    <?php elseif ($user['role'] === 'stylist'): ?>
                        <a href="/stylist/dashboard.php">Dashboard</a>
                    <?php else: ?>
                        <a href="/client/dashboard.php">Dashboard</a>
                    <?php endif; ?>
                    <a href="/logout.php">Logout</a>
                <?php else: ?>
                    <a href="/login.php" class="<?php echo $currentPath === 'login.php' ? 'active' : ''; ?>">Login</a>
                    <a href="/register.php" class="<?php echo $currentPath === 'register.php' ? 'active' : ''; ?>">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="main-content">
