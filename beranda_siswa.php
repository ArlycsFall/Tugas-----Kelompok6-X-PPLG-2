 <!-- BENERIN FOTO PROFILE JUGA BIAR MUNCUL HURUF PERTAMA DARI NAMA KAYA HALAMAN AKUN -->

<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'];
$role = $_SESSION['role'];



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
  <title>Beranda Siswa - Mandis</title>
  <link rel="stylesheet" href="beranda_siswa.css">
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
      <a href="beranda_siswa.php">Beranda</a>
      <a href="prestasi.php">Prestasi</a>
      <a href="akun_siswa.php">Akun</a>
      <a href="dokumen.php">Dokumen</a>
    </div>
    
    <!-- Main Content Area -->
    <div class="main">
        <!-- Top Bar with Search and Profile -->
       <div class="main-content">
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
      </div>
     
      <!-- Welcome Section -->
      <div class="welcome">
        <h1>SELAMAT DATANG</h1>
        <p><?= htmlspecialchars($username) ?></p>
      </div>
      
      <!-- Quick Action Buttons -->
      <div class="buttons">
        <a href="prestasi.php">Prestasi</a>
        <a href="akun_siswa.php">Akun</a>
      </div>
    </div>
    
    <!-- Help Button -->
    <a class="help" href="bantuan.php">Butuh Bantuan?</a>
  </div>
  <script src="beranda_siswa.js"></script>
</body>
</html>