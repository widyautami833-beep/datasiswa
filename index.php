<?php
session_start();

$host = 'localhost';
$db   = 'db_data_siswa';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal. Pastikan MySQL aktif dan database sudah dibuat.");
}

if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function kembali($url = 'index.php') {
    header("Location: $url");
    exit;
}

$kelasList = [
    'X RPL 1', 'X RPL 2',
    'XI RPL 1', 'XI RPL 2',
    'XII RPL 1', 'XII RPL 2',
    'X TKJ 1', 'XI TKJ 1', 'XII TKJ 1'
];

$hobiList = [
    'Membaca', 'Olahraga', 'Musik',
    'Menggambar', 'Memasak', 'Coding', 'Gaming'
];

/* =========================
   PROSES SIMPAN, EDIT, HAPUS
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['token']) ||
        !hash_equals($_SESSION['token'], $_POST['token'])
    ) {
        die('Permintaan tidak valid. Silakan kembali ke halaman sebelumnya.');
    }

    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nama = trim($_POST['nama'] ?? '');
        $nis = trim($_POST['nis'] ?? '');
        $gender = $_POST['jenis_kelamin'] ?? '';
        $kelas = $_POST['kelas'] ?? '';
        $alamat = trim($_POST['alamat'] ?? '');

        $hobi = $_POST['hobi'] ?? [];

        if (!is_array($hobi)) {
            $hobi = [];
        }

        $hobi = array_values(
            array_intersect($hobi, $hobiList)
        );

        $errors = [];

        if ($nama === '') {
            $errors[] = 'Nama siswa wajib diisi.';
        }

        if (!preg_match('/^[0-9]{4,20}$/', $nis)) {
            $errors[] = 'NIS harus berupa 4–20 digit angka.';
        }

        if (!in_array(
            $gender,
            ['Laki-laki', 'Perempuan'],
            true
        )) {
            $errors[] = 'Pilih jenis kelamin.';
        }

        if (!in_array($kelas, $kelasList, true)) {
            $errors[] = 'Pilih kelas yang tersedia.';
        }

        if ($alamat === '') {
            $errors[] = 'Alamat wajib diisi.';
        }

        if (empty($errors)) {
            try {
                if ($id) {
                    $sql = "UPDATE siswa
                            SET nama=?, nis=?, jenis_kelamin=?,
                                kelas=?, hobi=?, alamat=?
                            WHERE id=?";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        $nama,
                        $nis,
                        $gender,
                        $kelas,
                        implode(', ', $hobi),
                        $alamat,
                        $id
                    ]);

                    $_SESSION['pesan'] =
                        'Data siswa berhasil diperbarui.';
                } else {
                    $sql = "INSERT INTO siswa
                            (nama, nis, jenis_kelamin, kelas, hobi, alamat)
                            VALUES (?, ?, ?, ?, ?, ?)";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        $nama,
                        $nis,
                        $gender,
                        $kelas,
                        implode(', ', $hobi),
                        $alamat
                    ]);

                    $_SESSION['pesan'] =
                        'Data siswa berhasil ditambahkan.';
                }

                kembali();

            } catch (PDOException $ex) {
                $errors[] = $ex->getCode() === '23000'
                    ? 'NIS sudah terdaftar. Gunakan NIS lain.'
                    : 'Data gagal disimpan.';
            }
        }

        $_SESSION['errors'] = $errors;

        $_SESSION['old'] = [
            'id' => $id ?: '',
            'nama' => $nama,
            'nis' => $nis,
            'jenis_kelamin' => $gender,
            'kelas' => $kelas,
            'hobi' => $hobi,
            'alamat' => $alamat
        ];

        kembali('index.php?page=form' . ($id ? '&id='.$id : ''));
    }

    if ($aksi === 'hapus') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($id) {
            $stmt = $pdo->prepare(
                "DELETE FROM siswa WHERE id=?"
            );

            $stmt->execute([$id]);

            $_SESSION['pesan'] =
                'Data siswa berhasil dihapus.';
        }

        kembali();
    }
}

/* =========================
   PENGAMBILAN DATA
========================= */

$pesan = $_SESSION['pesan'] ?? '';
unset($_SESSION['pesan']);

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];

unset($_SESSION['errors'], $_SESSION['old']);

