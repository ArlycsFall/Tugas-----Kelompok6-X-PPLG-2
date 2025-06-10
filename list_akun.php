    <?php
    // Koneksi database
    $conn = new mysqli("localhost", "root", "", "mandis");

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Ambil filter dari GET
    $role_filter = $_GET['role'] ?? 'all';
    $status_filter = $_GET['status'] ?? 'all';

    // Query dasar
    $query = "SELECT a.id_akun, 
                 a.username, 
                 a.email, 
                 a.role, 
                 s.status, 
                 COALESCE(s.nama, g.nama) AS nama
          FROM akun a
          LEFT JOIN siswa s ON a.id_akun = s.id_akun
          LEFT JOIN guru g ON a.id_akun = g.id_akun
          WHERE 1";

    // Filter role
    if ($role_filter == 'guru') {
        $query .= " AND a.role = 'guru'";
    } elseif ($role_filter == 'siswa') {
        $query .= " AND a.role = 'siswa'";
    }

    // Filter status siswa
    if ($role_filter == 'siswa' && $status_filter != 'all') {
        $query .= " AND s.status = '" . $conn->real_escape_string($status_filter) . "'";
    }

    $query .= " ORDER BY a.role, a.username";
    $result = $conn->query($query);
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Akun - MANDIS</title>
    <link rel="stylesheet" href="list_akun.css">
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

        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar -->
            <div class="topbar">
                <div class="content-header">
                    <h1>Manajemen Akun</h1>
                    <p>Kelola akun pengguna sistem</p>
                </div>
                
                <div class="dropdown">
                    <div class="profile" onclick="toggleDropdown()">
                        <div class="user-avatar">A</div>
                        <span>Admin</span>
                        <span>▼</span>
                    </div>
                    <div class="dropdown-content" id="dropdown">
                        <a href="profile.php">Profil</a>
                        <a href="settings.php">Pengaturan</a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="logout-btn">Logout</a>
                    </div>
                </div>
            </div>

            <!-- Form Filter -->
            <form method="get" class="filter-form">
                <label>Role:
                    <select name="role" onchange="this.form.submit()">
                        <option value="all" <?= $role_filter=='all'?'selected':''; ?>>Semua</option>
                        <option value="guru" <?= $role_filter=='guru'?'selected':''; ?>>Guru</option>
                        <option value="siswa" <?= $role_filter=='siswa'?'selected':''; ?>>Siswa</option>
                    </select>
                </label>
                <?php if ($role_filter == 'siswa'): ?>
                <label>Status:
                    <select name="status" onchange="this.form.submit()">
                        <option value="all" <?= $status_filter=='all'?'selected':''; ?>>Semua</option>
                        <option value="aktif" <?= $status_filter=='aktif'?'selected':''; ?>>Aktif</option>
                        <option value="lulus" <?= $status_filter=='lulus'?'selected':''; ?>>Lulus</option>
                        <option value="keluar" <?= $status_filter=='keluar'?'selected':''; ?>>Keluar</option>
                    </select>
                </label>
                <?php endif; ?>
            </form>

            <!-- Tabel Akun -->
            <div class="table-container">
                <table>
                    <tr>
                        <th>No</th>
                        <th>ID Akun</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                    <?php 
                    $no = 1; 
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()): 
                    ?>
                    <tr onclick="window.location.href='detail_akun.php?id_akun=<?= $row['id_akun'] ?>';" style="cursor:pointer;">
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['id_akun']) ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td> <span class="role-badge role-<?= $row['role'] ?>"><?= htmlspecialchars($row['role']) ?></span></td>
                        <td><?php if ($row['role'] == 'siswa' && $row['status']): ?><span class="status-badge status-<?= $row['status'] ?>"><?= htmlspecialchars($row['status']) ?></span> <?php else: ?><span style="color: #a0aec0;">-</span><?php endif; ?> </td>
                        </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo "<tr><td colspan='5' style='text-align: center; color: #a0aec0;'>Tidak ada data</td></tr>";
                    }
                    ?>
                </table>
            </div>
        </div>

        <!-- Help Button -->
        <a href="help.php" class="help">?</a>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        function toggleDropdown() {
            document.getElementById('dropdown').classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.profile') && !event.target.closest('.profile')) {
                var dropdown = document.getElementById('dropdown');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        }
    </script>
</body>
</html>