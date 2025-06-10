<?php
// filepath: c:\xampp\htdocs\hifumi\detail_akun.php
session_start();
$conn = new mysqli("localhost", "root", "", "mandis");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Cek login dan role admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Ambil id_akun dari GET
$id_akun = isset($_GET['id_akun']) ? intval($_GET['id_akun']) : 0;
if ($id_akun <= 0) {
    echo "ID akun tidak valid.";
    exit();
}

// PERBAIKAN 1: Query yang sudah diperbaiki (hapus duplikasi telepon)
$query = "SELECT a.*, 
                 s.status, s.nis, s.nama AS nama_siswa, s.kelas, s.jenis_kelamin, 
                 s.tanggal_lahir, s.alamat, s.telepon, s.email as email_siswa, s.id_siswa,
                 g.nama AS nama_guru, g.jabatan,
                 o.nama_ayah, o.pekerjaan_ayah, o.nama_ibu, o.pekerjaan_ibu, o.id_orangtua
          FROM akun a
          LEFT JOIN siswa s ON a.id_akun = s.id_akun
          LEFT JOIN guru g ON a.id_akun = g.id_akun
          LEFT JOIN orangtua o ON s.id_siswa = o.id_siswa
          WHERE a.id_akun = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_akun);
$stmt->execute();
$result = $stmt->get_result();
$akun = $result->fetch_assoc();
$stmt->close();

if (!$akun) {
    echo "Akun tidak ditemukan.";
    exit();
}

// PROSES FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        // Update akun
        $stmt = $conn->prepare("UPDATE akun SET username=?, email=? WHERE id_akun=?");
        $stmt->bind_param("ssi", $username, $email, $id_akun);
        $stmt->execute();
        $stmt->close();

        if ($akun['role'] == 'siswa') {
            $nama = trim($_POST['nama']);
            $kelas = trim($_POST['kelas']);
            $status = trim($_POST['status']);
            $jenis_kelamin = trim($_POST['jenis_kelamin']);
            $tanggal_lahir = trim($_POST['tanggal_lahir']);
            $alamat = trim($_POST['alamat']);
            $telepon = trim($_POST['telepon']);
            $email_siswa = trim($_POST['email_siswa']);
            
            // Update siswa
            $stmt = $conn->prepare("UPDATE siswa SET nama=?, kelas=?, status=?, jenis_kelamin=?, tanggal_lahir=?, alamat=?, telepon=?, email=? WHERE id_akun=?");
            $stmt->bind_param("ssssssssi", $nama, $kelas, $status, $jenis_kelamin, $tanggal_lahir, $alamat, $telepon, $email_siswa, $id_akun);
            $stmt->execute();
            $stmt->close();

            // Update atau insert data orangtua
            $nama_ayah = trim($_POST['nama_ayah']);
            $pekerjaan_ayah = trim($_POST['pekerjaan_ayah']);
            $nama_ibu = trim($_POST['nama_ibu']);
            $pekerjaan_ibu = trim($_POST['pekerjaan_ibu']);
            
            if ($akun['id_orangtua']) {
                // Update orangtua yang sudah ada
                $stmt = $conn->prepare("UPDATE orangtua SET nama_ayah=?, pekerjaan_ayah=?, nama_ibu=?, pekerjaan_ibu=? WHERE id_orangtua=?");
                $stmt->bind_param("ssssi", $nama_ayah, $pekerjaan_ayah, $nama_ibu, $pekerjaan_ibu, $akun['id_orangtua']);
            } else {
                // Insert orangtua baru
                $stmt = $conn->prepare("INSERT INTO orangtua (id_siswa, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $akun['id_siswa'], $nama_ayah, $pekerjaan_ayah, $nama_ibu, $pekerjaan_ibu);
            }
            $stmt->execute();
            $stmt->close();

        } elseif ($akun['role'] == 'guru') {
            $nama = trim($_POST['nama']);
            $jabatan = trim($_POST['jabatan']);
            $stmt = $conn->prepare("UPDATE guru SET nama=?, jabatan=? WHERE id_akun=?");
            $stmt->bind_param("ssi", $nama, $jabatan, $id_akun);
            $stmt->execute();
            $stmt->close();
        }
        
        header("Location: detail_akun.php?id_akun=$id_akun&success=1");
        exit();
        
    } elseif (isset($_POST['hapus'])) {
        // Hapus akun dan data terkait
        if ($akun['role'] == 'siswa') {
            // Hapus data terkait siswa
            if ($akun['id_siswa']) {
                $conn->query("DELETE FROM orangtua WHERE id_siswa=" . $akun['id_siswa']);
                $conn->query("DELETE FROM prestasi WHERE id_siswa=" . $akun['id_siswa']);
                $conn->query("DELETE FROM dokumen WHERE id_siswa=" . $akun['id_siswa']);
            }
            $conn->query("DELETE FROM siswa WHERE id_akun=$id_akun");
        } elseif ($akun['role'] == 'guru') {
            $conn->query("DELETE FROM guru WHERE id_akun=$id_akun");
        }
        $conn->query("DELETE FROM akun WHERE id_akun=$id_akun");
        header("Location: list_akun.php?deleted=1");
        exit();
    }
}

