<?php
if (!isset($pdo)) {
  require_once __DIR__ . '/../config/database.php';
}
$userId = (int)($_SESSION['user_id'] ?? 0);
$username = $_SESSION['username'] ?? 'User';
$userEmail = '';
$profilePhoto = '';

if ($userId > 0) {
  try {
    $stmt = $pdo->prepare("SELECT username, email, COALESCE(profile_photo, '') as profile_photo FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if ($user) {
      $username = $user['username'] ?? $username;
      $userEmail = $user['email'] ?? '';
      $profilePhoto = $user['profile_photo'] ?? '';
    }
  } catch (Throwable $e) {
    // Jika kolom profile_photo belum ada, query tanpa kolom tersebut
    try {
      $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ? LIMIT 1");
      $stmt->execute([$userId]);
      $user = $stmt->fetch();
      if ($user) {
        $username = $user['username'] ?? $username;
        $userEmail = $user['email'] ?? '';
      }
    } catch (Throwable $e2) {
      // Ignore
    }
  }
}

$avatarInitial = strtoupper(substr($username, 0, 1));
?>
<header class="topbarx">
  <div class="searchx-wrapper" style="position: relative; flex: 1;">
    <form class="searchx" method="get" action="/money-tracker/app/transaksi_list.php" id="searchForm">
      <span class="sicon">🔎</span>
      <input type="text" name="q" id="searchInput" placeholder="Search notes / kategori / transaksi" value="<?= h($_GET['q'] ?? '') ?>" autocomplete="off">
      <input type="hidden" name="ym" value="<?= h($_GET['ym'] ?? date('Y-m')) ?>">
    </form>
    <div id="searchResults" class="search-results" style="display: none;"></div>
  </div>

  <div class="rightx">
    <button class="bellx" type="button" title="Notifikasi (UI saja)">🔔</button>
    <button class="profilex" type="button" id="btnOpenProfile" style="cursor: pointer; border: none; background: rgba(255,255,255,.92);">
      <div class="avatarx">
        <?php if ($profilePhoto): ?>
          <img src="<?= h($profilePhoto) ?>" alt="<?= h($username) ?>">
        <?php else: ?>
          <?= $avatarInitial ?>
        <?php endif; ?>
      </div>
      <div class="pmeta">
        <div class="pname"><?= h($username) ?></div>
        <div class="psub">Dompetin</div>
      </div>
    </button>
  </div>
</header>

