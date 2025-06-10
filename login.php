<?php
session_start();

// Redirect jika sudah login
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'siswa') {
        header("Location: beranda_siswa.php");
    } elseif ($_SESSION['role'] === 'guru') {
        header("Location: beranda_guru.php");
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: beranda_admin.php");
    }
    exit;
}

$error = "";

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Koneksi ke database
    $conn = new mysqli("localhost", "root", "", "mandis");
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    $username = $_POST["username"] ?? '';
    $password = $_POST["password"] ?? '';

    // Cari user berdasarkan username
    $sql = "SELECT * FROM akun WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Cocokkan password (ganti ke password_verify() jika sudah pakai hash)
        if ($password === $user["password"]) {
            // Set session dasar
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["id_akun"] = $user["id_akun"];
            $_SESSION["email"] = $user["email"] ?? '';

            // Jika siswa, ambil id_siswa
            if ($user["role"] === "siswa") {
                $sqlSiswa = "SELECT id_siswa FROM siswa WHERE id_akun = ?";
                $stmtSiswa = $conn->prepare($sqlSiswa);
                $stmtSiswa->bind_param("i", $user["id_akun"]);
                $stmtSiswa->execute();
                $resultSiswa = $stmtSiswa->get_result();
                if ($resultSiswa->num_rows === 1) {
                    $siswa = $resultSiswa->fetch_assoc();
                    $_SESSION["id_siswa"] = $siswa["id_siswa"];
                }
                $stmtSiswa->close();
            }

            // Jika guru, pakai id_guru sebagai id_guru
           if ($user["role"] === "guru") {
    // Ambil id_guru dari tabel guru
    $sqlGuru = "SELECT id_guru FROM guru WHERE id_akun = ?";
    $stmtGuru = $conn->prepare($sqlGuru);
    $stmtGuru->bind_param("i", $user["id_akun"]);
    $stmtGuru->execute();
    $resultGuru = $stmtGuru->get_result();
    if ($resultGuru->num_rows === 1) {
        $guru = $resultGuru->fetch_assoc();
        $_SESSION["id_guru"] = $guru["id_guru"];
    }
    $stmtGuru->close();
}

            // Redirect sesuai role
            if ($user["role"] === "siswa") {
                header("Location: beranda_siswa.php");
            } elseif ($user["role"] === "guru") {
                header("Location: beranda_guru.php");
            } elseif ($user["role"] === "admin") {
                header("Location: beranda_admin.php");
            } else {
                header("Location: beranda_siswa.php"); // fallback
            }
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page</title>
   <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
   <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      font-family: "League Spartan", sans-serif;
      background-image: url(./img/bgr.png);
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      padding: 20px;
    }

    .header {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 30px;
      z-index: 2;
      text-align: center;
    }

    .logo {
      width: 50px;
      height: auto;
      margin-bottom: 10px;
    }

    .judul {
      font-size: 3rem;
      color: #e7e7e7;
      font-weight: 700;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .signup-box {
      width: 100%;
      max-width: 400px;
      display: flex;
      justify-content: center;
    }

    .loginbox {
      position: relative;
      padding: 30px;
      border-radius: 20px;
      width: 100%;
      z-index: 1;
      overflow: hidden;
      min-height: 350px;
    }

    .loginbox::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to right, rgba(34, 28, 28, 0.9), rgba(97, 76, 76, 0.9));
      z-index: -1; 
      border-radius: 20px; 
    }

    .loginbox h2 {
      text-align: center;   
      color: #e7e7e7;  
      font-size: 2rem;
      margin-bottom: 30px;
      font-weight: 600;
    }
    
    .loginbox input,
    .loginbox select {
      width: 100%;
      padding: 12px 15px;
      margin: 10px 0;
      color: #ffffff; 
      background-color: rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .loginbox input:focus,
    .loginbox select:focus {
      outline: none;
      background-color: rgba(255, 255, 255, 0.3);
      border-color: #FFCC33;
      box-shadow: 0 0 10px rgba(255, 204, 51, 0.3);
    }

    .loginbox input::placeholder,
    .loginbox select::placeholder {
      color: rgba(255, 255, 255, 0.8); 
      opacity: 1; 
    }

    .button-container {
      display: flex;
      justify-content: flex-end;
      margin-top: 30px;
    }

    .loginbox button {
      background: #FFCC33;
      border-radius: 10px;
      color: #333;
      border: none;
      cursor: pointer;   
      padding: 12px 30px;
      font-weight: 700;
      font-size: 1rem;
      transition: all 0.3s ease;
      min-width: 120px;
    }

    .loginbox button:hover {
      background: #FFD966;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 204, 51, 0.3);
    }

    .error-message {
      color: #ff6b6b;
      text-align: center;
      margin-bottom: 20px;
      padding: 10px;
      background-color: rgba(255, 107, 107, 0.1);
      border: 1px solid rgba(255, 107, 107, 0.3);
      border-radius: 6px;
      font-weight: 500;
    }

    .login-link {
      position: absolute;
      bottom: 15px;
      left: 15px;
      font-size: 0.875rem;
    }

    .login-link a {
      text-decoration: none;
      color: #e7e7e7;
      transition: color 0.3s ease;
    }

    .login-link a:hover {
      color: #FFCC33;
    }

    /* Media Queries untuk Responsiveness */
    @media (max-width: 768px) {
      body {
        padding: 15px;
      }

      .judul {
        font-size: 2.5rem;
      }

      .loginbox {
        padding: 25px;
        min-height: 320px;
      }

      .loginbox h2 {
        font-size: 1.75rem;
        margin-bottom: 25px;
      }

      .logo {
        width: 45px;
      }

      .button-container {
        justify-content: center;
      }

      .loginbox button {
        width: 100%;
        max-width: 200px;
      }
    }

    @media (max-width: 480px) {
      body {
        padding: 10px;
      }

      .judul {
        font-size: 2rem;
      }

      .loginbox {
        padding: 20px;
        min-height: 300px;
      }

      .loginbox h2 {
        font-size: 1.5rem;
        margin-bottom: 20px;
      }

      .loginbox input,
      .loginbox select {
        padding: 10px 12px;
        font-size: 0.9rem;
      }

      .logo {
        width: 40px;
      }

      .login-link {
        font-size: 0.8rem;
        bottom: 10px;
        left: 10px;
      }

      .loginbox button {
        padding: 10px 25px;
        font-size: 0.9rem;
      }
    }

    @media (max-width: 320px) {
      .judul {
        font-size: 1.75rem;
      }

      .loginbox {
        padding: 15px;
      }

      .loginbox h2 {
        font-size: 1.25rem;
      }

      .logo {
        width: 35px;
      }
    }

    /* Landscape orientation untuk mobile */
    @media (max-height: 600px) and (orientation: landscape) {
      body {
        padding: 10px;
      }

      .header {
        margin-bottom: 15px;
      }

      .judul {
        font-size: 1.5rem;
      }

      .logo {
        width: 35px;
        margin-bottom: 5px;
      }

      .loginbox {
        padding: 20px;
        min-height: auto;
      }

      .loginbox h2 {
        font-size: 1.25rem;
        margin-bottom: 15px;
      }

      .button-container {
        margin-top: 15px;
      }
    }
  </style>
</head>
<body>
  <div class="header">
    <img src="./img/logos.png" class="logo" alt="Logo">
    <h1 class="judul">MANDIS</h1>
  </div>
  
  <div class="signup-box">
    <div class="loginbox">
      <h2>Log in</h2>
      <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <div class="button-container">
          <button type="submit"><strong>Log In</strong></button>
        </div>
      </form>
      <div class="login-link">
        <a href="signin.php">Dont have an account? <br> Click to Register</a>
      </div>
    </div>
  </div>

</body>
</html> 