// Ambil prestasi dan dokumen siswa
$prestasi = [];
$dokumen = [];
if ($akun['role'] == 'siswa' && !empty($akun['id_siswa'])) {
    // Prestasi
    $stmt = $conn->prepare("SELECT * FROM prestasi WHERE id_siswa = ?");
    $stmt->bind_param("i", $akun['id_siswa']);
    $stmt->execute();
    $prestasi = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Dokumen
    $stmt = $conn->prepare("SELECT * FROM dokumen WHERE id_siswa = ?");
    $stmt->bind_param("i", $akun['id_siswa']);
    $stmt->execute();
    $dokumen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// PERBAIKAN 3: Perbaiki role checking untuk CRUD prestasi
// Tambah Prestasi - hanya admin yang bisa
if (isset($_POST['tambah_prestasi']) && $_SESSION['role'] == 'admin') {
    $nama_prestasi = trim($_POST['nama_prestasi']);
    $jenis = trim($_POST['jenis']);
    $tingkat = trim($_POST['tingkat']);
    $penyelenggara = trim($_POST['penyelenggara']);
    $tanggal = trim($_POST['tanggal']);
    $keterangan = trim($_POST['keterangan']);
    $file_path = trim($_POST['file_path']);
    
    $stmt = $conn->prepare("INSERT INTO prestasi (id_siswa, nama_prestasi, jenis, tingkat, penyelenggara, tanggal, keterangan, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $akun['id_siswa'], $nama_prestasi, $jenis, $tingkat, $penyelenggara, $tanggal, $keterangan, $file_path);
    $stmt->execute();
    $stmt->close();
    header("Location: detail_akun.php?id_akun=$id_akun");
    exit();
}

// Edit Prestasi - hanya admin yang bisa
if (isset($_POST['edit_prestasi']) && $_SESSION['role'] == 'admin') {
    $id_prestasi = intval($_POST['id_prestasi']);
    $nama_prestasi = trim($_POST['nama_prestasi']);
    $jenis = trim($_POST['jenis']);
    $tingkat = trim($_POST['tingkat']);
    $penyelenggara = trim($_POST['penyelenggara']);
    $tanggal = trim($_POST['tanggal']);
    $keterangan = trim($_POST['keterangan']);
    $file_path = trim($_POST['file_path']);
    
    $stmt = $conn->prepare("UPDATE prestasi SET nama_prestasi=?, jenis=?, tingkat=?, penyelenggara=?, tanggal=?, keterangan=?, file_path=? WHERE id_prestasi=? AND id_siswa=?");
    $stmt->bind_param("sssssssii", $nama_prestasi, $jenis, $tingkat, $penyelenggara, $tanggal, $keterangan, $file_path, $id_prestasi, $akun['id_siswa']);
    $stmt->execute();
    $stmt->close();
    header("Location: detail_akun.php?id_akun=$id_akun");
    exit();
}

// Hapus Prestasi - hanya admin yang bisa
if (isset($_POST['hapus_prestasi']) && $_SESSION['role'] == 'admin') {
    $id_prestasi = intval($_POST['id_prestasi']);
    $stmt = $conn->prepare("DELETE FROM prestasi WHERE id_prestasi=? AND id_siswa=?");
    $stmt->bind_param("ii", $id_prestasi, $akun['id_siswa']);
    $stmt->execute();
    $stmt->close();
    header("Location: detail_akun.php?id_akun=$id_akun");
    exit();
}

// Tambah Dokumen - hanya admin yang bisa
if (isset($_POST['tambah_dokumen']) && $_SESSION['role'] == 'admin') {
    $jenis_dokumen = trim($_POST['jenis_dokumen']);
    $file_path = trim($_POST['file_path']);
    
    $stmt = $conn->prepare("INSERT INTO dokumen (id_siswa, jenis_dokumen, file_path) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $akun['id_siswa'], $jenis_dokumen, $file_path);
    $stmt->execute();
    $stmt->close();
    header("Location: detail_akun.php?id_akun=$id_akun");
    exit();
}

// Edit Dokumen - hanya admin yang bisa
if (isset($_POST['edit_dokumen']) && $_SESSION['role'] == 'admin') {
    $id_dokumen = intval($_POST['id_dokumen']);
    $jenis_dokumen = trim($_POST['jenis_dokumen']);
    $file_path = null;

    // Cek jika ada file diupload
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
        $new_name = 'uploads/dokumen_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        move_uploaded_file($_FILES['file_upload']['tmp_name'], $new_name);
        $file_path = $new_name;
    }

    if ($file_path) {
        $stmt = $conn->prepare("UPDATE dokumen SET jenis_dokumen=?, file_path=? WHERE id_dokumen=? AND id_siswa=?");
        $stmt->bind_param("ssii", $jenis_dokumen, $file_path, $id_dokumen, $akun['id_siswa']);
    } else {
        $stmt = $conn->prepare("UPDATE dokumen SET jenis_dokumen=? WHERE id_dokumen=? AND id_siswa=?");
        $stmt->bind_param("sii", $jenis_dokumen, $id_dokumen, $akun['id_siswa']);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: detail_akun.php?id_akun=$id_akun");
    exit();
}

// Hapus Dokumen - hanya admin yang bisa
if (isset($_POST['hapus_dokumen']) && $_SESSION['role'] == 'admin') {
    $id_dokumen = intval($_POST['id_dokumen']);
    $stmt = $conn->prepare("DELETE FROM dokumen WHERE id_dokumen=? AND id_siswa=?");
    $stmt->bind_param("ii", $id_dokumen, $akun['id_siswa']);
    $stmt->execute();
    $stmt->close();
    header("Location: detail_akun.php?id_akun=$id_akun");
    exit();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Akun - Sistem Manajemen</title>
    <link rel="stylesheet" href="detail_akun.css">
</head>
<body>
    <div class="container">
        <!-- Mobile Header -->
        <div class="mobile-header">
            <div class="logo-container">
                <img src="./img/logos.png" alt="Logo Sekolah" class="logo">                
                <h3>Manajemen<br>Data Siswa</h3>
            </div>
            <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        </div>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="./img/logos.png" alt="Logo Sekolah" class="logo">   
                <h3>Manajemen<br>Data Siswa</h3>
            </div>
            <a href="beranda_admin.php">Beranda</a>
            <a href="list_akun.php">List Akun</a>
            <a href="akun_admin.php">akun</a>
        </div>

        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar -->
            <div class="topbar">
                <div class="content-header">
                    <h1>Detail Akun</h1>
                </div>
                <div class="dropdown">
                    <div class="profile" onclick="toggleDropdown()">
                        <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                        </div>
                        <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                        <span>▼</span>
                    </div>
                    <div class="dropdown-content" id="dropdownContent">
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="logout-btn">Logout</a>
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="success-message">
                    Data akun berhasil diperbarui!
                </div>
            <?php endif; ?>

            <!-- Detail Akun -->
            <div class="detail-card">
                <h2>👤 Informasi Akun</h2>
                <div class="table-container">
                    <table>
                        <tr>
                            <th style="width: 200px;">Field</th>
                            <th>Value</th>
                        </tr>
                        <tr>
                            <td><strong>ID Akun</strong></td>
                            <td><?= htmlspecialchars($akun['id_akun']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Username</strong></td>
                            <td><?= htmlspecialchars($akun['username']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email</strong></td>
                            <td><?= htmlspecialchars($akun['email']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Role</strong></td>
                            <td>
                                <span class="role-badge role-<?= $akun['role'] ?>">
                                    <?= ucfirst(htmlspecialchars($akun['role'])) ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Detail Data Siswa (jika role siswa) -->
            <?php if ($akun['role'] == 'siswa'): ?>
            <div class="detail-card">
                <h2>🎓 Informasi Siswa</h2>
                <div class="table-container">
                    <table>
                        <tr>
                            <th style="width: 200px;">Field</th>
                            <th>Value</th>
                        </tr>
                        <tr>
                            <td><strong>Nama</strong></td>
                            <td><?= htmlspecialchars($akun['nama_siswa'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>NIS</strong></td>
                            <td><?= htmlspecialchars($akun['nis'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Kelas</strong></td>
                            <td><?= htmlspecialchars($akun['kelas'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Status</strong></td>
                            <td>
                                <span class="status-badge status-<?= $akun['status'] ?>">
                                    <?= ucfirst(htmlspecialchars($akun['status'] ?? '-')) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Jenis Kelamin</strong></td>
                            <td><?= htmlspecialchars($akun['jenis_kelamin'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Lahir</strong></td>
                            <td><?= htmlspecialchars($akun['tanggal_lahir'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Alamat</strong></td>
                            <td><?= htmlspecialchars($akun['alamat'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>No. Telepon</strong></td>
                            <td><?= htmlspecialchars($akun['telepon'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email Siswa</strong></td>
                            <td><?= htmlspecialchars($akun['email_siswa'] ?? '-') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Detail Data Orangtua -->
            <div class="detail-card">
                <h2>👨‍👩‍👧‍👦 Informasi Orangtua</h2>
                <div class="table-container">
                    <table>
                        <tr>
                            <th style="width: 200px;">Field</th>
                            <th>Value</th>
                        </tr>
                        <tr>
                            <td><strong>Nama Ayah</strong></td>
                            <td><?= htmlspecialchars($akun['nama_ayah'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Pekerjaan Ayah</strong></td>
                            <td><?= htmlspecialchars($akun['pekerjaan_ayah'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Nama Ibu</strong></td>
                            <td><?= htmlspecialchars($akun['nama_ibu'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Pekerjaan Ibu</strong></td>
                            <td><?= htmlspecialchars($akun['pekerjaan_ibu'] ?? '-') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- PERBAIKAN 4: Perbaiki tabel prestasi dengan field yang benar -->
            <div class="detail-card">
                <h2>🏆 Prestasi Siswa</h2>
                <div class="table-container">
                    <table>
                        <tr>
                            <th>Nama Prestasi</th>
                            <th>Jenis</th>
                            <th>Tingkat</th>
                            <th>Penyelenggara</th>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>File Path</th>
                            <th>Aksi</th>
                        </tr>
                        <?php foreach ($prestasi as $p): ?>
                        <tr>
                            <form method="post">
                                <td><input type="text" name="nama_prestasi" value="<?= htmlspecialchars($p['nama_prestasi']) ?>"></td>
                                <td><input type="text" name="jenis" value="<?= htmlspecialchars($p['jenis'] ?? '') ?>"></td>
                                <td><input type="text" name="tingkat" value="<?= htmlspecialchars($p['tingkat'] ?? '') ?>"></td>
                                <td><input type="text" name="penyelenggara" value="<?= htmlspecialchars($p['penyelenggara'] ?? '') ?>"></td>
                                <td><input type="date" name="tanggal" value="<?= htmlspecialchars($p['tanggal'] ?? '') ?>"></td>
                                <td><input type="text" name="keterangan" value="<?= htmlspecialchars($p['keterangan'] ?? '') ?>"></td>
                                <td><input type="text" name="file_path" value="<?= htmlspecialchars($p['file_path'] ?? '') ?>"></td>
                                <td>
                                    <input type="hidden" name="id_prestasi" value="<?= $p['id_prestasi'] ?>">
                                    <button class="btn btn-edit" type="submit" name="edit_prestasi">Edit</button>
                                    <button class="btn btn-delete" type="submit" name="hapus_prestasi" onclick="return confirm('Hapus prestasi ini?')">Hapus</button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                        <!-- Form tambah prestasi -->
                        <tr>
                            <form method="post">
                                <td><input type="text" name="nama_prestasi" placeholder="Nama Prestasi" required></td>
                                <td><input type="text" name="jenis" placeholder="Jenis"></td>
                                <td><input type="text" name="tingkat" placeholder="Tingkat"></td>
                                <td><input type="text" name="penyelenggara" placeholder="Penyelenggara"></td>
                                <td><input type="date" name="tanggal"></td>
                                <td><input type="text" name="keterangan" placeholder="Keterangan"></td>
                                <td><input type="text" name="file_path" placeholder="File Path"></td>
                                <td><button class="btn btn-add" type="submit" name="tambah_prestasi">Tambah</button></td>
                            </form>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- PERBAIKAN 5: Perbaiki tabel dokumen -->
            <div class="detail-card">
                <h2>📄 Dokumen Siswa</h2>
                <div class="table-container">
                    <table>
                        <tr>
                            <th>ID Dokumen</th>
                            <th>Tanggal Upload</th>
                            <th>Jenis Dokumen</th>
                            <th>File Path</th>
                            <th>Aksi</th>
                        </tr>
                        <?php foreach ($dokumen as $d): ?>
                        <tr>
                            <form method="post">
                                <td><?= htmlspecialchars($d['id_dokumen']) ?></td>
                                <td><?= htmlspecialchars($d['tanggal_upload'] ?? '') ?></td>
                                <td><input type="text" name="jenis_dokumen" value="<?= htmlspecialchars($d['jenis_dokumen']) ?>"></td>
                                <td><input type="text" name="file_path" value="<?= htmlspecialchars($d['file_path']) ?>"></td>
                                <td>
                                <?php  if (!empty($d['file_path'])): ?>
        <a href="<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="btn btn-view">Lihat</a>
    <?php else: ?>
        <span style="color:#aaa;">Tidak ada file</span>
    <?php endif; ?>
    <input type="hidden" name="id_dokumen" value="<?= $d['id_dokumen'] ?>">
    <button class="btn btn-edit" type="button"
        onclick="openEditDokumenModal(
            <?= $d['id_dokumen'] ?>,
            '<?= htmlspecialchars(addslashes($d['jenis_dokumen'])) ?>',
            '<?= htmlspecialchars(addslashes($d['file_path'])) ?>')">Edit</button>
    <button class="btn  btn-delete" type="submit" name="hapus_dokumen" onclick="return confirm('Hapus dokumen ini?')">Hapus</button>
</td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                        <!-- Form tambah dokumen -->
                        <tr>
                            <form method="post">
                                <td>-</td>
                                <td>-</td>
                                <td><input type="text" name="jenis_dokumen" placeholder="Jenis Dokumen" required></td>
                                <td><input type="text" name="file_path" placeholder="File Path" required></td>
                                <td><button class="btn btn-add" type="submit" name="tambah_dokumen">Tambah</button></td>
                            </form>
                        </tr>
                    </table>
                </div>
            </div>

            <?php endif; ?>

  <!-- Modal Edit Dokumen -->
<div id="editDokumenModal" class="modal" style="display:none;position:fixed;z-index:999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;">
    <div style="background:#fff;padding:24px 20px;border-radius:8px;max-width:400px;width:90%;position:relative;">
        <h3>Edit Dokumen</h3>
        <form method="post" id="editDokumenForm" enctype="multipart/form-data">
            <input type="hidden" name="id_dokumen" id="edit_id_dokumen">
            <div class="form-group">
                <label for="edit_jenis_dokumen">Jenis Dokumen:</label>
                <input type="text" name="jenis_dokumen" id="edit_jenis_dokumen" required>
            </div>
            <div class="form-group">
                <label for="edit_file_upload">Upload File Baru:</label>
                <input type="file" name="file_upload" id="edit_file_upload" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            </div>
            <div style="margin-top:16px;">
                <button type="submit" name="edit_dokumen" class="btn btn-primary">Simpan</button>
                <button type="button" onclick="closeEditDokumenModal()" class="btn btn-secondary">Batal</button>
            </div>
        </form>
    </div>
</div>
            <!-- Detail Data Guru (jika role guru) -->
            <?php if ($akun['role'] == 'guru'): ?>
            <div class="detail-card">
                <h2>👨‍🏫 Informasi Guru</h2>
                <div class="table-container">
                    <table>
                        <tr>
                            <th style="width: 200px;">Field</th>
                            <th>Value</th>
                        </tr>
                        <tr>
                            <td><strong>Nama</strong></td>
                            <td><?= htmlspecialchars($akun['nama_guru'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Jabatan</strong></td>
                            <td><?= htmlspecialchars($akun['jabatan'] ?? '-') ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Form Edit Akun -->
            <div class="form-container">
                <h3>✏️ Edit Data</h3>
                <form method="post">
                    <input type="hidden" name="id_akun" value="<?= $akun['id_akun'] ?>">
                    
                    <!-- Data Akun -->
                    <fieldset class="form-section">
                        <legend>Informasi Akun</legend>
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($akun['username']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($akun['email']) ?>" required>
                        </div>
                    </fieldset>
                    
                    <!-- BILA AKUN SISWA -->
                    <?php if ($akun['role'] == 'siswa'): ?>
                        <fieldset class="form-section">
                            <legend>Data Siswa</legend>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nama">Nama Lengkap:</label>
                                    <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($akun['nama_siswa'] ?? '') ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="kelas">Kelas:</label>
                                    <input type="text" id="kelas" name="kelas" value="<?= htmlspecialchars($akun['kelas'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="status">Status:</label>
                                    <select id="status" name="status" required>
                                        <option value="aktif" <?= ($akun['status']=='aktif')?'selected':''; ?>>Aktif</option>
                                        <option value="lulus" <?= ($akun['status']=='lulus')?'selected':''; ?>>Lulus</option>
                                        <option value="keluar" <?= ($akun['status']=='keluar')?'selected':''; ?>>Keluar</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="jenis_kelamin">Jenis Kelamin:</label>
                                    <select id="jenis_kelamin" name="jenis_kelamin">
                                        <option value="">Pilih</option>
                                        <option value="L" <?= ($akun['jenis_kelamin']=='L')?'selected':''; ?>>Laki-laki</option>
                                        <option value="P" <?= ($akun['jenis_kelamin']=='P')?'selected':''; ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="tanggal_lahir">Tanggal Lahir:</label>
                                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= htmlspecialchars($akun['tanggal_lahir'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="telepon">No. Telepon:</label>
                                    <input type="tel" id="telepon" name="telepon" value="<?= htmlspecialchars($akun['telepon'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="alamat">Alamat:</label>
                                <textarea id="alamat" name="alamat" rows="3"><?= htmlspecialchars($akun['alamat'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="email_siswa">Email Siswa:</label>
                                <input type="email" id="email_siswa" name="email_siswa" value="<?= htmlspecialchars($akun['email_siswa'] ?? '') ?>">
                            </div>
                        </fieldset>
                        
                        <fieldset class="form-section">
                            <legend>Data Orangtua</legend>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nama_ayah">Nama Ayah:</label>
                                    <input type="text" id="nama_ayah" name="nama_ayah" value="<?= htmlspecialchars($akun['nama_ayah'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="pekerjaan_ayah">Pekerjaan Ayah:</label>
                                    <input type="text" id="pekerjaan_ayah" name="pekerjaan_ayah" value="<?= htmlspecialchars($akun['pekerjaan_ayah'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nama_ibu">Nama Ibu:</label>
                                    <input type="text" id="nama_ibu" name="nama_ibu" value="<?= htmlspecialchars($akun['nama_ibu'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="pekerjaan_ibu">Pekerjaan Ibu:</label>
                                    <input type="text" id="pekerjaan_ibu" name="pekerjaan_ibu" value="<?= htmlspecialchars($akun['pekerjaan_ibu'] ?? '') ?>">
                                </div>
                            </div>
                        </fieldset>
                        
                    <!-- BILA AKUN GURU -->
                    <?php elseif ($akun['role'] == 'guru'): ?>
                        <fieldset class="form-section">
                            <legend>Data Guru</legend>
                            <div class="form-group">
                                <label for="nama">Nama Lengkap:</label>
                                <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($akun['nama_guru'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="jabatan">Jabatan:</label>
                                <input type="text" id="jabatan" name="jabatan" value="<?= htmlspecialchars($akun['jabatan'] ?? '') ?>" required>
                            </div>
                        </fieldset>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <button type="submit" name="edit" class="btn btn-primary">💾 Simpan Perubahan</button>
                        <button type="submit" name="hapus" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus akun ini? Tindakan ini tidak dapat dibatalkan.')">🗑️ Hapus Akun</button>
                        <a href="list_akun.php" class="btn btn-secondary">↩️ Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Help Button -->
    <a href="help.php" class="help">Help</a>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownContent');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.profile') && !event.target.matches('.profile *')) {
                const dropdown = document.getElementById('dropdownContent');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !menuToggle.contains(event.target) && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });

        // edit moadal
        function openEditDokumenModal(id, jenis) {
    document.getElementById('edit_id_dokumen').value = id;
    document.getElementById('edit_jenis_dokumen').value = jenis;
    // Tidak perlu lagi: document.getElementById('edit_file_path').value = file;
    document.getElementById('editDokumenModal').style.display = 'flex';
}
function closeEditDokumenModal() {
    document.getElementById('editDokumenModal').style.display = 'none';
}
// Tutup modal jika klik di luar modal
window.onclick = function(event) {
    var modal = document.getElementById('editDokumenModal');
    if (event.target == modal) {
        closeEditDokumenModal();
    }
}
    </script>
</body>
</html>
