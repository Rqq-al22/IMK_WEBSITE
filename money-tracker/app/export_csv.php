<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /money-tracker/auth/login.php"); exit; }
require_once __DIR__ . '/../config/database.php';

$userId = (int)$_SESSION['user_id'];

$ym = $_GET['ym'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) $ym = date('Y-m');

$type = $_GET['type'] ?? '';
$cat  = $_GET['cat'] ?? '';
$q    = trim($_GET['q'] ?? '');

$start = $ym.'-01';
$end = date('Y-m-t', strtotime($start));

$params = [$userId, $start, $end];
$where = "t.user_id=? AND t.trx_date BETWEEN ? AND ?";

if (in_array($type, ['income','expense'], true)) { $where .= " AND t.type=?"; $params[] = $type; }
if ($cat !== '' && ctype_digit($cat)) { $where .= " AND t.category_id=?"; $params[] = (int)$cat; }
if ($q !== '') { $where .= " AND t.note LIKE ?"; $params[] = "%{$q}%"; }

$stmt = $pdo->prepare("
  SELECT t.trx_date, t.type, COALESCE(c.name,'') AS category, t.amount, COALESCE(t.note,'') AS note
  FROM transactions t
  LEFT JOIN categories c ON c.id=t.category_id
  WHERE {$where}
  ORDER BY t.trx_date DESC, t.id DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = "money-tracker-{$ym}.csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename='.$filename);

$out = fopen('php://output', 'w');
fputcsv($out, ['Tanggal','Tipe','Kategori','Nominal','Catatan']);

foreach ($rows as $r) {
  $tipe = $r['type']==='income' ? 'Pemasukan' : 'Pengeluaran';
  fputcsv($out, [$r['trx_date'], $tipe, $r['category'], $r['amount'], $r['note']]);
}
fclose($out);
exit;
