<!-- NANTI TAMBAHIN FITUR DELETE DOKUMEN BIAR SISWA BISA HAPUS DOKUMENNYA V-->
 <!-- BENERIN FOTO PROFILE JUGA BIAR MUNCUL HURUF PERTAMA DARI NAMA KAYA HALAMAN AKUN V-->
  <!-- TAMBAHIN FITUR DELETE DOKUMEN V-->
   <!-- V = BERES -->

<?php
session_start();

// Tambahkan koneksi database
$conn = new mysqli("localhost", "root", "", "mandis");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$username = $_SESSION['username'] ?? 'Guest';
$role = $_SESSION['role'] ?? 'guest';

// Ambil id_siswa berdasarkan username jika belum ada di session
if (!isset($_SESSION['id_siswa']) && isset($_SESSION['username'])) {
    $query = "SELECT s.id_siswa FROM siswa s 
              JOIN akun a ON s.id_akun = a.id_akun 
              WHERE a.username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['id_siswa'] = $row['id_siswa'];
    }
}

// Ambil data siswa lengkap
$siswa_data = null;
$orangtua_data = null;
$dokumen_data = [];

if (isset($_SESSION['id_siswa'])) {
    // Ambil data siswa
    $query = "SELECT s.*, a.username, a.email as email_akun 
              FROM siswa s 
              JOIN akun a ON s.id_akun = a.id_akun 
              WHERE s.id_siswa = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['id_siswa']);
    $stmt->execute();
    $result = $stmt->get_result();
    $siswa_data = $result->fetch_assoc();
    
    // Ambil data orangtua
    $query = "SELECT * FROM orangtua WHERE id_siswa = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['id_siswa']);
    $stmt->execute();
    $result = $stmt->get_result();
    $orangtua_data = $result->fetch_assoc();
    
    // Ambil data dokumen
    $query = "SELECT * FROM dokumen WHERE id_siswa = ? ORDER BY tanggal_upload DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['id_siswa']);
    $stmt->execute();
    $result = $stmt->get_result();
    $dokumen_data = $result->fetch_all(MYSQLI_ASSOC);
}

// Proses upload dokumen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_dokumen'])) {
    $jenis_dokumen = $_POST['jenis_dokumen'];
    $id_siswa = $_SESSION['id_siswa'];
    
    // Handle file upload
    if (isset($_FILES['file_dokumen']) && $_FILES['file_dokumen']['error'] === 0) {
        $upload_dir = 'uploads/dokumen/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['file_dokumen']['name']);
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['file_dokumen']['tmp_name'], $file_path)) {
            // Insert ke database
            $query = "INSERT INTO dokumen (id_siswa, jenis_dokumen, file_path, tanggal_upload) 
                      VALUES (?, ?, ?, CURDATE())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iss", $id_siswa, $jenis_dokumen, $file_path);
            
            if ($stmt->execute()) {
                header("Location: dokumen.php?success=1");
                exit;
            } else {
                $error = "Gagal menyimpan data dokumen: " . $stmt->error;
            }
        } else {
            $error = "Gagal mengupload file.";
        }
    } else {
        $error = "Tidak ada file yang diupload atau terjadi kesalahan.";
    }
}


