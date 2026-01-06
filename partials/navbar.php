<?php
$isLoggedIn = isset($_SESSION['user_id']);
?>
<nav class="topbar py-2">
  <div class="container d-flex align-items-center justify-content-between">
    <a class="text-decoration-none d-flex align-items-center gap-2" href="/money-tracker/index.php">
      <div class="rounded-4 px-3 py-2" style="background: rgba(79,70,229,0.12); border:1px solid rgba(79,70,229,0.16);">
        <span class="fw-bold" style="color:#3730a3;">MT</span>
      </div>
      <div>
        <div class="fw-bold" style="letter-spacing:-0.02em;">Money Tracker</div>
        <div class="small text-muted" style="margin-top:-2px;">simple • clean • useful</div>
      </div>
    </a>

    <div class="d-flex align-items-center gap-2">
      <?php if ($isLoggedIn): ?>
        <a class="btn btn-sm btn-soft" href="/money-tracker/app/dashboard.php">Dashboard</a>
        <a class="btn btn-sm btn-primary" href="/money-tracker/app/transaksi_tambah.php">+ Transaksi</a>
        <a class="btn btn-sm btn-outline-secondary" href="/money-tracker/app/transaksi_list.php">Riwayat</a>
        <a class="btn btn-sm btn-outline-secondary" href="/money-tracker/app/laporan.php">Laporan</a>
        <a class="btn btn-sm btn-outline-danger" href="/money-tracker/auth/logout.php">Logout</a>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-secondary" href="/money-tracker/auth/login.php">Login</a>
        <a class="btn btn-sm btn-primary" href="/money-tracker/auth/register.php">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
