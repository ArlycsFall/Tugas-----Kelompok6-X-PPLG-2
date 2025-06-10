<?php
session_start();

function safeFetch(PDOStatement $stmt) {
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result !== false ? $result : [];
}

function safeGet(array $arr, $key, $default = '-') {
    return isset($arr[$key]) && $arr[$key] !== '' ? htmlspecialchars($arr[$key]) : $default;
}

// Database connection
class Database {
    private $host = "localhost:3306";
    private $db_name = "mandis";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Initialize database
$database = new Database();
$db = $database->getConnection();

// Get student ID from session
if (!isset($_SESSION['id_siswa'])) {
    header("Location: login.php");
    exit;
}

$id_siswa = intval($_SESSION['id_siswa']);

// Handle AJAX update request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_student') {
    header('Content-Type: application/json');
    try {
        // Update siswa table
        $query_siswa = "UPDATE siswa SET 
                       nama = :nama, nisn = :nisn, nis = :nis, kelas = :kelas,
                       tanggal_lahir = :tanggal_lahir, jenis_kelamin = :jenis_kelamin,
                       telepon = :telepon, email = :email, alamat = :alamat,
                       asal_sekolah = :asal_sekolah, status = :status
                       WHERE id_siswa = :id_siswa";
        
        $stmt_siswa = $db->prepare($query_siswa);
        $stmt_siswa->bindParam(':nama', $_POST['nama']);
        $stmt_siswa->bindParam(':nisn', $_POST['nisn']);
        $stmt_siswa->bindParam(':nis', $_POST['nis']);
        $stmt_siswa->bindParam(':kelas', $_POST['kelas']);
        $stmt_siswa->bindParam(':tanggal_lahir', $_POST['tanggal_lahir']);
        $stmt_siswa->bindParam(':jenis_kelamin', $_POST['jenis_kelamin']);
        $stmt_siswa->bindParam(':telepon', $_POST['telepon']);
        $stmt_siswa->bindParam(':email', $_POST['email']);
        $stmt_siswa->bindParam(':alamat', $_POST['alamat']);
        $stmt_siswa->bindParam(':asal_sekolah', $_POST['asal_sekolah']);
        $stmt_siswa->bindParam(':status', $_POST['status']);
        $stmt_siswa->bindParam(':id_siswa', $_POST['id_siswa']);
        
        $siswa_updated = $stmt_siswa->execute();
        
        // Update or insert orangtua table
        $query_check_orangtua = "SELECT id_siswa FROM orangtua WHERE id_siswa = :id_siswa";
        $stmt_check = $db->prepare($query_check_orangtua);
        $stmt_check->bindParam(':id_siswa', $_POST['id_siswa']);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            // Update existing record
            $query_orangtua = "UPDATE orangtua SET 
                              nama_ayah = :nama_ayah, pekerjaan_ayah = :pekerjaan_ayah,
                              nama_ibu = :nama_ibu, pekerjaan_ibu = :pekerjaan_ibu
                              WHERE id_siswa = :id_siswa";
        } else {
            // Insert new record
            $query_orangtua = "INSERT INTO orangtua (id_siswa, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu)
                              VALUES (:id_siswa, :nama_ayah, :pekerjaan_ayah, :nama_ibu, :pekerjaan_ibu)";
        }
        
        $stmt_orangtua = $db->prepare($query_orangtua);
        $stmt_orangtua->bindParam(':id_siswa', $_POST['id_siswa']);
        $stmt_orangtua->bindParam(':nama_ayah', $_POST['nama_ayah']);
        $stmt_orangtua->bindParam(':pekerjaan_ayah', $_POST['pekerjaan_ayah']);
        $stmt_orangtua->bindParam(':nama_ibu', $_POST['nama_ibu']);
        $stmt_orangtua->bindParam(':pekerjaan_ibu', $_POST['pekerjaan_ibu']);
        
        $orangtua_updated = $stmt_orangtua->execute();
        