$page = $_GET['page'] ?? 'list';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$siswa = null;

if ($page === 'form' && $id && empty($old)) {
    $stmt = $pdo->prepare(
        "SELECT * FROM siswa WHERE id=?"
    );

    $stmt->execute([$id]);

    $data = $stmt->fetch();

    if (!$data) {
        $_SESSION['pesan'] = 'Data siswa tidak ditemukan.';
        kembali();
    }

    $old = [
        'id' => $data['id'],
        'nama' => $data['nama'],
        'nis' => $data['nis'],
        'jenis_kelamin' => $data['jenis_kelamin'],
        'kelas' => $data['kelas'],
        'hobi' => array_filter(
            array_map('trim', explode(',', $data['hobi']))
        ),
        'alamat' => $data['alamat']
    ];
}

if ($page === 'detail') {
    $stmt = $pdo->prepare(
        "SELECT * FROM siswa WHERE id=?"
    );

    $stmt->execute([$id]);

    $siswa = $stmt->fetch();

    if (!$siswa) {
        $_SESSION['pesan'] = 'Data siswa tidak ditemukan.';
        kembali();
    }
}


if ($page === 'list') {
    $search = trim($_GET['q'] ?? '');

    if ($search !== '') {
        $stmt = $pdo->prepare(
            "SELECT * FROM siswa
             WHERE nama LIKE ?
                OR nis LIKE ?
                OR kelas LIKE ?
             ORDER BY id DESC"
        );

        $keyword = "%$search%";

        $stmt->execute([
            $keyword,
            $keyword,
            $keyword
        ]);

        $daftarSiswa = $stmt->fetchAll();
    } else {
        $daftarSiswa = $pdo->query(
            "SELECT * FROM siswa ORDER BY id DESC"
        )->fetchAll();
    }

    $total = (int) $pdo->query(
        "SELECT COUNT(*) FROM siswa"
    )->fetchColumn();

    $laki = (int) $pdo->query(
        "SELECT COUNT(*) FROM siswa
         WHERE jenis_kelamin = 'Laki-laki'"
    )->fetchColumn();

    $perempuan = (int) $pdo->query(
        "SELECT COUNT(*) FROM siswa
         WHERE jenis_kelamin = 'Perempuan'"
    )->fetchColumn();
} else {
    $search = '';
    $daftarSiswa = [];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>DataSiswa | Aplikasi Data Siswa</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --biru: #2563eb;
    --biru-tua: #1d4ed8;
    --biru-muda: #eff6ff;
    --teks: #172554;
    --abu: #64748b;
    --garis: #e2e8f0;
    --latar: #f5f8ff;
    --putih: #ffffff;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Inter, Arial, sans-serif;
    background: var(--latar);
    color: #334155;
    font-size: 14px;
}

.app {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 245px;
    flex-shrink: 0;
    background: white;
    border-right: 1px solid var(--garis);
    padding: 25px 17px;
    display: flex;
    flex-direction: column;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 8px 32px;
}

.brand-icon {
    width: 43px;
    height: 43px;
    background: linear-gradient(135deg, #60a5fa, #1d4ed8);
    border-radius: 13px;
    display: grid;
    place-items: center;
    color: white;
    font-size: 21px;
    font-weight: 800;
}

.brand-name {
    font-weight: 800;
    font-size: 17px;
    color: var(--teks);
}

.brand-sub {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 4px;
    letter-spacing: 1px;
}

.nav-label {
    font-size: 10px;
    letter-spacing: 1.4px;
    color: #94a3b8;
    font-weight: 700;
    padding: 0 12px;
    margin-bottom: 13px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px;
    margin-bottom: 6px;
    border-radius: 10px;
    text-decoration: none;
    color: #64748b;
    font-weight: 600;
    font-size: 12px;
}

.nav-link:hover,
.nav-link.active {
    color: var(--biru);
    background: var(--biru-muda);
}

.nav-symbol {
    width: 20px;
    text-align: center;
    font-size: 17px;
}

.sidebar-info {
    margin-top: auto;
    padding: 17px 14px;
    background: var(--biru-muda);
    border-radius: 13px;
    color: var(--abu);
    font-size: 11px;
    line-height: 1.8;
}

.sidebar-info strong {
    display: block;
    color: var(--teks);
    margin-bottom: 5px;
}

.main {
    flex: 1;
    min-width: 0;
}

.topbar {
    height: 75px;
    background: white;
    border-bottom: 1px solid var(--garis);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 35px;
}

.breadcrumb {
    color: #94a3b8;
    font-size: 12px;
}

.breadcrumb b {
    color: #334155;
}

.profile {
    display: flex;
    align-items: center;
    gap: 10px;
}

.avatar {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #dbeafe;
    color: var(--biru);
    font-weight: 800;
}

.profile-name {
    font-size: 12px;
    font-weight: 700;
    color: var(--teks);
}

.profile-role {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 4px;
}

.content {
    max-width: 1450px;
    padding: 32px 35px;
    margin: auto;
}

.heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 25px;
}

