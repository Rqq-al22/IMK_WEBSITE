<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /money-tracker/auth/login.php"); exit; }
require_once __DIR__ . '/../config/database.php';

$userId = (int)$_SESSION['user_id'];
$id = $_GET['id'] ?? '';
if (!ctype_digit($id)) { header("Location: /money-tracker/app/transaksi_list.php"); exit; }
$id = (int)$id;

$stmt = $pdo->prepare("DELETE FROM transactions WHERE id=? AND user_id=?");
$stmt->execute([$id, $userId]);

header("Location: /money-tracker/app/transaksi_list.php");
exit;
