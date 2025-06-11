<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function login($username, $password) {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, username, password, name FROM admin WHERE username = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$username]);
    
    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_name'] = $user['name'];
            return true;
        }
    }
    return false;
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>