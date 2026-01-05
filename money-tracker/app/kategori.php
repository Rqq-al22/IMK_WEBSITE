<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /money-tracker/auth/login.php"); exit; }
require_once __DIR__ . '/../config/database.php';

$userId = (int)$_SESSION['user_id'];
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$error = ""; $success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? '';
    if ($name === '' || !in_array($type, ['income','expense'], true)) {
      $error = "Input kategori tidak valid.";
    } else {
      try {
        $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type) VALUES (?,?,?)");
        $stmt->execute([$userId, $name, $type]);
        $success = "Kategori ditambahkan.";
      } catch (Throwable $e) {
        $error = "Kategori sudah ada untuk tipe ini.";
      }
    }
  }

  if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    if (ctype_digit($id)) {
      $stmt = $pdo->prepare("DELETE FROM categories WHERE id=? AND user_id=?");
      $stmt->execute([(int)$id, $userId]);
      $success = "Kategori dihapus.";
    }
  }
}

$stmt = $pdo->prepare("SELECT id, name, type FROM categories WHERE user_id=? ORDER BY type, name");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

$title = "Kategori";
$active = "kategori";
require_once __DIR__ . '/../partials/header.php';
?>
<div class="appx">
  <?php require_once __DIR__ . '/../partials/sidebar.php'; ?>
  <main class="mainx">
    <?php require_once __DIR__ . '/../partials/topbar.php'; ?>

    <div class="d-flex justify-content-between align-items-end mt-3 mb-3">
      <div>
        <div class="page-title fs-3">Kategori</div>
        <div class="muted">Kelola kategori pemasukan & pengeluaran.</div>
      </div>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

    <div class="row g-3">
      <div class="col-lg-5">
        <div class="cardx">
          <div class="card-pad">
            <div class="fw-bold mb-2">Tambah Kategori</div>
            <form method="post" class="row g-2">
              <input type="hidden" name="action" value="add">
              <div class="col-12">
                <label class="form-label small">Nama</label>
                <input type="text" name="name" class="form-control" placeholder="misal: Kopi, Parkir, Freelance" required>
              </div>
              <div class="col-12">
                <label class="form-label small">Tipe</label>
                <select name="type" class="form-select" required>
                  <option value="income">Pemasukan</option>
                  <option value="expense">Pengeluaran</option>
                </select>
              </div>
              <div class="col-12">
                <button class="btn btn-primary w-100">Tambah</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="cardx">
          <div class="card-pad">
            <div class="fw-bold mb-2">Daftar Kategori</div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th>Tipe</th>
                    <th>Nama</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rows as $r): ?>
                    <tr>
                      <td>
                        <?php if ($r['type'] === 'income'): ?>
                          <span class="badge badge-income">Pemasukan</span>
                        <?php else: ?>
                          <span class="badge badge-expense">Pengeluaran</span>
                        <?php endif; ?>
                      </td>
                      <td class="fw-semibold"><?= h($r['name']) ?></td>
                      <td class="text-end">
                        <form method="post" class="d-inline" onsubmit="return confirm('Hapus kategori ini?')">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                          <button class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="muted small mt-2">Catatan: jika kategori dihapus, transaksi lama tetap ada (category jadi null).</div>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
