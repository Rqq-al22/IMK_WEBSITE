<?php
declare(strict_types=1);
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

require_once __DIR__ . '/../config/database.php';

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
  exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$profilePhoto = null;

// Validasi
if ($username === '' || $email === '') {
  echo json_encode(['success' => false, 'message' => 'Username dan email wajib diisi.']);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']);
  exit;
}

// Handle upload foto profil
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
  $file = $_FILES['profile_photo'];
  $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  $maxSize = 2 * 1024 * 1024; // 2MB

  if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.']);
    exit;
  }

  if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 2MB.']);
    exit;
  }

  // Buat direktori uploads jika belum ada
  $uploadDir = __DIR__ . '/../uploads/profiles/';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }

  // Generate nama file unik
  $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
  $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
  $filepath = $uploadDir . $filename;

  if (move_uploaded_file($file['tmp_name'], $filepath)) {
    $profilePhoto = '/money-tracker/uploads/profiles/' . $filename;

    // Hapus foto lama jika ada
    $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $oldUser = $stmt->fetch();
    if ($oldUser && $oldUser['profile_photo']) {
      $oldPath = __DIR__ . '/../' . ltrim($oldUser['profile_photo'], '/');
      if (file_exists($oldPath) && strpos($oldPath, 'uploads/profiles/') !== false) {
        @unlink($oldPath);
      }
    }
  } else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengupload foto.']);
    exit;
  }
}

try {
  // Cek apakah username/email sudah dipakai user lain
  $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1");
  $stmt->execute([$username, $email, $userId]);
  if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Username atau email sudah digunakan.']);
    exit;
  }

  // Update database
  if ($profilePhoto) {
    // Update dengan foto baru
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_photo = ? WHERE id = ?");
    $stmt->execute([$username, $email, $profilePhoto, $userId]);
  } else {
    // Update tanpa foto (foto tetap sama)
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->execute([$username, $email, $userId]);
  }

  // Update session
  $_SESSION['username'] = $username;

  echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui!']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui profil.']);
}
