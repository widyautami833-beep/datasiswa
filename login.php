
<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=db_data_siswa;charset=utf8mb4',
            'root',
            '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare(
            'SELECT * FROM users_login WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $akun = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($akun && password_verify($password, $akun['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $akun['id'];
            $_SESSION['username'] = $akun['username'];
            $_SESSION['nama_user'] = $akun['nama'];
            $_SESSION['token'] = bin2hex(random_bytes(32));

            header('Location: index.php');
            exit;
        }

        $error = 'Username atau password salah.';
    } catch (PDOException $e) {
        $error = 'Database tidak dapat diakses. Periksa koneksi MySQL.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | DataSiswa</title>
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px;background:linear-gradient(135deg,#dbeafe,#f5f8ff);font-family:Arial,sans-serif;color:#172554}
.card{width:100%;max-width:390px;padding:32px;background:white;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 15px 40px #1e40af16}
.logo{width:54px;height:54px;margin:auto;display:grid;place-items:center;border-radius:15px;background:#2563eb;color:white;font-size:25px;font-weight:bold}
h2{text-align:center;margin:18px 0 8px}
.desc{text-align:center;color:#64748b;font-size:13px;margin-bottom:25px}
label{display:block;margin:18px 0 8px;font-size:13px;font-weight:bold}
input{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:9px;font-size:14px}
input:focus{outline:2px solid #bfdbfe;border-color:#2563eb}
button{width:100%;margin-top:24px;padding:13px;background:#2563eb;border:0;border-radius:9px;color:white;font-weight:bold;cursor:pointer}
button:hover{background:#1d4ed8}
.error{padding:11px;background:#fff1f2;color:#be123c;border-radius:8px;font-size:13px}
</style>
</head>
<body>
<div class="card">
    <div class="logo">S</div>
    <h2>DataSiswa</h2>
    <p class="desc">Login untuk mengelola data siswa</p>

    <?php if ($error !== ''): ?>
        <div class="error"><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?></div>
    <?php endif; ?>

    <form method="post">
        <label for="username">Username</label>
        <input id="username" name="username"
               autocomplete="username" required autofocus>

        <label for="password">Password</label>
        <input id="password" type="password" name="password"
               autocomplete="current-password" required>

        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
