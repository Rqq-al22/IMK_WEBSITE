<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { 
  header("Location: /money-tracker/auth/login.php"); 
  exit; 
}

require_once __DIR__ . '/../config/database.php';

$userId = (int)$_SESSION['user_id'];
$query = trim($_GET['q'] ?? '');

header('Content-Type: application/json');

if (strlen($query) < 2) {
  echo json_encode(['results' => []]);
  exit;
}

$results = [];

// Search transaksi (notes dan kategori)
if ($query !== '') {
  $stmt = $pdo->prepare("
    SELECT 
      t.id,
      t.trx_date,
      t.type,
      t.amount,
      t.note,
      c.name AS category_name,
      'transaction' AS result_type
    FROM transactions t
    LEFT JOIN categories c ON c.id = t.category_id
    WHERE t.user_id = ? 
      AND (
        t.note LIKE ? 
        OR c.name LIKE ?
      )
    ORDER BY t.trx_date DESC, t.id DESC
    LIMIT 10
  ");
  $searchTerm = "%{$query}%";
  $stmt->execute([$userId, $searchTerm, $searchTerm]);
  $transactions = $stmt->fetchAll();
  
  foreach ($transactions as $t) {
    $results[] = [
      'id' => (int)$t['id'],
      'type' => 'transaction',
      'title' => $t['category_name'] ?? 'Tanpa Kategori',
      'subtitle' => $t['note'] ?? '',
      'date' => $t['trx_date'],
      'amount' => (float)$t['amount'],
      'trx_type' => $t['type'],
      'url' => '/money-tracker/app/transaksi_list.php?q=' . urlencode($query)
    ];
  }
  
  // Search kategori
  $stmt = $pdo->prepare("
    SELECT id, name, type
    FROM categories
    WHERE user_id = ? AND name LIKE ?
    ORDER BY type, name
    LIMIT 5
  ");
  $stmt->execute([$userId, $searchTerm]);
  $categories = $stmt->fetchAll();
  
  foreach ($categories as $c) {
    // Cek apakah kategori sudah ada di results
    $exists = false;
    foreach ($results as $r) {
      if ($r['type'] === 'transaction' && $r['title'] === $c['name']) {
        $exists = true;
        break;
      }
    }
    
      if (!$exists) {
      // Hitung jumlah transaksi di kategori ini
      $stmt2 = $pdo->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND category_id = ?");
      $stmt2->execute([$userId, (int)$c['id']]);
      $countRow = $stmt2->fetch();
      $count = $countRow['count'] ?? 0;
      
      $results[] = [
        'id' => (int)$c['id'],
        'type' => 'category',
        'title' => $c['name'],
        'subtitle' => ($c['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran') . ' • ' . $count . ' transaksi',
        'cat_type' => $c['type'],
        'url' => '/money-tracker/app/transaksi_list.php?cat=' . (int)$c['id'] . '&q=' . urlencode($query)
      ];
    }
  }
}

echo json_encode(['results' => $results]);