        if ($siswa_updated && $orangtua_updated) {
            echo json_encode(['success' => true, 'message' => 'Data berhasil diupdate']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate data']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Get student data
$query = "SELECT s.*, o.nama_ayah, o.pekerjaan_ayah, o.nama_ibu, o.pekerjaan_ibu 
          FROM siswa s 
          LEFT JOIN orangtua o ON s.id_siswa = o.id_siswa 
          WHERE s.id_siswa = :id_siswa";

$stmt = $db->prepare($query);
$stmt->bindParam(':id_siswa', $id_siswa);
$stmt->execute();

$siswa = safeFetch($stmt);
$username = safeGet($siswa, 'nama', 'Siswa');
$role = 'siswa';

// Check if student data found
$dataFound = !empty($siswa);
if (!$dataFound) {
    $siswa = [
        'id_siswa' => $id_siswa,
        'nama' => 'Data tidak ditemukan',
        'nisn' => '',
        'nis' => '',
        'kelas' => '',
        'tanggal_lahir' => '',
        'jenis_kelamin' => '',
        'telepon' => '',
        'email' => '',
        'alamat' => '',
        'asal_sekolah' => '',
        'status' => 'aktif',
        'nama_ayah' => '',
        'pekerjaan_ayah' => '',
        'nama_ibu' => '',
        'pekerjaan_ibu' => ''
    ];
}

// Generate initials for avatar
$initials = '';
if (!empty($siswa['nama'])) {
    $nameParts = explode(' ', $siswa['nama']);
    foreach ($nameParts as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($initials) >= 2) break;
    }
}
if ($initials === '') $initials = 'U';

//untuk upload foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_foto']) && isset($_FILES['foto'])) {
    $foto = $_FILES['foto'];
    if ($foto['error'] === 0) {
        $ext = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $newName = 'foto_' . $id_siswa . '_' . time() . '.' . $ext;
        $target = 'uploads/foto/' . $newName;
        if (!is_dir('uploads/foto')) mkdir('uploads/foto', 0777, true);
        if (move_uploaded_file($foto['tmp_name'], $target)) {
            // Simpan path ke database
            $stmt = $db->prepare("UPDATE siswa SET foto = :foto WHERE id_siswa = :id_siswa");
            $stmt->bindParam(':foto', $target);
            $stmt->bindParam(':id_siswa', $id_siswa);
            $stmt->execute();
            // Redirect agar foto langsung tampil
            header("Location: akun_siswa.php");
            exit;
        } else {
            echo "<div class='alert alert-danger'>Gagal upload foto!</div>";
        }
    }
}   
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Siswa - Manajemen Data Siswa</title>
   <link rel="stylesheet" href="akun_siswa.css">
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
  


        <!-- Main Content -->
        <div class="main">
            <!-- Header -->
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
            
      <!-- Content Card -->
      <div class="content-card">
          <button class="edit-btn" onclick="openEditModal()">Edit data</button>
          
          <!-- Profile Section -->
          <div class="profile-section">
              <div class="profile-image">
                                         <div class="profile-avatar" id="profileAvatar" style="background-image: url('<?php echo !empty($siswa['foto']) ? $siswa['foto'] : ''; ?>');background-size: cover; background-position: center;"> <?php if (empty($siswa['foto'])) echo $initials; ?> </div>

                     <form class="upload-btn" id="uploadFotoForm" method="post" enctype="multipart/form-data" style="margin-top:10px;"> 
                    <input type="file" name="foto" accept="image/*" required>
  <button type="submit" name="upload_foto">Upload Foto</button>
</form>   
              </div>
              