// UNTUK MEMBERIKAN INISIAL PADA FOTO PROFIL BILA FOTO PROFIL TIDAK TEERSEDIA
$initials = '';
if (!empty($siswa_data['username'])) {
    $nameParts = explode(' ', $siswa_data['username']);
    foreach ($nameParts as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($initials) >= 2) break;
    }
}
if ($initials === '') $initials = 404;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dokumen Siswa - Mandis</title>
  <link rel="stylesheet" href="dokumen.css">
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
                <h2>Dokumen</h2>
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

      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
          Dokumen berhasil diupload!
        </div>
      <?php endif; ?>

      <?php if (isset($error)): ?>
        <div class="alert alert-error">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Data Pribadi Siswa -->
      <?php if ($siswa_data): ?>
      <div class="data-container">
        <h3>📋 Data Pribadi</h3>
        <div class="data-grid">
          <div class="data-item">
            <strong>NIS:</strong>
            <span><?= htmlspecialchars($siswa_data['nis']) ?></span>
          </div>
          <div class="data-item">
            <strong>NISN:</strong>
            <span><?= htmlspecialchars($siswa_data['nisn']) ?></span>
          </div>
          <div class="data-item">
            <strong>Nama Lengkap:</strong>
            <span><?= htmlspecialchars($siswa_data['nama']) ?></span>
          </div>
          <div class="data-item">
            <strong>Kelas:</strong>
            <span><?= htmlspecialchars($siswa_data['kelas']) ?></span>
          </div>
          <div class="data-item">
            <strong>Jenis Kelamin:</strong>
            <span><?= htmlspecialchars($siswa_data['jenis_kelamin']) ?></span>
          </div>
          <div class="data-item">
            <strong>Tanggal Lahir:</strong>
            <span><?= date('d/m/Y', strtotime($siswa_data['tanggal_lahir'])) ?></span>
          </div>
          <div class="data-item">
            <strong>Alamat:</strong>
            <span><?= htmlspecialchars($siswa_data['alamat']) ?></span>
          </div>
          <div class="data-item">
            <strong>Email:</strong>
            <span><?= htmlspecialchars($siswa_data['email']) ?></span>
          </div>
          <div class="data-item">
            <strong>Telepon:</strong>
            <span><?= htmlspecialchars($siswa_data['telepon']) ?></span>
          </div>
          <div class="data-item">
            <strong>Asal Sekolah:</strong>
            <span><?= htmlspecialchars($siswa_data['asal_sekolah']) ?></span>
          </div>
          <div class="data-item">
            <strong>Status:</strong>
            <span class="status-badge status-<?= $siswa_data['status'] ?>">
              <?= ucfirst($siswa_data['status']) ?>
            </span>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Data Orangtua -->
      <?php if ($orangtua_data): ?>
      <div class="data-container">
        <h3>👨‍👩‍👧‍👦 Data Orangtua</h3>
        <div class="data-grid">
          <div class="data-item">
            <strong>Nama Ayah:</strong>
            <span><?= htmlspecialchars($orangtua_data['nama_ayah']) ?></span>
          </div>
          <div class="data-item">
            <strong>Pekerjaan Ayah:</strong>
            <span><?= htmlspecialchars($orangtua_data['pekerjaan_ayah']) ?></span>
          </div>
          <div class="data-item">
            <strong>Nama Ibu:</strong>
            <span><?= htmlspecialchars($orangtua_data['nama_ibu']) ?></span>
          </div>
          <div class="data-item">
            <strong>Pekerjaan Ibu:</strong>
            <span><?= htmlspecialchars($orangtua_data['pekerjaan_ibu']) ?></span>
          </div>
        </div>
      </div>
      <?php else: ?>
      <div class="data-container">
        <h3>👨‍👩‍👧‍👦 Data Orangtua</h3>
        <p class="no-data">Data orangtua belum lengkap. Silakan hubungi admin untuk melengkapi data.</p>
      </div>
      <?php endif; ?>

      <!-- Upload Dokumen -->
      <div class="data-container">
        <h3>📄 Upload Dokumen</h3>
        <form method="POST" enctype="multipart/form-data" class="upload-form">
          <div class="form-group">
            <label for="jenis_dokumen">Jenis Dokumen</label>
            <select id="jenis_dokumen" name="jenis_dokumen" required>
              <option value="">Pilih jenis dokumen</option>
              <option value="Kartu Keluarga">Kartu Keluarga</option>
              <option value="Akta Kelahiran">Akta Kelahiran</option>
              <option value="Ijazah">Ijazah</option>
              <option value="Rapor">Rapor</option>
              <option value="Surat Keterangan Lulus">Surat Keterangan Lulus</option>
              <option value="Foto">Foto</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="file_dokumen">File Dokumen</label>
            <input type="file" id="file_dokumen" name="file_dokumen" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
            <small>Format yang diizinkan: PDF, JPG, PNG, DOC, DOCX (Maksimal 5MB)</small>
          </div>
          
          <button type="submit" name="upload_dokumen" class="btn-submit">Upload Dokumen</button>
        </form>
      </div>

      <!-- Daftar Dokumen -->
      <div class="data-container">
        <h3>📁 Daftar Dokumen</h3>
        
        <?php if (empty($dokumen_data)): ?>
          <p class="no-data">Belum ada dokumen yang diupload.</p>
        <?php else: ?>
          <div class="dokumen-list">
            <?php foreach ($dokumen_data as $dokumen): ?>
              <div class="dokumen-item">
                <div class="dokumen-info">
                  <h4><?= htmlspecialchars($dokumen['jenis_dokumen']) ?></h4>
                  <p>Tanggal Upload: <?= date('d/m/Y', strtotime($dokumen['tanggal_upload'])) ?></p>
                </div>
                <di class="dokumen-actions">
                  <a href="<?= htmlspecialchars($dokumen['file_path']) ?>" class="btn-view" target="_blank">Lihat</a>
                  <a href="<?= htmlspecialchars($dokumen['file_path']) ?>" class="btn-download" download>Download</a>
                  <a href="hapus_dokumen.php?id=<?= $dokumen['id_dokumen'] ?>"class="btn-delete" onclick="return confirm('Yakin ingin menghapus dokumen ini?')">Hapus</a>
                </di
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
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

    // Auto hide alerts
    document.addEventListener('DOMContentLoaded', function() {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(function(alert) {
        setTimeout(function() {
          alert.style.opacity = '0';
          setTimeout(function() {
            alert.remove();
          }, 300);
        }, 5000);
      });
    });

      
  </script>
</body>
</html>