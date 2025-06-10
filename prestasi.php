<!-- BENERIN FOTO PROFILE JUGA BIAR MUNCUL HURUF PERTAMA DARI NAMA KAYA HALAMAN AKUN -->
  <!-- BENERIN JUGA TAMPILA DATA SISWANYA -->

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

// Ambil data prestasi dari database
$prestasi = [];
if (isset($_SESSION['id_siswa'])) {
    $query = "SELECT * FROM prestasi WHERE id_siswa = ? ORDER BY tanggal DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['id_siswa']);
    $stmt->execute();
    $result = $stmt->get_result();
    $prestasi = $result->fetch_all(MYSQLI_ASSOC);
}

// Proses form tambah prestasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_prestasi'])) {
    $nama_prestasi = $_POST['nama_prestasi'];
    $jenis = $_POST['jenis'];
    $tingkat = $_POST['tingkat'];
    $tanggal = $_POST['tanggal'];
    $penyelenggara = $_POST['penyelenggara'];
    $keterangan = $_POST['keterangan'] ?? '';
    $id_siswa = $_SESSION['id_siswa'];
    
    // Insert ke database
    $query = "INSERT INTO prestasi (id_siswa, nama_prestasi, jenis, tingkat, tanggal, penyelenggara, keterangan) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issssss", $id_siswa, $nama_prestasi, $jenis, $tingkat, $tanggal, $penyelenggara, $keterangan);
    
    if ($stmt->execute()) {
        header("Location: prestasi.php?success=1");
        exit;
    } else {
        $error = "Gagal menambahkan prestasi: " . $stmt->error;
    }
}

