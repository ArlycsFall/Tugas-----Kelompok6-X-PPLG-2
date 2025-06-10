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

// Ambil statistik siswa
$total_siswa = 0;
$siswa_aktif = 0;
$total_akun = 0;
$total_guru = 0;


$query = "SELECT COUNT(*) as total FROM siswa";
$result = $conn->query($query);
if ($result) {
    $total_siswa = $result->fetch_assoc()['total'];
}

$query = "SELECT COUNT(*) as aktif FROM siswa WHERE status = 'aktif'";
$result = $conn->query($query);
if ($result) {
    $siswa_aktif = $result->fetch_assoc()['aktif'];
}

$query = "SELECT COUNT(*) as total FROM akun";
$result = $conn->query($query);
if ($result) {
    $total_akun = $result->fetch_assoc()['total' ] - 1;//-1 bwat atmin, tehe
}

$query = "SELECT COUNT(*) as total FROM guru";
$result = $conn->query($query);
if ($result) {
    $total_guru = $result->fetch_assoc()['total'];
}
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
    <title>Beranda Raja Jaw-</title>
    <link rel="stylesheet" href="beranda_admin.css">

 
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
                <h2>Beranda</h2>
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
              
              <!-- Welcome Section -->
              <div class="welcome-section">
                <h3>Halo <?= htmlspecialchars($username) ?> Sang Penggaris</h3> 
                <p>Siap untuk mengatur dunia? (data)</p>
              </div>
              
              <!-- Recent Activity atau Info Lainnya -->
              <div class="data-container">
                  <h3>📊 Informasi Sistem</h3>
                  <div class="info-grid">
                      <div class="info-item">
                          <strong>Role Anda:</strong>
                          <span class="status-badge status-aktif"><?= ucfirst($role) ?></span>
                      </div>
                      <div class="info-item">
                          <strong>Username:</strong>
                          <span><?= htmlspecialchars($username) ?></span>
                      </div>
                      <div class="info-item">
                          <strong>Email:</strong>
                          <span><?= htmlspecialchars($email) ?></span>
                      </div>
                  </div>
              </div>
              
              <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-info">
                    <h3><?= $total_siswa ?></h3>
                    <p>Total Siswa</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?= $siswa_aktif ?></h3>
                    <p>Siswa Aktif</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🏫</div>
                <div class="stat-info">
                  <h3><?= $total_guru   ?></h3>
                    <p>Akun Guru</p>
                </div>
            </div>
        </div>

            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                  <h3><?= $total_akun ?></h3>
                    <p>Total Akun</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>Menu Guru</h3>
                <div class="action-buttons">
                    <a href="lihat_dokumen.php" class="action-btn">
                        <span class="btn-icon">👥</span>
                        <span>Lihat Data Siswa</span>
                    </a>
                </div>
            </div>
            
        </div>  
        
    </div>
        
    <!-- Help Button -->
    <a class="help" href="bantuan.php">Butuh Bantuan?</a>

</div>

<script>
    // Toggle dropdown profile
    function toggleDropdown() {
        document.getElementById("profileDropdown").classList.toggle("show");
    }

    // Close dropdown when clicking outside
    window.onclick = function(event) {
        if (!event.target.matches('.profile') && !event.target.matches('.profile *')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
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
    });
</script>
</body>
</html>