              <div class="profile-info">
                  <div class="profile-header">
                      <div class="profile-name" id="studentName"><?php echo safeGet($siswa, 'nama'); ?></div>
                      <div class="profile-id">NISN: <span id="studentNisn"><?php echo safeGet($siswa, 'nisn'); ?></span></div>
                      <div class="profile-id">NIS: <span id="studentNis"><?php echo safeGet($siswa, 'nis'); ?></span></div>
                      <div class="profile-class"><?php echo safeGet($siswa, 'kelas'); ?></div>
                      <div class="status-badge" id="statusBadge" style="background: <?php echo $siswa['status'] === 'aktif' ? '#4CAF50' : ($siswa['status'] === 'lulus' ? '#2196F3' : '#f44336'); ?>">
                          <?php echo strtoupper($siswa['status']); ?>
                      </div>
                  </div>
                  
                  <div class="info-grid">
                      <div class="info-item">
                          <div class="info-label">Nama Lengkap</div>
                          <div class="info-value" id="namaLengkap"><?php echo safeGet($siswa, 'nama'); ?></div>
                      </div>
                      <div class="info-item">
                          <div class="info-label">NISN</div>
                          <div class="info-value" id="nisn"><?php echo safeGet($siswa, 'nisn'); ?></div>
                      </div>
                      <div class="info-item">
                          <div class="info-label">NIS</div>
                          <div class="info-value" id="nis"><?php echo safeGet($siswa, 'nis'); ?></div>
                      </div>
                      <div class="info-item">
                          <div class="info-label">alamat</div>
                          <div class="info-value" id="alamat"><?php echo safeGet($siswa, 'alamat'); ?></div>
                      </div>
                      <div class="info-item">
                          <div class="info-label">Tanggal Lahir</div>
                          <div class="info-value" id="tanggalLahir"><?php echo safeGet($siswa, 'tanggal_lahir'); ?></div>
                      </div>
                      <div class="info-item">
                          <div class="info-label">Jenis Kelamin</div>
                          <div class="info-value" id="jenisKelamin"><?php echo safeGet($siswa, 'jenis_kelamin'); ?></div>
                      </div>
                      <div class="info-item">
                          <div class="info-label">No. Telepon</div>
                          <div class="info-value" id="telepon"><?php echo safeGet($siswa, 'telepon'); ?></div>
                      </div>
                      <div class="info-item">
                          <div class="info-label">Email</div>
                          <div class="info-value" id="email"><?php echo safeGet($siswa, 'email'); ?></div>
                      </div>
                      <div class="info-item">
                          <div class="info-label">Asal Sekolah</div>
                          <div class="info-value" id="asalSekolah"><?php echo safeGet($siswa, 'asal_sekolah'); ?></div>
                      </div>
                  </div>
              </div>
          </div>
          
          <!-- Parent Section -->
          <div class="parent-section">
              <div class="section-title">Orang Tua/Wali</div>
              <div class="parent-grid">
                  <div class="info-item">
                      <div class="info-label">Nama Ayah</div>
                      <div class="info-value" id="namaAyah"><?php echo safeGet($siswa, 'nama_ayah'); ?></div>
                  </div>
                  <div class="info-item">
                      <div class="info-label">Pekerjaan Ayah</div>
                      <div class="info-value" id="pekerjaanAyah"><?php echo safeGet($siswa, 'pekerjaan_ayah'); ?></div>
                  </div>
                  <div class="info-item">
                      <div class="info-label">Nama Ibu</div>
                      <div class="info-value" id="namaIbu"><?php echo safeGet($siswa, 'nama_ibu'); ?></div>
                  </div>
                  <div class="info-item">
                      <div class="info-label">Pekerjaan Ibu</div>
                      <div class="info-value" id="pekerjaanIbu"><?php echo safeGet($siswa, 'pekerjaan_ibu'); ?></div>
                  </div>
              </div>
          </div>
      </div>
  </div>
</div>

            </div>
            