// Proses form edit prestasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_prestasi'])) {
    $id_prestasi = $_POST['id_prestasi'];
    $nama_prestasi = $_POST['edit_nama_prestasi'];
    $jenis = $_POST['edit_jenis'];
    $tingkat = $_POST['edit_tingkat'];
    $tanggal = $_POST['edit_tanggal'];
    $penyelenggara = $_POST['edit_penyelenggara'];
    $keterangan = $_POST['edit_keterangan'] ?? '';
    // Sertifikat edit diabaikan (bisa ditambah jika perlu upload baru)
    $query = "UPDATE prestasi SET nama_prestasi=?, jenis=?, tingkat=?, tanggal=?, penyelenggara=?, keterangan=? WHERE id_prestasi=? AND id_siswa=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssii", $nama_prestasi, $jenis, $tingkat, $tanggal, $penyelenggara, $keterangan, $id_prestasi, $_SESSION['id_siswa']);
    if ($stmt->execute()) {
        header("Location: prestasi.php?edit_success=1");
        exit;
    } else {
        $error = "Gagal mengedit prestasi: " . $stmt->error;
    }
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
  <title>Prestasi - Mandis</title>
  <link rel="stylesheet" href="prestasi.css">
 
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
                <h2>Predtasi</h2>
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

      <!-- Form Tambah Prestasi -->
      <div class="prestasi-container">
        <h3>+ Tambahkan Prestasi</h3>
        <form method="POST" enctype="multipart/form-data">
          <div class="form-group">
            <label for="nama_prestasi">Nama Prestasi</label>
            <input type="text" id="nama_prestasi" name="nama_prestasi" required>
          </div>
          
          <div class="form-group">
            <label for="jenis">Jenis</label>
            <input type="text" id="jenis" name="jenis" required>
          </div>
          
          <div class="form-group">
            <label for="tingkat">Tingkat</label>
            <select id="tingkat" name="tingkat" required>
              <option value="kecamatan">Kecamatan</option>
              <option value="kota">Kota/Kabupaten</option>
              <option value="provinsi">Provinsi</option>
              <option value="nasional">Nasional</option>
              <option value="internasional">Internasional</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="tanggal">Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" required>
          </div>
          
          <div class="form-group">
            <label for="penyelenggara">Penyelenggara</label>
            <input type="text" id="penyelenggara" name="penyelenggara" required>
          </div>
          
          <div class="form-group">
            <label for="sertifikat">Sertifikat/Bukti</label>
            <input type="file" id="sertifikat" name="sertifikat">
          </div>
          
          <button type="submit" name="tambah_prestasi" class="btn-submit">Simpan Prestasi</button>
        </form>
      </div>

      <!-- Daftar Prestasi -->
      <div class="prestasi-container prestasi-list">
        <h3>Daftar Prestasi</h3>
        
        <?php if (empty($prestasi)): ?>
          <p>Belum ada prestasi yang tercatat.</p>
        <?php else: ?>
          <?php foreach ($prestasi as $item): ?>
            <div class="prestasi-item">
              <h4><?= htmlspecialchars($item['nama_prestasi']) ?></h4>
              <p>Jenis: <?= htmlspecialchars($item['jenis']) ?></p>
              <p>Tingkat: <?= ucfirst($item['tingkat']) ?></p>
              <p>Tanggal: <?= date('d/m/Y', strtotime($item['tanggal'])) ?></p>
              <p>Penyelenggara: <?= htmlspecialchars($item['penyelenggara']) ?></p>
              <?php if (!empty($item['file_path'])): ?>
                <p>Bukti: <a href="<?= htmlspecialchars($item['file_path']) ?>" class="file-link" target="_blank">Lihat Sertifikat</a></p>
              <?php endif; ?>
              <button class="btn-submit edit-btn" 
                data-id="<?= $item['id_prestasi'] ?>"
                data-nama="<?= htmlspecialchars($item['nama_prestasi'], ENT_QUOTES) ?>"
                data-jenis="<?= htmlspecialchars($item['jenis'], ENT_QUOTES) ?>"
                data-tingkat="<?= htmlspecialchars($item['tingkat'], ENT_QUOTES) ?>"
                data-tanggal="<?= $item['tanggal'] ?>"
                data-penyelenggara="<?= htmlspecialchars($item['penyelenggara'], ENT_QUOTES) ?>"
                data-keterangan="<?= htmlspecialchars($item['keterangan'], ENT_QUOTES) ?>"
              >Edit Data</button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Modal Edit Prestasi -->
      <div id="editPrestasiModal" class="modal" style="display:none;">
        <div class="modal-content">
          <span class="close" id="closeEditModal">&times;</span>
          <h3>Edit Prestasi</h3>
          <form method="POST">
            <input type="hidden" name="id_prestasi" id="edit_id_prestasi">
            <div class="form-group">
              <label for="edit_nama_prestasi">Nama Prestasi</label>
              <input type="text" id="edit_nama_prestasi" name="edit_nama_prestasi" required>
            </div>
            <div class="form-group">
              <label for="edit_jenis">Jenis</label>
              <input type="text" id="edit_jenis" name="edit_jenis" required>
            </div>
            <div class="form-group">
              <label for="edit_tingkat">Tingkat</label>
              <select id="edit_tingkat" name="edit_tingkat" required>
                <option value="kecamatan">Kecamatan</option>
                <option value="kota">Kota/Kabupaten</option>
                <option value="provinsi">Provinsi</option>
                <option value="nasional">Nasional</option>
                <option value="internasional">Internasional</option>
              </select>
            </div>
            <div class="form-group">
              <label for="edit_tanggal">Tanggal</label>
              <input type="date" id="edit_tanggal" name="edit_tanggal" required>
            </div>
            <div class="form-group">
              <label for="edit_penyelenggara">Penyelenggara</label>
              <input type="text" id="edit_penyelenggara" name="edit_penyelenggara" required>
            </div>
            <div class="form-group">
              <label for="edit_keterangan">Keterangan</label>
              <textarea id="edit_keterangan" name="edit_keterangan"></textarea>
            </div>
            <button type="submit" name="edit_prestasi" class="btn-submit">Simpan Perubahan</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Help Button -->
    <a class="help" href="bantuan.php">Butuh Bantuan?</a>
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
    
    // Close dropdown when clicking outside
    window.addEventListener('click', function(event) {
        if (!event.target.matches('.profile') && !event.target.closest('.profile')) {
            if (profileDropdown && profileDropdown.classList.contains("show")) {
                profileDropdown.classList.remove("show");
            }
        }
    });

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

    // Modal Edit Prestasi
    const editModal = document.getElementById('editPrestasiModal');
    const closeEditModal = document.getElementById('closeEditModal');
    const editBtns = document.querySelectorAll('.edit-btn');
    editBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        document.getElementById('edit_id_prestasi').value = btn.getAttribute('data-id');
        document.getElementById('edit_nama_prestasi').value = btn.getAttribute('data-nama');
        document.getElementById('edit_jenis').value = btn.getAttribute('data-jenis');
        document.getElementById('edit_tingkat').value = btn.getAttribute('data-tingkat');
        document.getElementById('edit_tanggal').value = btn.getAttribute('data-tanggal');
        document.getElementById('edit_penyelenggara').value = btn.getAttribute('data-penyelenggara');
        document.getElementById('edit_keterangan').value = btn.getAttribute('data-keterangan');
        editModal.style.display = 'block';
      });
    });
    if (closeEditModal) {
      closeEditModal.onclick = function() {
        editModal.style.display = 'none';
      }
    }
    window.onclick = function(event) {
      if (event.target == editModal) {
        editModal.style.display = 'none';
      }
    }
});
  </script>
</body>
</html>