<?php
session_start();
header('Content-Type: application/json');

// Cek session dan role admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Konfigurasi database
require_once 'db_config.php';

try {
    $conn = new mysqli(
        $db_config['host'],
        $db_config['user'],
        $db_config['pass'],
        $db_config['name'],
        $db_config['port']
    );

    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    $username = $conn->real_escape_string($_POST['newUsername'] ?? '');
    $password = $_POST['newPassword'] ?? '';
    $full_name = $conn->real_escape_string($_POST['full_name'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');

    // Validasi input
    if (empty($username) || empty($password) || empty($full_name)) {
        throw new Exception("Semua field harus diisi");
    }

    // Validasi kompleksitas password
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        throw new Exception("Password harus mengandung minimal 8 karakter, termasuk huruf besar, huruf kecil, angka, dan karakter khusus");
    }

    // Cek username sudah ada
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("Username sudah digunakan");
    }

    // Cek email jika diisi
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email sudah digunakan");
        }
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Buat user baru
    $stmt = $conn->prepare("
        INSERT INTO users (username, password_hash, full_name, email) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssss", $username, $password_hash, $full_name, $email);

    if (!$stmt->execute()) {
        throw new Exception("Gagal membuat user: " . $conn->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'User berhasil dibuat',
        'user_id' => $conn->insert_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>