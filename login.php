<?php
session_start();
header('Content-Type: application/json');

// Konfigurasi database baru
$db_config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'login_system_v2',
    'port' => 3306
];

try {
    $conn = new mysqli(
        $db_config['host'],
        $db_config['user'],
        $db_config['pass'],
        $db_config['name'],
        $db_config['port']
    );

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Dapatkan input
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input
    if (empty($username) || empty($password)) {
        throw new Exception("Username dan password harus diisi");
    }

    // Dapatkan user dari database
    $stmt = $conn->prepare("
        SELECT id, username, password_hash, role, is_active, is_locked, login_attempts 
        FROM users 
        WHERE username = ?
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Username atau password salah");
    }

    $user = $result->fetch_assoc();

    // Cek akun terkunci
    if ($user['is_locked']) {
        throw new Exception("Akun Anda terkunci. Silakan hubungi administrator.");
    }

    // Cek akun aktif
    if (!$user['is_active']) {
        throw new Exception("Akun Anda tidak aktif.");
    }

    // Verifikasi password
    if (!password_verify($password, $user['password_hash'])) {
        // Update percobaan login gagal
        $new_attempts = $user['login_attempts'] + 1;
        $max_attempts = 3;
        
        $stmt = $conn->prepare("
            UPDATE users 
            SET login_attempts = ?, 
                is_locked = CASE WHEN ? >= ? THEN TRUE ELSE FALSE END 
            WHERE username = ?
        ");
        $stmt->bind_param("iiis", $new_attempts, $new_attempts, $max_attempts, $username);
        $stmt->execute();

        $attempts_left = $max_attempts - $new_attempts;
        
        if ($new_attempts >= $max_attempts) {
            throw new Exception("Terlalu banyak percobaan login. Akun Anda terkunci.");
        } else {
            throw new Exception("Username atau password salah. Percobaan tersisa: " . $attempts_left);
        }
    }

    // Login berhasil, reset percobaan login
    $stmt = $conn->prepare("
        UPDATE users 
        SET login_attempts = 0, 
            last_login = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;

    // Catat log login
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("
        INSERT INTO login_logs (user_id, login_time, ip_address, success) 
        VALUES (?, NOW(), ?, TRUE)
    ");
    $stmt->bind_param("is", $user['id'], $ip_address);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'role' => $user['role'],
        'message' => 'Login berhasil'
    ]);

} catch (Exception $e) {
    // Catat log login gagal jika user ada
    if (isset($user)) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $stmt = $conn->prepare("
            INSERT INTO login_logs (user_id, login_time, ip_address, success) 
            VALUES (?, NOW(), ?, FALSE)
        ");
        $stmt->bind_param("is", $user['id'], $ip_address);
        $stmt->execute();
    }
    
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