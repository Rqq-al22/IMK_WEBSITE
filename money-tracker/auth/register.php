<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['user_id'])) {
  header("Location: " . BASE_URL . "/app/dashboard.php");
  exit;
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function seedCategories(PDO $pdo, int $userId): void {
  $income = ['Gaji', 'Hadiah', 'Freelance', 'Bonus', 'Lainnya'];
  $expense = ['Makan', 'Transport', 'Pulsa/Internet', 'Hiburan', 'Kos/Tagihan', 'Belanja', 'Lainnya'];
  $stmt = $pdo->prepare("INSERT IGNORE INTO categories (user_id, name, type) VALUES (?,?,?)");
  foreach ($income as $c)  $stmt->execute([$userId, $c, 'income']);
  foreach ($expense as $c) $stmt->execute([$userId, $c, 'expense']);
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $password2 = $_POST['password2'] ?? '';

  if ($username === '' || $email === '' || $password === '') {
    $error = "Semua field wajib diisi.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Format email tidak valid.";
  } elseif (strlen($password) < 6) {
    $error = "Password minimal 6 karakter.";
  } elseif ($password !== $password2) {
    $error = "Konfirmasi password tidak sama.";
  } else {
    try {
      $pdo->beginTransaction();

      $hash = password_hash($password, PASSWORD_BCRYPT);
      $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?,?,?)");
      $stmt->execute([$username, $email, $hash]);
      $userId = (int)$pdo->lastInsertId();

      seedCategories($pdo, $userId);

      $pdo->commit();
      $success = "Akun berhasil dibuat. Silakan login.";
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $error = "Register gagal. Username/email mungkin sudah dipakai.";
    }
  }
}

$title = "Register";
require_once __DIR__ . '/../partials/header.php';
?>
<div class="d-flex align-items-center justify-content-center" style="min-height:100vh; padding:24px;">
  <div class="cardx" style="max-width:560px; width:100%;">
    <div class="card-pad">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="avatarx" style="width:44px;height:44px;">MT</div>
        <div>
          <div class="page-title fs-4">Buat Akun</div>
          <div class="muted">Kategori default dibuat otomatis.</div>
        </div>
      </div>

      <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

      <form method="post" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Konfirmasi Password</label>
          <input type="password" name="password2" class="form-control" required>
        </div>

        <div class="col-12">
          <button class="btn btn-primary w-100">Register</button>
        </div>

        <div class="small d-flex justify-content-between">
          <span class="muted">Sudah punya akun?</span>
          <a href="<?= BASE_URL ?>/auth/login.php">Login</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
