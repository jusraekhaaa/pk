<?php
/**
 * TOKO FHIKA - Halaman Login
 * File: login.php
 */
require_once 'koneksi.php';

// Jika sudah login, redirect ke halaman tujuan
if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . 'index.php');
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = bersihkan($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Cari user di database
        $stmt = $koneksi->prepare("SELECT id, nama, username, password, role FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verifikasi password (bcrypt)
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_nama']  = $user['nama'];
                $_SESSION['user_role']  = $user['role'];
                $_SESSION['username']   = $user['username'];

                // Redirect berdasarkan role
                if ($user['role'] === 'admin') {
                    redirect(BASE_URL . 'admin/dashboard.php');
                } else {
                    redirect(BASE_URL . 'index.php');
                }
            } else {
                $error = 'Password salah. Silakan coba lagi.';
            }
        } else {
            $error = 'Username tidak ditemukan.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <meta name="description" content="Masuk ke sistem POS dan Manajemen Stok Toko Fhika">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-page">

        <div class="login-card">
            <!-- Logo -->
            <div class="login-logo">
                <div class="login-logo-text">TOKO<span>FHIKA</span></div>
                <div class="login-logo-sub">Point of Sale & Manajemen Stok</div>
            </div>

            <h1 class="login-title">Selamat Datang! 👋</h1>
            <p class="login-subtitle">Masukkan kredensial Anda untuk mengakses sistem.</p>

            <!-- Error Alert -->
            <?php if ($error): ?>
                <div class="login-error">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label class="form-label-custom" for="username">Username</label>
                    <div class="form-input-icon">
                        <i class="bi bi-person"></i>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Masukkan username..."
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            autocomplete="username"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label-custom" for="password">Password</label>
                    <div class="form-input-icon">
                        <i class="bi bi-lock"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan password..."
                            autocomplete="current-password"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk ke Sistem
                </button>
            </form>

            <!-- Demo Info -->
            <div class="login-demo-info">
                <p><i class="bi bi-info-circle"></i> Akun Demo:</p>
                <div style="display:flex; flex-direction:column; gap:6px; font-size:0.78rem; color: var(--text-secondary);">
                    <div>
                        <strong>Admin:</strong>
                        username: <code>admin</code> | password: <code>password</code>
                    </div>
                    <div>
                        <strong>Kasir:</strong>
                        username: <code>kasir</code> | password: <code>password</code>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
    // Loading state on submit
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnLogin');
        btn.innerHTML = '<span class="loading-spinner"></span> Memproses...';
        btn.disabled = true;
    });
    </script>
</body>
</html>
