<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'guru') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mandis");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$email = $_SESSION['email'] ?? '';
$success_message = '';

if (!isset($_SESSION['id_guru'])) {
    header("Location: login.php");
    exit;
}

$id_guru = intval($_SESSION['id_guru']);

$query = "SELECT g.*, a.username, a.email 
          FROM guru g 
          JOIN akun a ON g.id_akun = a.id_akun 
          WHERE g.id_guru = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$result = $stmt->get_result();
$guru = $result->fetch_assoc();
$stmt->close();

if (isset($_POST['edit_guru'])) {
    $nama = trim($_POST['nama']);
    $jabatan = trim($_POST['jabatan']);
    $username_baru = trim($_POST['username']);
    $email_baru = trim($_POST['email']);

    if (!empty($nama) && !empty($jabatan) && !empty($username_baru) && !empty($email_baru)) {
        $update_guru = "UPDATE guru SET nama = ?, jabatan = ? WHERE id_guru = ?";
        $stmt_guru = $conn->prepare($update_guru);
        $stmt_guru->bind_param("ssi", $nama, $jabatan, $id_guru);
        $success_guru = $stmt_guru->execute();
        $stmt_guru->close();

        $update_akun = "UPDATE akun SET username = ?, email = ? WHERE id_akun = ?";
        $stmt_akun = $conn->prepare($update_akun);
        $stmt_akun->bind_param("ssi", $username_baru, $email_baru, $guru['id_akun']);
        $success_akun = $stmt_akun->execute();
        $stmt_akun->close();

        if ($success_guru && $success_akun) {
            $success_message = "Data berhasil diperbarui!";
            $_SESSION['username'] = $username_baru;
            $_SESSION['email'] = $email_baru;
        } else {
            $success_message = "Gagal memperbarui data!";
        }
    }
}

// Refresh data guru setelah update
$query = "SELECT g.*, a.username, a.email 
          FROM guru g 
          JOIN akun a ON g.id_akun = a.id_akun 
          WHERE g.id_guru = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$result = $stmt->get_result();
$guru = $result->fetch_assoc();
$stmt->close();

// Inisial nama
$initials = '';
if (!empty($_SESSION['username'])) {
    $nameParts = explode(' ', $_SESSION['username']);
    foreach ($nameParts as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($initials) >= 2) break;
    }
}
if ($initials === '') $initials = '404';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Guru - Mandis</title>
    <link rel="stylesheet" href="akun_guru.css">
</head>
<body>
<div class="container">

    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="logo-container">
            <img src="./img/logos.png" alt="Logo Sekolah" class="logo">
            <h3>Mandis</h3>
        </div>
        <button class="menu-toggle" id="menuToggle">☰</button>
    </div>

    <!-- Overlay untuk mobile menu -->
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="./img/logos.png" alt="Logo M" class="logo">
            <h3><br>Manajemen<br>Data Siswa</h3>
        </div>
        <a href="beranda_guru.php">Beranda</a>
        <a href="lihat_dokumen.php">Lihat Data Siswa</a>
        <a href="akun_guru.php">Akun</a>
    </div>

    <!-- Main Content Area -->
    <div class="main">
        <!-- Top Bar with Profile -->
        <div class="topbar">
            <h2>Akun</h2>
            <div class="dropdown">
                <div class="profile" onclick="toggleDropdown()">
                    <div class="user-avatar" id='userAvatar'><?= $initials ?></div>
                    <span><?= htmlspecialchars($username) ?> (<?= ucfirst($role) ?>)</span>
                    <span style="margin-left: 5px;">▼</span>
                </div>
                <div id="profileDropdown" class="dropdown-content">
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (!empty($success_message)): ?>
            <div class="alert">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <!-- Data Guru Card -->
        <div class="guru-card">
            <h2>📋 Data Akun Guru</h2>
            <div class="guru-info">
                <div class="info-item">
                    <div class="info-label">👤 Nama</div>
                    <div class="info-value"><?= htmlspecialchars($guru['nama']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">💼 Jabatan</div>
                    <div class="info-value"><?= htmlspecialchars($guru['jabatan']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">🔑 Username</div>
                    <div class="info-value"><?= htmlspecialchars($guru['username']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">📧 Email</div>
                    <div class="info-value"><?= htmlspecialchars($guru['email']) ?></div>
                </div>
            </div>
            <button class="edit-btn" onclick="openEditModal()">✏️ Edit Data</button>
        </div>

        <!-- Modal Edit Data Guru -->
        <div id="editModal" class="modal" style="display:none;">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">&times;</span>
                <h3>✏️ Edit Data Guru</h3>
                <form method="post">
                    <div class="form-group">
                        <label>👤 Nama</label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($guru['nama']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>💼 Jabatan</label>
                        <input type="text" name="jabatan" value="<?= htmlspecialchars($guru['jabatan']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>🔑 Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($guru['username']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>📧 Email</label>
                        <input type="text" name="email" value="<?= htmlspecialchars($guru['email']) ?>" required>
                    </div>
                    <button type="submit" name="edit_guru" class="edit-btn">💾 Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDropdown() {
    document.getElementById("profileDropdown").classList.toggle("show");
}

// Modal open/close
function openEditModal() {
    document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    });
    
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });

    // Logout confirmation
    document.querySelectorAll('.logout-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin logout?')) {
                e.preventDefault();
            }
        });
    });
});

// Close dropdown and modal when clicking outside
window.onclick = function(event) {
    // Dropdown
    if (!event.target.matches('.profile') && !event.target.matches('.profile *')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
    // Modal
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
};
</script>
</body>
</html>