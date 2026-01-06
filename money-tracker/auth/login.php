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

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $identity = trim($_POST['identity'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($identity === '' || $password === '') {
    $error = "Isi email/username dan password.";
  } else {
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE username=? OR email=? LIMIT 1");
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['username'] = $user['username'];
      session_regenerate_id(true);

      header("Location: " . BASE_URL . "/app/dashboard.php");
      exit;
    }
    $error = "Login gagal. Cek kembali data kamu.";
  }
}

$title = "Login";
require_once __DIR__ . '/../partials/header.php';
?>
<div class="d-flex align-items-center justify-content-center" style="min-height:100vh; padding:24px;">
  <div class="cardx" style="max-width:520px; width:100%;">
    <div class="card-pad">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="avatarx" style="width:44px;height:44px;">MT</div>
        <div>
          <div class="page-title fs-4">Masuk</div>
          <div class="muted">Kelola pemasukan & pengeluaran.</div>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger mb-3"><?= h($error) ?></div>
      <?php endif; ?>

      <form method="post" class="vstack gap-3">
        <div>
          <label class="form-label">Email / Username</label>
          <input type="text" name="identity" class="form-control" required>
        </div>
        <div>
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100">Login</button>

        <div class="d-flex justify-content-between small mt-1">
          <span class="muted">Belum punya akun?</span>
          <a href="<?= BASE_URL ?>/auth/register.php">Register</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
