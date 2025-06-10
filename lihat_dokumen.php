<?php
// Untuk file dokumen.php - perbaikan sederhana
session_start();

// Tambahkan koneksi database
$conn = new mysqli("localhost", "root", "", "mandis");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Cek login
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'Guest';
$role = $_SESSION['role'] ?? 'guest';
$email = $_SESSION['email'] ?? '';

// Generate initials untuk avatar
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

// Inisialisasi variabel
$siswa_data = null;
$orangtua_data = null;
$dokumen_data = [];
$view_mode = ($role === 'guru') ? 'view' : 'edit';

// Tentukan siswa mana yang akan ditampilkan
$target_siswa_id = null;

if ($role === 'siswa') {
    // Jika siswa, ambil id_siswa dari session atau database
    if (!isset($_SESSION['id_siswa'])) {
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
    $target_siswa_id = $_SESSION['id_siswa'] ?? null;
    
} elseif ($role === 'guru') {
    // Jika guru, bisa melihat data siswa berdasarkan parameter atau pilihan
    if (isset($_GET['id_siswa'])) {
        $target_siswa_id = (int)$_GET['id_siswa'];
    } else {
        // Tampilkan daftar siswa untuk dipilih dengan layout yang sama
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Pilih Siswa - Mandis</title>
         <link rel="stylesheet" href="lihat_dokumen.css">
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
                    <h2>Pilih Siswa</h2>
                    <div class="dropdown">
                        <div class="profile" onclick="toggleDropdown()">
                            <div class="user-avatar"><?= $initials ?></div>
                            <span><?= htmlspecialchars($username) ?> (<?= ucfirst($role) ?>)</span>
                            <span style="margin-left: 5px;">▼</span>
                        </div>    
                        <div id="profileDropdown" class="dropdown-content">
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="logout-btn">Logout</a>
                        </div>
                    </div>
                </div>
                
                <div class="data-container">
                    <h3>👥 Daftar Siswa</h3>
                    <div class="siswa-list">
        <?php
        
        // Ambil daftar siswa aktif
        $query = "SELECT id_siswa, nama, kelas, nis FROM siswa ORDER BY nama";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($siswa = $result->fetch_assoc()) {
                echo '<div class="siswa-item">
                        <div class="siswa-info">
                            <h4>' . htmlspecialchars($siswa['nama']) . '</h4>
                            <p>NIS: ' . htmlspecialchars($siswa['nis']) . ' | Kelas: ' . htmlspecialchars($siswa['kelas']) . '</p>
                        </div>
                        <div class="siswa-actions">
                            <a href="lihat_dokumen.php?id_siswa=' . $siswa['id_siswa'] . '" class="btn-view">Lihat Dokumen</a>
                        </div>
                      </div>'   ;
            }
        } else {
            echo '<div class="no-data">Tidak ada siswa aktif.</div>';
        }
        
        ?>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="beranda_guru.php" class="btn-back">← Kembali ke Beranda</a>
                </div>
            </div>
        </div>

        <script>
            function toggleDropdown() {
                document.getElementById("profileDropdown").classList.toggle("show");
            }

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

            document.addEventListener('DOMContentLoaded', function() {
                const menuToggle = document.getElementById('menuToggle');
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('overlay');
                
                if (menuToggle) {
                    menuToggle.addEventListener('click', function() {
                        sidebar.classList.toggle('active');
                        overlay.classList.toggle('active');
                    });
                }
                
                if (overlay) {
                    overlay.addEventListener('click', function() {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                    });
                }
            });
        </script>
        </body>
        </html>
        <?php
        exit();
    }
} else {
    // Role lain (admin) - bisa implementasi sesuai kebutuhan
    $target_siswa_id = isset($_GET['id_siswa']) ? (int)$_GET['id_siswa'] : null;
}

// Ambil data siswa jika ada target_siswa_id
if ($target_siswa_id) {
    // Ambil data siswa
    $query = "SELECT s.*, a.username, a.email as email_akun 
              FROM siswa s 
              JOIN akun a ON s.id_akun = a.id_akun 
              WHERE s.id_siswa = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $target_siswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $siswa_data = $result->fetch_assoc();
    
    // Ambil data orangtua
    $query = "SELECT * FROM orangtua WHERE id_siswa = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $target_siswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $orangtua_data = $result->fetch_assoc();
    
    // Ambil data dokumen
    $query = "SELECT * FROM dokumen WHERE id_siswa = ? ORDER BY tanggal_upload DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $target_siswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dokumen_data = $result->fetch_all(MYSQLI_ASSOC);
}