.eyebrow {
    color: var(--biru);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.4px;
    margin-bottom: 9px;
}

h1 {
    color: var(--teks);
    font-size: 27px;
    letter-spacing: -1px;
    margin: 0;
}

.subtitle {
    color: var(--abu);
    font-size: 12px;
    margin: 9px 0 0;
    line-height: 1.7;
}

.btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 7px;
    border: 0;
    border-radius: 9px;
    padding: 11px 15px;
    font: 600 12px Inter, Arial, sans-serif;
    text-decoration: none;
    cursor: pointer;
    transition: .2s;
}

.btn-primary {
    background: var(--biru);
    color: white;
    box-shadow: 0 5px 12px #2563eb25;
}

.btn-primary:hover {
    background: var(--biru-tua);
}

.btn-light {
    background: white;
    border: 1px solid var(--garis);
    color: #475569;
}

.btn-light:hover {
    background: #f8fafc;
}

.btn-danger {
    background: #fff1f2;
    border: 1px solid #ffe4e6;
    color: #be123c;
}

.btn-sm {
    padding: 7px 9px;
    font-size: 10px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 17px;
    margin-bottom: 23px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 14px;
    background: white;
    border: 1px solid var(--garis);
    border-radius: 15px;
    padding: 20px;
}

.stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: 20px;
}

.blue {
    background: #eff6ff;
    color: #2563eb;
}

.cyan {
    background: #ecfeff;
    color: #0891b2;
}

.purple {
    background: #f5f3ff;
    color: #7c3aed;
}

.stat-label {
    font-size: 11px;
    color: var(--abu);
}

.stat-number {
    font-size: 25px;
    color: var(--teks);
    font-weight: 800;
    margin-top: 5px;
}

.panel {
    background: white;
    border: 1px solid var(--garis);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 3px 12px #0f172a05;
}

.panel-head {
    padding: 20px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    border-bottom: 1px solid #f1f5f9;
}

.panel-title {
    color: var(--teks);
    font-size: 14px;
    font-weight: 800;
}

.panel-sub {
    color: #94a3b8;
    font-size: 10px;
    margin-top: 5px;
}

.search-form {
    display: flex;
    gap: 8px;
}

.search-box,
.control {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #dbe3ef;
    border-radius: 9px;
    outline: none;
    background: white;
    color: #334155;
    font: 12px Inter, Arial, sans-serif;
}

.search-box {
    width: 245px;
}

.search-box:focus,
.control:focus {
    border-color: #60a5fa;
    box-shadow: 0 0 0 3px #dbeafe;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

th {
    padding: 13px 20px;
    background: #f8fafc;
    color: #94a3b8;
    text-align: left;
    font-size: 10px;
    letter-spacing: .7px;
}

td {
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    color: #475569;
    font-size: 11px;
}

tbody tr:hover {
    background: #fbfdff;
}

.student {
    display: flex;
    align-items: center;
    gap: 10px;
}

.student-avatar {
    width: 35px;
    height: 35px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: #dbeafe;
    color: #1d4ed8;
    font-weight: 800;
}

.student-name {
    color: #1e293b;
    font-weight: 700;
}

.student-nis {
    color: #94a3b8;
    font-size: 10px;
    margin-top: 4px;
}

.badge {
    display: inline-block;
    background: #eff6ff;
    color: #1d4ed8;
    padding: 6px 9px;
    border-radius: 7px;
    font-size: 10px;
    font-weight: 700;
}

.actions {
    display: flex;
    align-items: center;
    gap: 5px;
}

.inline-form {
    display: inline;
}

.alert {
    padding: 13px 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 12px;
    line-height: 1.7;
}

.alert.success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}