            <!-- Alert Messages -->
            <div id="alertContainer"></div>

    
    
    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h3>Edit Data Siswa</h3>
            <form id="editForm">
                <input type="hidden" id="editIdSiswa" value="<?php echo $siswa['id_siswa']; ?>" name="id_siswa">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" id="editNama" value="<?php echo safeGet($siswa, 'nama'); ?>" name="nama" required>
                </div>
                <div class="form-group">
                    <label>NISN</label>
                    <input type="text" id="editNisn" value="<?php echo safeGet($siswa, 'nisn'); ?>" name="nisn" required>
                </div>
                <div class="form-group">
                    <label>NIS</label>
                    <input type="text" id="editNis" value="<?php echo safeGet($siswa, 'nis'); ?>" name="nis" required>
                </div>
                <div class="form-group">
                    <label>Kelas</label>
                    <input type="text" id="editKelas" value="<?php echo safeGet($siswa, 'kelas'); ?>" name="kelas" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" id="editTanggalLahir" value="<?php echo safeGet($siswa, 'tanggal_lahir'); ?>" name="tanggal_lahir" required>
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select id="editJenisKelamin" name="jenis_kelamin" required>
                        <option value="Laki-laki" <?php echo $siswa['jenis_kelamin'] === 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php echo $siswa['jenis_kelamin'] === 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" id="editTelepon" value="<?php echo safeGet($siswa, 'telepon'); ?>" name="telepon" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="editEmail" value="<?php echo safeGet($siswa, 'email'); ?>" name="email" required>
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <input type="text" id="editAlamat" value="<?php echo safeGet($siswa, 'alamat'); ?>" name="alamat" required>
                </div>
                <div class="form-group">
                    <label>Asal Sekolah</label>
                    <input type="text" id="editAsalSekolah" value="<?php echo safeGet($siswa, 'asal_sekolah'); ?>" name="asal_sekolah" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="editStatus" name="status" required>
                        <option value="aktif" <?php echo $siswa['status'] === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="lulus" <?php echo $siswa['status'] === 'lulus' ? 'selected' : ''; ?>>Lulus</option>
                        <option value="keluar" <?php echo $siswa['status'] === 'keluar' ? 'selected' : ''; ?>>Keluar</option>
                    </select>
                </div>
                
