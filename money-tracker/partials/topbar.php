<?php
$username = $_SESSION['username'] ?? 'User';
?>
<header class="topbarx">
  <div class="searchx">
    <span class="sicon">🔎</span>
    <input type="text" placeholder="Search notes / kategori / transaksi (dummy UI)" disabled>
  </div>

  <div class="rightx">
    <button class="bellx" type="button" title="Notifikasi (UI saja)">🔔</button>
    <div class="profilex">
      <div class="avatarx"><?= strtoupper(substr($username,0,1)) ?></div>
      <div class="pmeta">
        <div class="pname"><?= h($username) ?></div>
        <div class="psub">money tracker</div>
      </div>
    </div>
  </div>
</header>