?>

<!-- halaman selannjutnya -->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa - Mandis</title>
    <link rel="stylesheet" href="lihat_dokumen.css">
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
        <?php if ($role === 'guru'): ?>
            <a href="beranda_guru.php">Beranda</a>
            <a href="lihat_dokumen.php">Lihat Data Siswa</a>
            <a href="akun_guru.php">Akun</a>
        <?php endif; ?>
    </div>

    <!-- Main Content Area -->
    <div class="main">
        <!-- Top Bar with Profile -->
        <div class="topbar">
            <h2><?= ($role === 'guru') ? 'Data Siswa' : 'Data Saya' ?></h2>
            <div class="dropdown">
                <div class="profile" onclick="toggleDropdown()">
                    <div class="user-avatar"><?= $initials ?></div>
                    <span><?= htmlspecialchars($username) ?> (<?= ucfirst($role) ?>)</span>
                    <span style="margin-left: 5px;">▼</span>
                </div>    
                <div id="profileDropdown" class="dropdown-content">
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✅ Dokumen berhasil diupload!
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Tambahkan info jika guru melihat data siswa -->
        <?php if ($role === 'guru' && $siswa_data): ?>
        <div class="alert alert-info">
            <strong>Mode Guru:</strong> Anda sedang melihat data siswa <strong><?= htmlspecialchars($siswa_data['nama']) ?></strong>
            <a href="lihat_dokumen.php" style="margin-left: 10px;">← Pilih Siswa Lain</a>
        </div>
        <?php endif; ?>

        <?php if ($siswa_data): ?>
        <!-- Data Siswa -->
        <div class="data-container">
            <h3>👤 Informasi Siswa</h3>
            <table class="data-table">
                <tr>
                    <td class="label-col">Username</td>
                    <td class="value-col"><?= htmlspecialchars($siswa_data['username'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">NIS</td>
                    <td class="value-col"><?= htmlspecialchars($siswa_data['nis'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Nama Lengkap</td>
                    <td class="value-col"><?= htmlspecialchars($siswa_data['nama'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Kelas</td>
                    <td class="value-col"><?= htmlspecialchars($siswa_data['kelas'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Jenis Kelamin</td>
                    <td class="value-col"><?= htmlspecialchars($siswa_data['jenis_kelamin'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Tanggal Lahir</td>
                    <td class="value-col"><?= htmlspecialchars($siswa_data['tanggal_lahir'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Alamat</td>
                    <td class="value-col"><?= htmlspecialchars($siswa_data['alamat'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">No. Telepon</td>
                    <td class="value-col"><?= htmlspecialchars($siswa_data['no_telepon'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Email</td>
                    <td class="value-col"><?= htmlspecialchars($siswa_data['email'] ?? $siswa_data['email_akun'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Status</td>
                    <td class="value-col">
                        <span class="status-badge status-<?= strtolower($siswa_data['status']) ?>">
                            <?= htmlspecialchars($siswa_data['status'] ?? '-') ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Data Orangtua -->
        <?php if ($orangtua_data): ?>
        <div class="data-container">
            <h3>👨‍👩‍👧‍👦 Informasi Orangtua</h3>
            <table class="data-table">
                <tr>
                    <td class="label-col">Nama Ayah</td>
                    <td class="value-col"><?= htmlspecialchars($orangtua_data['nama_ayah'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Pekerjaan Ayah</td>
                    <td class="value-col"><?= htmlspecialchars($orangtua_data['pekerjaan_ayah'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Nama Ibu</td>
                    <td class="value-col"><?= htmlspecialchars($orangtua_data['nama_ibu'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Pekerjaan Ibu</td>
                    <td class="value-col"><?= htmlspecialchars($orangtua_data['pekerjaan_ibu'] ?? '-') ?></td>
                </tr>
            
            </table>
        </div>
        <?php endif; ?>

        <!-- Form Upload hanya untuk siswa -->
        <?php if ($role === 'siswa' && $target_siswa_id): ?>
        <div class="data-container">
            <h3>📄 Upload Dokumen</h3>
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" class="upload-form">
                <div class="form-group">
                    <label for="jenis_dokumen">Jenis Dokumen:</label>
                    <select name="jenis_dokumen" id="jenis_dokumen" required>
                        <option value="">Pilih Jenis Dokumen</option>
                        <option value="ijazah">Ijazah</option>
                        <option value="sertifikat">Sertifikat</option>
                        <option value="rapor">Rapor</option>
                        <option value="kartu_keluarga">Kartu Keluarga</option>
                        <option value="akta_lahir">Akta Kelahiran</option>
                        <option value="foto">Foto</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="file_dokumen">File Dokumen:</label>
                    <div class="file-upload-area" onclick="document.getElementById('file_dokumen').click()">
                        <div class="file-upload-icon">📁</div>
                        <div class="file-upload-text">Klik untuk memilih file atau drag & drop</div>
                        <div class="file-upload-hint">Format: PDF, JPG, PNG, DOC, DOCX (Max: 5MB)</div>
                    </div>
                    <input type="file" name="file_dokumen" id="file_dokumen" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required style="display: none;">
                    <div id="file-name" style="margin-top: 10px; color: #6c757d; font-style: italic;"></div>
                </div>
                <button type="submit" name="upload_dokumen" class="btn btn-primary">
                    📤 Upload Dokumen
                </button>
            </form>
        </div>
       <?php elseif ($role === 'guru' && $target_siswa_id): ?>
    <div class="data-container">
        <h3>📄 Prestasi Siswa</h3>

        <?php
        // Ambil data prestasi dari database
        $query = "SELECT * FROM prestasi WHERE id_siswa = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $target_siswa_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID SISWA</th>
                        <th>Nama Siswa</th>
                        <th>Nama Prestasi</th>
                        <th>Jenis</th>
                        <th>Tingkat</th>
                        <th>Penyelenggara</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>FILE PATH</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id_siswa']) ?></td>
                            <td><?= htmlspecialchars($siswa_data['nama']) ?></td>
                            <td><?= htmlspecialchars($row['nama_prestasi']) ?></td>
                            <td><?= htmlspecialchars($row['jenis']) ?></td>
                            <td><?= htmlspecialchars($row['tingkat']) ?></td>
                            <td><?= htmlspecialchars($row['penyelenggara']) ?></td>
                            <td><?= htmlspecialchars($row['tanggal']) ?></td>
                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                            <td><?= htmlspecialchars($row['file_path']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Belum ada data prestasi yang ditambahkan.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

        <!-- Daftar Dokumen -->
        <?php if ($target_siswa_id): ?>
        <div class="data-container">
            <h3>📋 Daftar Dokumen</h3>
            <?php if (!empty($dokumen_data)): ?>
                <div class="document-list">
                    <?php foreach ($dokumen_data as $doc): ?>
                        <div class="document-item">
                            <div class="document-info">
                                <h4><?= ucwords(str_replace('_', ' ', htmlspecialchars($doc['jenis_dokumen']))) ?></h4>
                                <p><strong>Tanggal Upload:</strong> <?= date('d/m/Y', strtotime($doc['tanggal_upload'])) ?></p>
                                <p><strong>File:</strong> <?= basename($doc['file_path']) ?></p>
                            </div>
                            <div class="document-actions">
                                <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-download">
                                    📥 Download
                                </a>
                                <?php if ($role === 'siswa'): ?>
                                <a href="hapus_dokumen.php?id=<?= $doc['id_dokumen'] ?>" 
                                   onclick="return confirm('Yakin ingin menghapus dokumen ini?')" 
                                   class="btn btn-delete">
                                    🗑️ Hapus
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    Belum ada dokumen yang diupload
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="data-container">
            <div class="no-data">
                Data siswa tidak ditemukan atau Anda tidak memiliki akses untuk melihat data ini.
            </div>
        </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div style="margin-top: 20px;">
            <?php if ($role === 'siswa'): ?>
                <a href="beranda_siswa.php" class="btn btn-back">← Kembali ke Beranda</a>
            <?php elseif ($role === 'guru'): ?>
                <a href="beranda_guru.php" class="btn btn-back">← Kembali ke Beranda</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Dropdown functionality
    function toggleDropdown() {
        document.getElementById("profileDropdown").classList.toggle("show");
    }

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

    // Mobile menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
        }
        
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }

        // File upload functionality
        const fileInput = document.getElementById('file_dokumen');
        const fileUploadArea = document.querySelector('.file-upload-area');
        const fileName = document.getElementById('file-name');

        if (fileInput && fileUploadArea) {
            // Handle file selection
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    fileName.textContent = 'File dipilih: ' + this.files[0].name;
                } else {
                    fileName.textContent = '';
                }
            });

            // Drag and drop functionality
            fileUploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            fileUploadArea.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });

            fileUploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    fileInput.files = e.dataTransfer.files;
                    fileName.textContent = 'File dipilih: ' + e.dataTransfer.files[0].name;
                }
            });
        }
    });
</script>
</body>
</html>