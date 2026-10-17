
<?php
require_once 'db.php';

// Pastikan pengguna sudah login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Mengambil seluruh data siswa
$stmt = $pdo->query("SELECT * FROM siswa ORDER BY id DESC");
$dataSiswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Menghitung jumlah siswa
$totalSiswa = count($dataSiswa);
$totalLaki = 0;
$totalPerempuan = 0;

foreach ($dataSiswa as $siswa) {
    if ($siswa['jenis_kelamin'] === 'Laki-laki') {
        $totalLaki++;
    } elseif ($siswa['jenis_kelamin'] === 'Perempuan') {
        $totalPerempuan++;
    }
}

function aman($nilai)
{
    return htmlspecialchars((string) ($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Siswa | DataSiswa</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f3f7fc;
            color: #243247;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .header h1 {
            margin: 0 0 8px;
            color: #173d70;
        }

        .header p {
            margin: 0;
            color: #738197;
        }

        .btn {
            display: inline-block;
            padding: 11px 17px;
            border: none;
            border-radius: 8px;
            background: #2864b4;
            color: white;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }

        .btn:hover {
            background: #194d91;
        }

        .btn-secondary {
            background: #e5edf8;
            color: #244c80;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: white;
            padding: 22px;
            border-radius: 13px;
            border: 1px solid #e1eaf5;
            box-shadow: 0 4px 15px rgba(31, 72, 120, 0.04);
        }

        .summary-card p {
            margin: 0 0 12px;
            color: #718198;
            font-size: 14px;
        }

        .summary-card h2 {
            margin: 0;
            color: #2059a1;
            font-size: 30px;
        }

        .panel {
            background: white;
            border-radius: 13px;
            padding: 24px;
            border: 1px solid #e1eaf5;
            overflow: hidden;
        }

        .panel h2 {
            margin: 0 0 7px;
            color: #173d70;
        }

        .panel .description {
            color: #738197;
            font-size: 14px;
            margin-bottom: 22px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th {
            background: #edf4fd;
            color: #31557f;
            font-size: 12px;
            text-align: left;
            padding: 14px 12px;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #edf1f7;
            font-size: 14px;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #75849a;
        }

        .footer {
            margin-top: 22px;
            text-align: center;
            color: #8190a4;
            font-size: 13px;
        }

        @media (max-width: 650px) {
            body {
                padding: 15px;
            }

            .summary {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 15px;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .container {
                max-width: 100%;
            }

            .summary-card,
            .panel {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            th {
                background: #edf4fd !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="header">
        <div>
            <h1>Laporan Data Siswa</h1>
            <p>DataSiswa · Laporan pengelolaan data siswa</p>
        </div>

        <div class="no-print">
            <a href="index.php" class="btn btn-secondary">
                Kembali
            </a>

            <button onclick="window.print()" class="btn">
                Cetak Laporan
            </button>
        </div>
    </div>

    <div class="summary">
        <div class="summary-card">
            <p>Total Siswa</p>
            <h2><?= $totalSiswa ?></h2>
        </div>

        <div class="summary-card">
            <p>Siswa Laki-laki</p>
            <h2><?= $totalLaki ?></h2>
        </div>

        <div class="summary-card">
            <p>Siswa Perempuan</p>
            <h2><?= $totalPerempuan ?></h2>
        </div>
    </div>

    <div class="panel">
        <h2>Data Seluruh Siswa</h2>
        <div class="description">
            Laporan berisi data siswa yang tersimpan di database.
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>NO.</th>
                        <th>NAMA SISWA</th>
                        <th>NIS</th>
                        <th>JENIS KELAMIN</th>
                        <th>KELAS</th>
                        <th>HOBI</th>
                        <th>ALAMAT</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($dataSiswa)): ?>
                        <tr>
                            <td colspan="7" class="empty">
                                Belum ada data siswa.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dataSiswa as $i => $siswa): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= aman($siswa['nama']) ?></td>
                                <td><?= aman($siswa['nis']) ?></td>
                                <td><?= aman($siswa['jenis_kelamin']) ?></td>
                                <td><?= aman($siswa['kelas']) ?></td>
                                <td><?= aman($siswa['hobi']) ?></td>
                                <td><?= aman($siswa['alamat']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        DataSiswa © <?= date('Y') ?> · Aplikasi Pengelolaan Data Siswa
    </div>

</div>
</body>
</html>