.alert.error {
    background: #fff1f2;
    border: 1px solid #fecdd3;
    color: #9f1239;
}

.empty {
    text-align: center;
    padding: 45px 20px;
    color: #94a3b8;
}

.empty strong {
    display: block;
    color: #334155;
    margin: 10px 0 5px;
}

.empty p {
    font-size: 11px;
    margin-bottom: 18px;
}

.form-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(230px, .8fr);
    gap: 20px;
    align-items: start;
}

.form-panel {
    padding: 24px;
}

.section-title {
    font-size: 14px;
    color: var(--teks);
    font-weight: 800;
    margin: 0 0 6px;
}

.section-desc {
    color: #94a3b8;
    font-size: 11px;
    line-height: 1.7;
    margin: 0 0 24px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 18px;
}

.field {
    margin-bottom: 19px;
}

.field.full {
    grid-column: 1 / -1;
}

.field label,
.field-label {
    display: block;
    color: #334155;
    font-weight: 700;
    font-size: 11px;
    margin-bottom: 9px;
}

.required {
    color: #ef4444;
}

textarea.control {
    min-height: 105px;
    resize: vertical;
    line-height: 1.6;
}

.choice-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.choice {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px;
    border: 1px solid var(--garis);
    border-radius: 9px;
    font-size: 10px;
    color: #475569;
    cursor: pointer;
}

.choice input {
    accent-color: var(--biru);
    margin: 0;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 8px;
    padding-top: 19px;
    margin-top: 4px;
    border-top: 1px solid #f1f5f9;
}

