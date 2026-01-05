<?php
session_start();
require_once __DIR__ . '/config/app.php';

if (isset($_SESSION['user_id'])) {
  header("Location: " . BASE_URL . "/app/dashboard.php");
  exit;
}

header("Location: " . BASE_URL . "/auth/login.php");
exit;
