<?php
$isLoggedIn = isset($_SESSION['user_id']);
$active = $active ?? ''; // set dari tiap halaman: 'dashboard', 'list', dll

function navItem(string $href, string $label, string $icon, string $key, string $activeKey): string {
  $isActive = $key === $activeKey;
  return '
    <a class="nav-itemx '.($isActive?'active':'').'" href="'.$href.'">
      <span class="iconx">'.$icon.'</span>
      <span class="labelx">'.$label.'</span>
    </a>
  ';
}
?>
<aside class="sidebarx">
  <div class="brandx">
    <div class="logo">
      <img src="/money-tracker/uploads/logo.jpg" alt="Money Tracker logo">
    </div>
    <div class="meta">
      <div class="name">Money Tracker</div>
      <div class="sub">Student finance</div>
    </div>
  </div>

  <?php if ($isLoggedIn): ?>
  <nav class="navx">
    <?= navItem('/money-tracker/app/dashboard.php', 'Dashboard', '🏠', 'dashboard', $active) ?>
    <?= navItem('/money-tracker/app/transaksi_tambah.php', 'Tambah', '➕', 'tambah', $active) ?>
    <?= navItem('/money-tracker/app/transaksi_list.php', 'Riwayat', '🧾', 'list', $active) ?>
    <?= navItem('/money-tracker/app/laporan.php', 'Laporan', '📊', 'laporan', $active) ?>
    <?= navItem('/money-tracker/app/kategori.php', 'Kategori', '🏷️', 'kategori', $active) ?>
  </nav>

  <div class="sidecard">
    <div class="sidecard-title">Tips</div>
    <div class="sidecard-text">Catat transaksi kecil juga biar saldo kamu akurat.</div>
    <a class="btn btn-light btn-sm w-100 mt-2" href="/money-tracker/app/transaksi_tambah.php">Mulai catat</a>
  </div>

  <div class="logoutx">
    <a class="btn btn-outline-light w-100" href="/money-tracker/auth/logout.php">Logout</a>
  </div>
  <?php else: ?>
    <div class="p-3">
      <a class="btn btn-light w-100 mb-2" href="/money-tracker/auth/login.php">Login</a>
      <a class="btn btn-outline-light w-100" href="/money-tracker/auth/register.php">Register</a>
    </div>
  <?php endif; ?>
</aside>
