<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /money-tracker/auth/login.php"); exit; }
require_once __DIR__ . '/../config/database.php';

$userId = (int)$_SESSION['user_id'];

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function rupiah(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }

function getCategories(PDO $pdo, int $userId, string $type): array {
  $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE user_id=? AND type=? ORDER BY name ASC");
  $stmt->execute([$userId, $type]);
  return $stmt->fetchAll();
}

$types = ['income' => 'Pemasukan', 'expense' => 'Pengeluaran'];
$type = $_POST['type'] ?? 'expense';
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $type = $_POST['type'] ?? 'expense';
  $trx_date = $_POST['trx_date'] ?? date('Y-m-d');
  $category_id = $_POST['category_id'] ?? '';
  $amount = (float)($_POST['amount'] ?? 0);
  $note = trim($_POST['note'] ?? '');

  if (!in_array($type, ['income','expense'], true)) $error = "Tipe tidak valid.";
  elseif (!$trx_date) $error = "Tanggal wajib diisi.";
  elseif ($amount <= 0) $error = "Nominal harus > 0.";
  else {
    $catId = $category_id !== '' ? (int)$category_id : null;

    if ($catId !== null) {
      $chk = $pdo->prepare("SELECT id FROM categories WHERE id=? AND user_id=? AND type=?");
      $chk->execute([$catId, $userId, $type]);
      if (!$chk->fetch()) $catId = null;
    }

    $stmt = $pdo->prepare("
      INSERT INTO transactions (user_id, category_id, type, amount, trx_date, note)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $catId, $type, $amount, $trx_date, ($note!==''?$note:null)]);
    $success = "Transaksi berhasil ditambahkan.";
  }
}

$categories = getCategories($pdo, $userId, $type);

$title = "Tambah Transaksi";
$active = "tambah";
require_once __DIR__ . '/../partials/header.php';
?>
<div class="appx">
  <?php require_once __DIR__ . '/../partials/sidebar.php'; ?>
  <main class="mainx">
    <?php require_once __DIR__ . '/../partials/topbar.php'; ?>

    <div class="d-flex justify-content-between align-items-end mt-3 mb-3">
      <div>
        <div class="page-title fs-3">Tambah Transaksi</div>
        <div class="muted">Input cepat, kategori menyesuaikan tipe.</div>
      </div>
      <a class="btn btn-outline-secondary" href="/money-tracker/app/transaksi_list.php">Riwayat</a>
    </div>

    <div class="cardx">
      <div class="card-pad">
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

        <form method="post" class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Tipe</label>
            <select name="type" class="form-select" onchange="this.form.submit()">
              <?php foreach ($types as $k => $v): ?>
                <option value="<?= h($k) ?>" <?= $type===$k?'selected':'' ?>><?= h($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Tanggal</label>
            <input type="date" name="trx_date" class="form-control"
              value="<?= h($_POST['trx_date'] ?? date('Y-m-d')) ?>" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Kategori</label>
            <select name="category_id" class="form-select">
              <option value="">Tanpa kategori</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (($_POST['category_id'] ?? '') == $c['id'])?'selected':'' ?>>
                  <?= h($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Nominal</label>
            <input type="number" name="amount" class="form-control" min="1" step="0.01"
              value="<?= h($_POST['amount'] ?? '') ?>" placeholder="contoh: 25000" required>
            <div class="muted small mt-1">Tip: catat juga transaksi kecil biar akurat.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Catatan (opsional)</label>
            <input type="text" name="note" class="form-control" maxlength="255"
              value="<?= h($_POST['note'] ?? '') ?>" placeholder="misal: makan siang, bayar ojek...">
          </div>

          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Simpan</button>
            <a class="btn btn-outline-secondary" href="/money-tracker/app/dashboard.php">Kembali</a>
          </div>
        </form>
      </div>
    </div>

  </main>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