<!-- Modal Edit Profil -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content cardx" style="border: none;">
      <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 20px;">
        <h5 class="modal-title page-title" id="profileModalLabel">Edit Profil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 20px;">
        <form id="profileForm" enctype="multipart/form-data">
          <div class="text-center mb-4">
            <div class="profile-avatar-large mb-3">
              <div class="avatarx-large" id="avatarPreview">
                <?php if ($profilePhoto): ?>
                  <img src="<?= h($profilePhoto) ?>" alt="<?= h($username) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:18px;">
                <?php else: ?>
                  <?= $avatarInitial ?>
                <?php endif; ?>
              </div>
              <label for="profilePhotoInput" class="btn-avatar-upload">
                <span>📷</span> Ganti Foto
              </label>
              <input type="file" id="profilePhotoInput" name="profile_photo" accept="image/*" style="display: none;">
            </div>
          </div>

          <div class="mb-3">
            <label for="profileUsername" class="form-label fw-semibold">Username</label>
            <input type="text" class="form-control" id="profileUsername" name="username" value="<?= h($username) ?>" required>
          </div>

          <div class="mb-3">
            <label for="profileEmail" class="form-label fw-semibold">Email</label>
            <input type="email" class="form-control" id="profileEmail" name="email" value="<?= h($userEmail) ?>" required>
          </div>

          <div id="profileMessage" class="alert" style="display: none;"></div>

          <div class="d-flex gap-2 justify-content-end mt-4">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary" id="btnSaveProfile">
              <span class="spinner-border spinner-border-sm" id="profileSpinner" style="display: none;" role="status"></span>
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const btnOpenProfile = document.getElementById('btnOpenProfile');
  const profileModal = new bootstrap.Modal(document.getElementById('profileModal'));
  const profileForm = document.getElementById('profileForm');
  const profilePhotoInput = document.getElementById('profilePhotoInput');
  const avatarPreview = document.getElementById('avatarPreview');
  const profileMessage = document.getElementById('profileMessage');
  const btnSaveProfile = document.getElementById('btnSaveProfile');
  const profileSpinner = document.getElementById('profileSpinner');

  // Buka modal saat profil diklik
  btnOpenProfile.addEventListener('click', function() {
    profileModal.show();
  });

  // Preview foto profil saat dipilih
  profilePhotoInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        avatarPreview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;border-radius:18px;">';
      };
      reader.readAsDataURL(file);
    }
  });

  // Handle form submission
  profileForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(profileForm);
    
    btnSaveProfile.disabled = true;
    profileSpinner.style.display = 'inline-block';
    profileMessage.style.display = 'none';

    try {
      const response = await fetch('/money-tracker/app/profile_update.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        profileMessage.className = 'alert alert-success';
        profileMessage.textContent = result.message || 'Profil berhasil diperbarui!';
        profileMessage.style.display = 'block';

        // Update UI
        setTimeout(() => {
          location.reload();
        }, 1000);
      } else {
        profileMessage.className = 'alert alert-danger';
        profileMessage.textContent = result.message || 'Gagal memperbarui profil.';
        profileMessage.style.display = 'block';
        btnSaveProfile.disabled = false;
        profileSpinner.style.display = 'none';
      }
    } catch (error) {
      profileMessage.className = 'alert alert-danger';
      profileMessage.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
      profileMessage.style.display = 'block';
      btnSaveProfile.disabled = false;
      profileSpinner.style.display = 'none';
    }
  });

  // Search functionality
  const searchInput = document.getElementById('searchInput');
  const searchResults = document.getElementById('searchResults');
  const searchForm = document.getElementById('searchForm');
  let searchTimeout = null;

  // Real-time search dengan debounce
  searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
      searchResults.style.display = 'none';
      return;
    }

    searchTimeout = setTimeout(async () => {
      try {
        const response = await fetch(`/money-tracker/app/search.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        displaySearchResults(data.results, query);
      } catch (error) {
        console.error('Search error:', error);
      }
    }, 300);
  });

  // Submit form saat Enter
  searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      searchResults.style.display = 'none';
      searchForm.submit();
    } else if (e.key === 'Escape') {
      searchResults.style.display = 'none';
      this.blur();
    }
  });

  // Hide results saat klik di luar
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.searchx-wrapper')) {
      searchResults.style.display = 'none';
    }
  });

  function displaySearchResults(results, query) {
    if (!results || results.length === 0) {
      searchResults.innerHTML = `
        <div class="search-result-item" style="padding: 12px; text-align: center; color: var(--muted);">
          Tidak ada hasil untuk "${query}"
        </div>
      `;
      searchResults.style.display = 'block';
      return;
    }

    let html = '';
    results.forEach(result => {
      if (result.type === 'transaction') {
        const badgeClass = result.trx_type === 'income' ? 'badge-income' : 'badge-expense';
        const badgeText = result.trx_type === 'income' ? 'Pemasukan' : 'Pengeluaran';
        const amount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(result.amount);
        
        html += `
          <a href="${result.url}" class="search-result-item">
            <div class="search-result-icon" style="background: ${result.trx_type === 'income' ? 'rgba(16,185,129,0.16)' : 'rgba(239,68,68,0.14)'};">
              ${result.trx_type === 'income' ? '💰' : '💸'}
            </div>
            <div class="search-result-content">
              <div class="search-result-title">${escapeHtml(result.title)}</div>
              <div class="search-result-subtitle">${escapeHtml(result.subtitle || '')} • ${result.date}</div>
            </div>
            <div class="search-result-meta">
              <span class="badge ${badgeClass}">${badgeText}</span>
              <div class="search-result-amount">${amount}</div>
            </div>
          </a>
        `;
      } else if (result.type === 'category') {
        const icon = result.cat_type === 'income' ? '📈' : '📉';
        html += `
          <a href="${result.url}" class="search-result-item">
            <div class="search-result-icon" style="background: ${result.cat_type === 'income' ? 'rgba(16,185,129,0.16)' : 'rgba(239,68,68,0.14)'};">
              ${icon}
            </div>
            <div class="search-result-content">
              <div class="search-result-title">${escapeHtml(result.title)}</div>
              <div class="search-result-subtitle">${escapeHtml(result.subtitle)}</div>
            </div>
          </a>
        `;
      }
    });

    // Tambahkan link "Lihat semua hasil"
    html += `
      <div class="search-result-footer">
        <a href="/money-tracker/app/transaksi_list.php?q=${encodeURIComponent(query)}" class="search-result-view-all">
          Lihat semua hasil untuk "${escapeHtml(query)}" →
        </a>
      </div>
    `;

    searchResults.innerHTML = html;
    searchResults.style.display = 'block';
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
});
</script>