.side-note {
    padding: 22px;
    background: linear-gradient(145deg, #eff6ff, #f8faff);
    border: 1px solid #dbeafe;
    border-radius: 14px;
}

.side-note h3 {
    color: var(--teks);
    font-size: 14px;
    margin: 0 0 10px;
}

.side-note p,
.side-note li {
    color: var(--abu);
    font-size: 11px;
    line-height: 1.9;
}

.side-note ul {
    padding-left: 17px;
}

.detail-layout {
    display: grid;
    grid-template-columns: 260px minmax(0, 1fr);
    gap: 20px;
}

.detail-profile {
    padding: 28px;
    text-align: center;
}

.big-avatar {
    width: 80px;
    height: 80px;
    display: grid;
    place-items: center;
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 24px;
    font-size: 28px;
    font-weight: 800;
    margin: 0 auto 16px;
}

.detail-profile h2 {
    color: var(--teks);
    font-size: 16px;
    overflow-wrap: anywhere;
}

.detail-profile p {
    color: #94a3b8;
    font-size: 11px;
}

.detail-info {
    padding: 24px;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px;
}

.info-item {
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
}

.info-label {
    color: #94a3b8;
    font-size: 10px;
    font-weight: 700;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: .6px;
}

.info-value {
    color: #334155;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.8;
    overflow-wrap: anywhere;
    white-space: normal;
}

.footer {
    text-align: center;
    color: #a0aec0;
    font-size: 10px;
    padding: 28px 10px 10px;
}

@media(max-width:1000px) {
    .sidebar { width: 210px; }
    .content { padding: 25px 22px; }
    .topbar { padding: 0 22px; }
    .form-layout { grid-template-columns: 1fr; }
    .side-note { display: none; }
}

@media(max-width:720px) {
    .app { display: block; }
    .sidebar { width: 100%; padding: 14px; border-right: 0; border-bottom: 1px solid var(--garis); }
    .brand { padding: 0 5px 15px; }
    .nav-label, .sidebar-info { display: none; }
    .sidebar nav { display: flex; gap: 5px; overflow-x: auto; }
    .nav-link { white-space: nowrap; padding: 10px; margin: 0; font-size: 10px; }
    .topbar { height: 62px; padding: 0 15px; }
    .content { padding: 23px 14px; }
    .heading { align-items: flex-start; flex-direction: column; }
    h1 { font-size: 23px; }
    .stats { grid-template-columns: 1fr; gap: 10px; }
    .panel-head { align-items: flex-start; flex-direction: column; padding: 16px; }
    .search-form { width: 100%; }
    .search-box { width: 100%; min-width: 0; }
    .form-grid, .info-grid, .detail-layout { grid-template-columns: 1fr; }
    .form-panel { padding: 18px; }
    .actions { flex-wrap: wrap; }
}
</style>
</head>
<body>
<div class="app">

<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon">S</div>
        <div>
            <div class="brand-name">DataSiswa</div>
            <div class="brand-sub">STUDENT MANAGEMENT</div>
        </div>
    </div>

    <div class="nav-label">MENU UTAMA</div>

    <nav>
        <a class="nav-link <?=$page==='list'?'active':''?>" href="index.php">
            <span class="nav-symbol">▦</span> Daftar Siswa
        </a>
        <a class="nav-link <?=$page==='form'?'active':''?>" href="laporan.php">
            <span class="nav-symbol">▦</span> Laporan
        </a>
        <a class="nav-link <?=$page==='form'?'active':''?>" href="index.php?page=form">
            <span class="nav-symbol">＋</span> Input Data Siswa
        </a>
    </nav>

    <div class="sidebar-info">
        <strong>Informasi Aplikasi</strong>
        Aplikasi pengelolaan data siswa dengan tampilan sederhana, rapi, dan mudah digunakan.
    </div>
</aside>

<main class="main">
<header class="topbar">
    <div class="breadcrumb">
        Aplikasi / <b><?=$page==='form'?( !empty($old['id'])?'Edit Data Siswa':'Input Data Siswa' ):($page==='detail'?'Detail Siswa':'Daftar Siswa')?></b>
    </div>
    
<div class="profile">
    <div class="avatar">AD</div>

    <div>
        <div class="profile-name">Administrator</div>
        <div class="profile-role">Pengelola Data</div>
    </div>

    <form action="logout.php" method="post"
          onsubmit="return confirm('Yakin ingin logout dari aplikasi?')">
        <input type="hidden" name="token"
               value="<?= e($_SESSION['token']) ?>">

        <button type="submit" class="btn btn-danger">
            ↪ Logout
        </button>
    </form>
</div>

</header>

<div class="content">

<?php if ($pesan): ?>
    <div class="alert success"><?=e($pesan)?></div>
<?php endif; ?>

<?php if ($page === 'list'): ?>

    <div class="heading">
        <div>
            <div class="eyebrow">STUDENT OVERVIEW</div>
            <h1>Daftar Siswa</h1>
            <p class="subtitle">Kelola dan pantau informasi siswa dalam satu tempat.</p>
        </div>
        <a class="btn btn-primary" href="index.php?page=form">＋ Tambah Siswa</a>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon blue">♙</div>
            <div><div class="stat-label">Total Siswa</div><div class="stat-number"><?=number_format($total,0,',','.')?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cyan">◉</div>
            <div><div class="stat-label">Laki-laki</div><div class="stat-number"><?=number_format($laki,0,',','.')?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">◉</div>
            <div><div class="stat-label">Perempuan</div><div class="stat-number"><?=number_format($perempuan,0,',','.')?></div></div>
        </div>
    </div>

    <section class="panel">
        <div class="panel-head">
            <div>
                <div class="panel-title">Data Siswa</div>
                <div class="panel-sub">Daftar seluruh siswa yang tersimpan</div>
            </div>
            <form class="search-form" method="get">
                <input class="search-box" type="search" name="q" placeholder="Cari nama, NIS, atau kelas..." value="<?=e($search)?>">
                <button class="btn btn-light" type="submit">Cari</button>
                <?php if($search!==''): ?><a class="btn btn-light" href="index.php">Reset</a><?php endif; ?>
            </form>
        </div>

        <div class="table-wrap">
        <table>
            <thead>
                <tr><th>SISWA</th><th>JENIS KELAMIN</th><th>KELAS</th><th>HOBI</th><th>AKSI</th></tr>
            </thead>
            <tbody>
            <?php if (!$daftarSiswa): ?>
                <tr><td colspan="5">
                    <div class="empty">
                        <div style="font-size:30px">⌕</div>
                        <strong><?=$search!==''?'Data tidak ditemukan':'Belum ada data siswa'?></strong>
                        <p><?=$search!==''?'Coba kata kunci pencarian lain.':'Tambahkan data siswa pertama untuk mulai menggunakan aplikasi.'?></p>
                        <?php if($search===''): ?><a class="btn btn-primary" href="index.php?page=form">＋ Tambah Siswa</a><?php endif; ?>
                    </div>
                </td></tr>
            <?php else: foreach($daftarSiswa as $s): ?>
                <tr>
                    <td>
                        <div class="student">
                            <div class="student-avatar"><?=e(mb_strtoupper(mb_substr($s['nama'],0,1,'UTF-8'),'UTF-8'))?></div>
                            <div><div class="student-name"><?=e($s['nama'])?></div><div class="student-nis">NIS: <?=e($s['nis'])?></div></div>
                        </div>
                    </td>
                    <td><?=e($s['jenis_kelamin'])?></td>
                    <td><span class="badge"><?=e($s['kelas'])?></span></td>
                    <td><?=e($s['hobi'] ?: '—')?></td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-light btn-sm" href="index.php?page=detail&id=<?=$s['id']?>">Lihat</a>
                            <a class="btn btn-light btn-sm" href="index.php?page=form&id=<?=$s['id']?>">Edit</a>
                            <form class="inline-form" method="post" onsubmit="return confirm('Yakin ingin menghapus data siswa ini?')">
                                <input type="hidden" name="token" value="<?=e($_SESSION['token'])?>">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id" value="<?=$s['id']?>">
                                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </section>

<?php elseif ($page === 'form'): ?>

    <?php $isEdit = !empty($old['id']); ?>

    <div class="heading">
        <div>
            <div class="eyebrow">STUDENT FORM</div>
            <h1><?=$isEdit?'Edit Data Siswa':'Input Data Siswa'?></h1>
            <p class="subtitle"><?=$isEdit?'Perbarui informasi siswa pada formulir berikut.':'Lengkapi formulir untuk menambahkan siswa baru.'?></p>
        </div>
        <a class="btn btn-light" href="index.php">← Kembali</a>
    </div>

    <?php if($errors): ?>
        <div class="alert error">
            <strong>Periksa kembali data berikut:</strong>
            <ul><?php foreach($errors as $err): ?><li><?=e($err)?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="form-layout">
    <section class="panel form-panel">
        <h2 class="section-title">Informasi Pribadi</h2>
        <p class="section-desc">Isi data siswa dengan benar. Kolom bertanda * wajib diisi.</p>

        <form method="post" action="index.php?page=form<?=$isEdit?'&id='.(int)$old['id']:''?>">
            <input type="hidden" name="token" value="<?=e($_SESSION['token'])?>">
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="id" value="<?=e($old['id']??'')?>">

            <div class="form-grid">
                <div class="field">
                    <label for="nama">Nama Lengkap <span class="required">*</span></label>
                    <input class="control" id="nama" name="nama" maxlength="120" placeholder="Masukkan nama lengkap" value="<?=e($old['nama']??'')?>" required>
                </div>

                <div class="field">
                    <label for="nis">Nomor Induk Siswa (NIS) <span class="required">*</span></label>
                    <input class="control" id="nis" name="nis" inputmode="numeric" pattern="[0-9]{4,20}" maxlength="20" placeholder="Contoh: 12345" value="<?=e($old['nis']??'')?>" required>
                </div>

                <div class="field full">
                    <span class="field-label">Jenis Kelamin <span class="required">*</span></span>
                    <div class="choice-row">
                        <label class="choice"><input type="radio" name="jenis_kelamin" value="Laki-laki" <?=($old['jenis_kelamin']??'')==='Laki-laki'?'checked':''?> required> Laki-laki</label>
                        <label class="choice"><input type="radio" name="jenis_kelamin" value="Perempuan" <?=($old['jenis_kelamin']??'')==='Perempuan'?'checked':''?> required> Perempuan</label>
                    </div>
                </div>

                <div class="field">
                    <label for="kelas">Kelas <span class="required">*</span></label>
                    <select class="control" id="kelas" name="kelas" required>
                        <option value="">Pilih kelas</option>
                        <?php foreach($kelasList as $c): ?>
                            <option value="<?=e($c)?>" <?=($old['kelas']??'')===$c?'selected':''?>><?=e($c)?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <span class="field-label">Hobi</span>
                    <div class="choice-row">
                        <?php foreach($hobiList as $h): ?>
                            <label class="choice">
                                <input type="checkbox" name="hobi[]" value="<?=e($h)?>" <?=in_array($h,$old['hobi']??[],true)?'checked':''?>>
                                <?=e($h)?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="field full">
                    <label for="alamat">Alamat Lengkap <span class="required">*</span></label>
                    <textarea class="control" id="alamat" name="alamat" rows="4" placeholder="Masukkan alamat tempat tinggal siswa..." required><?=e($old['alamat']??'')?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a class="btn btn-light" href="index.php">Batal</a>
                <button class="btn btn-light" type="reset">↺ Reset Form</button>
                <button class="btn btn-primary" type="submit">✓ Simpan Data</button>
            </div>
        </form>
    </section>

    <aside class="side-note">
        <h3>Petunjuk Pengisian</h3>
        <p>Pastikan data yang dimasukkan sudah benar sebelum disimpan. NIS harus unik untuk setiap siswa.</p>
        <hr style="border:0;border-top:1px solid #dbeafe;margin:18px 0">
        <strong style="color:#1e40af;font-size:11px">Tips pengisian</strong>
        <ul>
            <li>Gunakan nama lengkap siswa.</li>
            <li>Periksa kembali nomor NIS.</li>
            <li>Pilih kelas yang sesuai.</li>
            <li>Hobi dapat dipilih lebih dari satu.</li>
        </ul>
    </aside>
    </div>

<?php elseif ($page === 'detail'): ?>

    <div class="heading">
        <div>
            <div class="eyebrow">STUDENT PROFILE</div>
            <h1>Detail Siswa</h1>
            <p class="subtitle">Informasi lengkap siswa yang dipilih.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-light" href="index.php">← Kembali</a>
            <a class="btn btn-primary" href="index.php?page=form&id=<?=$siswa['id']?>">Edit Data</a>
        </div>
    </div>

    <div class="detail-layout">
        <section class="panel detail-profile">
            <div class="big-avatar"><?=e(mb_strtoupper(mb_substr($siswa['nama'],0,1,'UTF-8'),'UTF-8'))?></div>
            <h2><?=e($siswa['nama'])?></h2>
            <p>NIS: <?=e($siswa['nis'])?></p>
            <span class="badge"><?=e($siswa['kelas'])?></span>
        </section>

        <section class="panel detail-info">
            <h2 class="section-title" style="margin-bottom:22px">Informasi Siswa</h2>
            <div class="info-grid">
                <div class="info-item"><div class="info-label">Nama Lengkap</div><div class="info-value"><?=e($siswa['nama'])?></div></div>
                <div class="info-item"><div class="info-label">Nomor Induk Siswa</div><div class="info-value"><?=e($siswa['nis'])?></div></div>
                <div class="info-item"><div class="info-label">Jenis Kelamin</div><div class="info-value"><?=e($siswa['jenis_kelamin'])?></div></div>
                <div class="info-item"><div class="info-label">Kelas</div><div class="info-value"><?=e($siswa['kelas'])?></div></div>
                <div class="info-item"><div class="info-label">Hobi</div><div class="info-value"><?=e($siswa['hobi'] ?: 'Belum diisi')?></div></div>
                <div class="info-item"><div class="info-label">Tanggal Ditambahkan</div><div class="info-value"><?=e(date('d-m-Y H:i', strtotime($siswa['created_at'])))?></div></div>
                <div class="info-item" style="grid-column:1/-1"><div class="info-label">Alamat Lengkap</div><div class="info-value"><?=nl2br(e($siswa['alamat']))?></div></div>
            </div>
        </section>
    </div>

<?php else: ?>

    <section class="panel" style="padding:30px">
        <h2>Halaman tidak ditemukan</h2>
        <a class="btn btn-primary" href="index.php">Kembali ke Daftar Siswa</a>
    </section>

<?php endif; ?>

<div class="footer">DataSiswa © <?=date('Y')?> · Aplikasi Pengelolaan Data Siswa</div>
</div>
</main>
</div>
</body>
</html>