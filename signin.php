<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mandis");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$message = "";
$guru_token = "GURU2025"; // Ganti dengan token rahasia Anda

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? '';
    $email    = $_POST["email"] ?? '';
    $password = $_POST["password"] ?? '';
    $token    = $_POST["token"] ?? '';

    // Default role
    $role = 'siswa';
    $guru_token = "guwragiwru";//token untuk menjadi guru

    // Jika token benar, role jadi guru
    if ($token === $guru_token) {
        $role = 'guru';
    }

    // $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    //biar nggak ke hash pw nya pake yang bawah
    $passwordHash = $password;

    if ($username && $email && $password) {
        $sql = "INSERT INTO akun (username, password, email, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $passwordHash, $email, $role);

        if ($stmt->execute()) {
            $id_akun_baru = $conn->insert_id;

            if ($role === 'siswa') {
                $sqlSiswa = "INSERT INTO siswa (id_akun) VALUES (?)";
                $stmtSiswa = $conn->prepare($sqlSiswa);
                $stmtSiswa->bind_param("i", $id_akun_baru);
                $stmtSiswa->execute();
                $stmtSiswa->close();
            } elseif ($role === 'guru') {
                $sqlGuru = "INSERT INTO guru (id_akun) VALUES (?)";
                $stmtGuru = $conn->prepare($sqlGuru);
                $stmtGuru->bind_param("i", $id_akun_baru);
                $stmtGuru->execute();
                $stmtGuru->close();
            }

            $message = "Akun berhasil dibuat sebagai $role!";
        } else {
            $message = "Gagal menyimpan akun: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Semua field harus diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up Mandis</title>
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

    .signup-container {
      width: 100%;
      max-width: 400px;
      display: flex;
      justify-content: center;
    }

    .signup-box {
      position: relative;
      padding: 30px;
      border-radius: 20px;
      width: 100%;
      z-index: 1;
      overflow: hidden;
      min-height: 450px;
    }

    .signup-box::before {
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

    .signup-box h2 {
      text-align: center;   
      color: #e7e7e7;  
      font-size: 2rem;
      margin-bottom: 30px;
      font-weight: 600;
    }
    
    .signup-box input,
    .signup-box select {
      width: 100%;
      padding: 12px 15px;
      margin: 8px 0;
      color: #ffffff; 
      background-color: rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .signup-box input:focus,
    .signup-box select:focus {
      outline: none;
      background-color: rgba(255, 255, 255, 0.3);
      border-color: #FFCC33;
      box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
    }

    .signup-box input::placeholder,
    .signup-box select::placeholder {
      color: #ffffff; 
      opacity: 50%; 
    }

    .button-container {
      display: flex;
      justify-content: flex-end;
      margin-top: 20px;
    }

    .signup-box button {
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

    .signup-box button:hover {
      background: #FFD966;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 204, 51, 0.3);
    }

    .message {
      text-align: center;
      margin-bottom: 20px;
      padding: 10px;
      border-radius: 6px;
      font-weight: 500;
    }

    .message.success {
      color: #4CAF50;
      background-color: rgba(76, 175, 80, 0.1);
      border: 1px solid rgba(76, 175, 80, 0.3);
    }

    .message.error {
      color: #ff6b6b;
      background-color: rgba(255, 107, 107, 0.1);
      border: 1px solid rgba(255, 107, 107, 0.3);
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

    .form-group {
      margin-bottom: 8px;
      opacity: 80%;
    }
    
   

    .token-info {
      font-size: 0.8rem;
      color: rgba(255, 255, 255, 0.7);
      margin-top: -5px;
      margin-bottom: 10px;
      font-style: italic;
    }

    /* Media Queries untuk Responsiveness */
    @media (max-width: 768px) {
      body {
        padding: 15px;
      }

      .judul {
        font-size: 2.5rem;
      }

      .signup-box {
        padding: 25px;
        min-height: 420px;
      }

      .signup-box h2 {
        font-size: 1.75rem;
        margin-bottom: 25px;
      }

      .logo {
        width: 45px;
      }

      .button-container {
        justify-content: center;
      }

      .signup-box button {
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

      .signup-box {
        padding: 20px;
        min-height: 400px;
      }

      .signup-box h2 {
        font-size: 1.5rem;
        margin-bottom: 20px;
      }

      .signup-box input,
      .signup-box select {
        padding: 10px 12px;
        font-size: 0.9rem;
        margin: 6px 0;
      }

      .logo {
        width: 40px;
      }

      .login-link {
        font-size: 0.8rem;
        bottom: 10px;
        left: 10px;
      }

      .signup-box button {
        padding: 10px 25px;
        font-size: 0.9rem;
      }

      .token-info {
        font-size: 0.75rem;
      }
    }

    @media (max-width: 320px) {
      .judul {
        font-size: 1.75rem;
      }

      .signup-box {
        padding: 15px;
      }

      .signup-box h2 {
        font-size: 1.25rem;
      }

      .logo {
        width: 35px;
      }
    }

    /* Landscape orientation untuk mobile */
    @media (max-height: 700px) and (orientation: landscape) {
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

      .signup-box {
        padding: 20px;
        min-height: auto;
      }

      .signup-box h2 {
        font-size: 1.25rem;
        margin-bottom: 15px;
      }

      .signup-box input,
      .signup-box select {
        margin: 4px 0;
        padding: 8px 12px;
      }

      .button-container {
        margin-top: 10px;
      }
    }

    /* Untuk layar yang sangat kecil dalam landscape */
    @media (max-height: 500px) and (orientation: landscape) {
      .signup-box {
        min-height: auto;
        padding: 15px;
      }

      .signup-box input,
      .signup-box select {
        margin: 3px 0;
        padding: 6px 10px;
      }

      .login-link {
        position: relative;
        bottom: auto;
        left: auto;
        text-align: center;
        margin-top: 10px;
      }
    }
  </style>
</head>

<body>
  <div class="header">
    <img src="./img/logos.png" class="logo" alt="Logo">
    <h1 class="judul">MANDIS</h1>
  </div>
  
  <div class="signup-container">
    <div class="signup-box">
      <h2>Sign Up</h2>
      <?php if ($message): ?>
        <div class="message <?= strpos($message, 'berhasil') !== false ? 'success' : 'error' ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>
      
      <form method="post">
        <div class="form-group">
          <input type="text" name="username" placeholder="Username" required>
        </div>
        
        <div class="form-group">
          <input type="email" name="email" placeholder="Email" required>
        </div>
        
        <div class="form-group">
          <input type="password" name="password" placeholder="Password" required>
        </div>
        
        <div class="form-group">
          <input type="text" name="token" placeholder="Token Listrik">
        </div>
        
        <div class="button-container">
          <button type="submit"><strong>Daftar</strong></button>
        </div>
      </form>
      
      <div class="login-link">
        <a href="login.php">Have an account? <br> Click to Log In</a>
      </div>
    </div>
  </div>
</body>
</html>