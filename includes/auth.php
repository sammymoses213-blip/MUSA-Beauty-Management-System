<?php
session_start();
require_once __DIR__ . '/../config/db.php';

function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function getCurrentUser() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function isLoggedIn() {
    return !empty($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['user']['role'] !== $role) {
        header('Location: /login.php');
        exit;
    }
}

function redirectDashboard() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }

    $role = $_SESSION['user']['role'];
    if ($role === 'admin') {
        header('Location: /admin/dashboard.php');
    } elseif ($role === 'stylist') {
        header('Location: /stylist/dashboard.php');
    } else {
        header('Location: /client/dashboard.php');
    }
    exit;
}
