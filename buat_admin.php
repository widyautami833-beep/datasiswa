
<?php
$host = 'localhost';
$db = 'db_data_siswa';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $cek = $pdo->prepare(
        "SELECT id FROM users_login WHERE username = ?"
    );
    $cek->execute(['admin']);

    if ($cek->fetch()) {
        exit('Akun admin sudah tersedia. Hapus file buat_admin.php.');
    }

    $passwordHash = password_hash('Admin123!', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO users_login (username, password, nama)
         VALUES (?, ?, ?)"
    );

    $stmt->execute(['admin', $passwordHash, 'Administrator']);

    echo 'Akun admin berhasil dibuat. Username: admin. ';
    echo 'Password: Admin123!. Hapus file buat_admin.php sekarang.';

} catch (PDOException $e) {
    exit('Koneksi database gagal: ' . $e->getMessage());
}
