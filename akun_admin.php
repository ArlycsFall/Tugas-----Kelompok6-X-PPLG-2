<?php
session_start();

// Cek login dan role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Koneksi database
$conn = new mysqli("localhost", "root", "", "mandis");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$email = $_SESSION['email'] ?? '';
$success_message = '';

// Get admin ID from session
if (!isset($_SESSION['id_akun'])) {
    header("Location: login.php");
    exit;
}

$id_akun = intval($_SESSION['id_akun']);

// Ambil data admin dari tabel akun
$query = "SELECT username, email FROM akun WHERE id_akun = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_akun);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

// Handle form submission untuk edit data admin
if (isset($_POST['edit_admin'])) {
    $username_baru = trim($_POST['username']);
    $email_baru = trim($_POST['email']);

    if (!empty($username_baru) && !empty($email_baru)) {
        $update_akun = "UPDATE akun SET username = ?, email = ? WHERE id_akun = ?";
        $stmt_akun = $conn->prepare($update_akun);
        $stmt_akun->bind_param("ssi", $username_baru, $email_baru, $id_akun);
        $success_akun = $stmt_akun->execute();
        $stmt_akun->close();

        if ($success_akun) {
            $success_message = "Data berhasil diperbarui!";
            $_SESSION['username'] = $username_baru;
            $_SESSION['email'] = $email_baru;
            // Refresh data admin
            $query = "SELECT username, email FROM akun WHERE id_akun = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id_akun);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            $stmt->close();
        } else {
            $success_message = "Gagal memperbarui data!";
        }
    }
}

// Inisial nama
$initials = '';
if (!empty($admin['username'])) {
    $nameParts = explode(' ', $admin['username']);
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
    <title>Beranda Raja Jaw- </title>
    <link rel="stylesheet" href="akun_admin.css">
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
        <a href="list_akun.php">List Akun</a>
        <a href="akun_admin.php">Akun</a>
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

        <!-- Data admin Card -->
        <div class="guru-card">
    <h2>📋 Data Akun Admin</h2>
    <div class="guru-info">
        <div class="info-item">
            <div class="info-label">🔑 Username</div>
            <div class="info-value"><?= htmlspecialchars($admin['username']) ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">📧 Email</div>
            <div class="info-value"><?= htmlspecialchars($admin['email']) ?></div>
        </div>
    </div>
    <button class="edit-btn" onclick="openEditModal()">✏️ Edit Data</button>
</div>

        <!-- Modal Edit Data Admin -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">&times;</span>
                <h3>✏️ Edit Data Admin</h3>
              <form method="post">
                <div class="form-group">
                    <label>🔑 Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label>📧 Email</label>
                         <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                    </div>
                <button type="submit" name="edit_admin" class="edit-btn">💾 Simpan Perubahan</button>
            </form>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logout confirmation
    document.querySelectorAll('.logout-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin logout?')) {
                e.preventDefault();
            }
        });
    });

    // Profile dropdown functionality
    const profileDropdown = document.getElementById('profileDropdown');
    window.toggleDropdown = function() {
        profileDropdown.classList.toggle("show");
    }
    window.addEventListener('click', function(event) {
        if (!event.target.matches('.profile') && !event.target.closest('.profile')) {
            if (profileDropdown && profileDropdown.classList.contains("show")) {
                profileDropdown.classList.remove("show");
            }
        }
    });

    // Modal edit functionality
    window.openEditModal = function() {
        document.getElementById('editModal').style.display = 'block';
    }
    window.closeEditModal = function() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    // Submit edit function
    window.submitEdit = function() {
        const successAlert = document.getElementById('successAlert');
        successAlert.style.display = 'block';
        closeEditModal();
        
        // Auto hide success message after 5 seconds
        setTimeout(() => {
            successAlert.style.opacity = '0';
            setTimeout(() => {
                successAlert.style.display = 'none';
                successAlert.style.opacity = '1';
            }, 300);
        }, 5000);
    }
    
    // Tutup modal jika klik di luar modal
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) {
            closeEditModal();
        }
    }

    // Mobile menu functionality
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    if (menuToggle && sidebar && overlay) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        });
    }
});
</script>
</body>
</html>