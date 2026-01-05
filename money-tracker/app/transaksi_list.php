<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /money-tracker/auth/login.php"); exit; }
require_once __DIR__ . '/../config/database.php';

$userId = (int)$_SESSION['user_id'];

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function rupiah(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }

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
  SELECT t.*, c.name AS category_name
  FROM transactions t
  LEFT JOIN categories c ON c.id=t.category_id
  WHERE {$where}
  ORDER BY t.trx_date DESC, t.id DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT id, name, type FROM categories WHERE user_id=? ORDER BY type, name");
$stmt->execute([$userId]);
$cats = $stmt->fetchAll();

$title = "Riwayat";
$active = "list";
require_once __DIR__ . '/../partials/header.php';
?>
<div class="appx">
  <?php require_once __DIR__ . '/../partials/sidebar.php'; ?>
  <main class="mainx">
    <?php require_once __DIR__ . '/../partials/topbar.php'; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-end mt-3 mb-3 gap-2">
      <div>
        <div class="page-title fs-3">Riwayat Transaksi</div>
        <div class="muted">Filter default: bulan terpilih.</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="/money-tracker/app/export_csv.php?<?= http_build_query($_GET) ?>">Export CSV</a>
        <a class="btn btn-primary" href="/money-tracker/app/transaksi_tambah.php">+ Transaksi</a>
      </div>
    </div>

    <div class="cardx mb-3">
      <div class="card-pad">
        <form class="row g-2 align-items-end" method="get">
          <div class="col-md-2">
            <label class="form-label small">Bulan</label>
            <input type="month" name="ym" class="form-control" value="<?= h($ym) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label small">Tipe</label>
            <select name="type" class="form-select">
              <option value="">Semua</option>
              <option value="income" <?= $type==='income'?'selected':'' ?>>Pemasukan</option>
              <option value="expense" <?= $type==='expense'?'selected':'' ?>>Pengeluaran</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Kategori</label>
            <select name="cat" class="form-select">
              <option value="">Semua</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= ($cat!=='' && (int)$cat===(int)$c['id'])?'selected':'' ?>>
                  <?= h(($c['type']==='income'?'[IN] ':'[OUT] ').$c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Keyword catatan</label>
            <input type="text" name="q" class="form-control" value="<?= h($q) ?>" placeholder="misal: makan, ojek">
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary w-100">Terapkan</button>
            <a class="btn btn-outline-secondary w-100" href="/money-tracker/app/transaksi_list.php">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="cardx">
      <div class="card-pad">
        <?php if (!$rows): ?>
          <div class="muted">Tidak ada transaksi untuk filter ini.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Tipe</th>
                  <th>Kategori</th>
                  <th class="text-end">Nominal</th>
                  <th>Catatan</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($rows as $t): ?>
                <tr>
                  <td><?= h($t['trx_date']) ?></td>
                  <td>
                    <?php if ($t['type'] === 'income'): ?>
                      <span class="badge badge-income">Pemasukan</span>
                    <?php else: ?>
                      <span class="badge badge-expense">Pengeluaran</span>
                    <?php endif; ?>
                  </td>
                  <td><?= h($t['category_name'] ?? '-') ?></td>
                  <td class="text-end fw-semibold"><?= rupiah((float)$t['amount']) ?></td>
                  <td><?= h($t['note'] ?? '') ?></td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-secondary" href="/money-tracker/app/transaksi_edit.php?id=<?= (int)$t['id'] ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Hapus transaksi ini?')"
                       href="/money-tracker/app/transaksi_hapus.php?id=<?= (int)$t['id'] ?>">Hapus</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </main>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