                <h4 style="margin-top: 20px;">Data Orang Tua</h4>
                <div class="form-group">
                    <label>Nama Ayah</label>
                    <input type="text" id="editNamaAyah" value="<?php echo safeGet($siswa, 'nama_ayah'); ?>" name="nama_ayah">
                </div>
                <div class="form-group">
                    <label>Pekerjaan Ayah</label>
                    <input type="text" id="editPekerjaanAyah" value="<?php echo safeGet($siswa, 'pekerjaan_ayah'); ?>" name="pekerjaan_ayah">
                </div>
                <div class="form-group">
                    <label>Nama Ibu</label>
                    <input type="text" id="editNamaIbu" value="<?php echo safeGet($siswa, 'nama_ibu'); ?>" name="nama_ibu">
                </div>
                <div class="form-group">
                    <label>Pekerjaan Ibu</label>
                    <input type="text" id="editPekerjaanIbu" value="<?php echo safeGet($siswa, 'pekerjaan_ibu'); ?>" name="pekerjaan_ibu">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                     <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
  
<script>
document.addEventListener('DOMContentLoaded', function() {

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
    
    // Logout confirmation
    document.querySelectorAll('.logout-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin logout?')) {
                e.preventDefault();
            }
        });
    });

    // Data siswa dari PHP ke JavaScript
    let currentStudent = <?php echo json_encode($siswa); ?>;

    function showPage(page) {
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.remove('active');
        });
        event.target.closest('.menu-item').classList.add('active');
        if (page.includes('.php')) {
            window.location.href = page;
        } else {
            alert('Navigasi ke halaman: ' + page);
        }
    }

    function toggleUserMenu() {
        alert('User menu - implementasi dropdown menu');
    }

    function uploadImage() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatar = document.getElementById('profileAvatar');
                    avatar.style.backgroundImage = `url(${e.target.result})`;
                    avatar.style.backgroundSize = 'cover';
                    avatar.style.backgroundPosition = 'center';
                    avatar.innerHTML = '';
                };
                reader.readAsDataURL(file);
            }
        };
        input.click();
    }

    window.openEditModal = function() {
        document.getElementById('editModal').style.display = 'block';
    }

    window.closeEditModal = function() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Handle form submission with AJAX
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'update_student');
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            showAlert(data.success ? 'success' : 'danger', data.message);
            if (data.success) {
                // Update currentStudent object
                currentStudent.nama = document.getElementById('editNama').value;
                currentStudent.nisn = document.getElementById('editNisn').value;
                currentStudent.nis = document.getElementById('editNis').value;
                currentStudent.kelas = document.getElementById('editKelas').value;
                currentStudent.tanggal_lahir = document.getElementById('editTanggalLahir').value;
                currentStudent.jenis_kelamin = document.getElementById('editJenisKelamin').value;
                currentStudent.telepon = document.getElementById('editTelepon').value;
                currentStudent.email = document.getElementById('editEmail').value;
                currentStudent.alamat = document.getElementById('editAlamat').value;
                currentStudent.asal_sekolah = document.getElementById('editAsalSekolah').value;
                currentStudent.status = document.getElementById('editStatus').value;
                currentStudent.nama_ayah = document.getElementById('editNamaAyah').value;
                currentStudent.pekerjaan_ayah = document.getElementById('editPekerjaanAyah').value;
                currentStudent.nama_ibu = document.getElementById('editNamaIbu').value;
                currentStudent.pekerjaan_ibu = document.getElementById('editPekerjaanIbu').value;
                updateDisplay();
                closeEditModal();
            }
        })
        .catch(() => showAlert('danger', 'Terjadi kesalahan saat mengirim data!'));
    });

    function showAlert(type, message) {
        const alertContainer = document.getElementById('alertContainer');
        alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        setTimeout(() => { alertContainer.innerHTML = ''; }, 3000);
    }

    function updateDisplay() {
        document.getElementById('studentName').textContent = currentStudent.nama;
        document.getElementById('namaLengkap').textContent = currentStudent.nama;
        document.getElementById('nisn').textContent = currentStudent.nisn;
        document.getElementById('nis').textContent = currentStudent.nis;
        document.getElementById('alamat').textContent = currentStudent.alamat;
        document.getElementById('tanggalLahir').textContent = formatDate(currentStudent.tanggal_lahir);
        document.getElementById('jenisKelamin').textContent = currentStudent.jenis_kelamin;
        document.getElementById('telepon').textContent = currentStudent.telepon;
        document.getElementById('email').textContent = currentStudent.email;
        document.getElementById('asalSekolah').textContent = currentStudent.asal_sekolah;

        // Update status badge
        const statusBadge = document.getElementById('statusBadge');
        statusBadge.textContent = currentStudent.status.toUpperCase();
        statusBadge.className = 'status-badge';
        if (currentStudent.status === 'aktif') {
            statusBadge.style.background = '#4CAF50';
        } else if (currentStudent.status === 'lulus') {
            statusBadge.style.background = '#2196F3';
        } else {
            statusBadge.style.background = '#f44336';
        }

        // Update parent info
        document.getElementById('namaAyah').textContent = currentStudent.nama_ayah || '-';
        document.getElementById('pekerjaanAyah').textContent = currentStudent.pekerjaan_ayah || '-';
        document.getElementById('namaIbu').textContent = currentStudent.nama_ibu || '-';
        document.getElementById('pekerjaanIbu').textContent = currentStudent.pekerjaan_ibu || '-';

        // Update avatar initials jika tidak ada gambar
        const avatar = document.getElementById('profileAvatar');
        if (!avatar.style.backgroundImage) {
            let initials = '';
            if (currentStudent.nama) {
                initials = currentStudent.nama.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            }
            avatar.textContent = initials || 'U';
        }
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        return `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getFullYear()}`;
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) {
            closeEditModal();
        }
    }

    // Sidebar mobile toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    if(menuToggle && sidebar && overlay) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }

    // Inisialisasi tampilan
    updateDisplay();
});
</script>
</body>
</html>