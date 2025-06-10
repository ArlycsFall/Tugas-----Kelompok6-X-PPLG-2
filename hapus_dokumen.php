<?php
session_start();
if (!isset($_SESSION['id_siswa'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id_dokumen = intval($_GET['id']);
    $conn = new mysqli("localhost", "root", "", "mandis");
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Pastikan dokumen milik siswa yang login
    $stmt = $conn->prepare("SELECT file_path FROM dokumen WHERE id_dokumen=? AND id_siswa=?");
    $stmt->bind_param("ii", $id_dokumen, $_SESSION['id_siswa']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Hapus file fisik
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
        // Hapus data di database
        $del = $conn->prepare("DELETE FROM dokumen WHERE id_dokumen=? AND id_siswa=?");
        $del->bind_param("ii", $id_dokumen, $_SESSION['id_siswa']);
        $del->execute();
    }
    $stmt->close();
    $conn->close();
}

header("Location: dokumen.php");
exit;
?>