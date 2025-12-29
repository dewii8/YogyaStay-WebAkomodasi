<?php
require_once '../config.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 'admin') {
    header("Location: ../autentikasi/login.php");
    exit();
}

function getTotal($conn, $query) {
    $res = mysqli_query($conn, $query);
    if (!$res) return 0;
    $row = mysqli_fetch_assoc($res);
    return $row['total'] ?? 0;
}

/* ================= CARD ================= */
$total_booking = getTotal($conn,"
    SELECT COUNT(*) AS total 
    FROM booking 
    WHERE DATE(created_at)=CURDATE()
");

$total_refund = getTotal($conn,"
    SELECT COUNT(*) AS total 
    FROM refund 
    WHERE status='pending'
");

$pendapatan = getTotal($conn,"
    SELECT SUM(total_bayar) AS total 
    FROM pembayaran 
    WHERE status='lunas'
    AND MONTH(created_at)=MONTH(CURDATE())
");

/* ================= CHART DATA ================= */
$dataPendapatan = mysqli_query($conn,"
    SELECT b.kabupaten, SUM(p.total_bayar) total
    FROM pembayaran p
    JOIN booking b ON p.id_booking=b.id_booking
    WHERE p.status='lunas'
    GROUP BY b.kabupaten
");

$dataReservasi = mysqli_query($conn,"
    SELECT kabupaten, COUNT(*) total
    FROM booking
    GROUP BY kabupaten
");

/* ================= TRANSAKSI ================= */
$transaksi = mysqli_query($conn,"
    SELECT * FROM booking 
    ORDER BY created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin YogyaStay</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://code.highcharts.com/highcharts.js"></script>

<style>
*{box-sizing:border-box}
body{
    margin:0;
    font-family:'Poppins', sans-serif;
    background:#fff7ed
}


.layout{display:flex}
.content{margin-left:240px;padding:24px;width:100%}

.topbar{display:flex;gap:15px;align-items:center;margin-bottom:20px}
.toggle-btn{display:none;font-size:26px;background:none;border:none}

h1{margin:0;font-size:26px;border-bottom:4px solid #f59e0b;display:inline-block}

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin-top:25px
}

.card{
    background:#fff;
    padding:20px;
    border-radius:14px;
    box-shadow:0 6px 15px rgba(0,0,0,.08);
    border-left:6px solid #3b82f6
}

.card.refund{border-color:#f59e0b}
.card.income{border-color:#6366f1}
.card small{color:#64748b}

.chart-grid{
    margin-top:35px;
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:25px
}

.chart-box{
    background:#fff;
    padding:20px;
    border-radius:14px;
    box-shadow:0 6px 15px rgba(0,0,0,.08)
}

.table-box{
    margin-top:40px;
    background:#fff;
    padding:20px;
    border-radius:14px;
    box-shadow:0 6px 15px rgba(0,0,0,.08);
    overflow-x:auto
}

table{width:100%;border-collapse:collapse}
th,td{padding:12px;border-bottom:1px solid #ddd}
th{background:#f1f5f9}

a.id-link{color:#2563eb;text-decoration:none;font-weight:600}

.status{padding:4px 10px;border-radius:12px;font-size:12px}
.pending{background:#fde68a}
.confirmed{background:#bbf7d0}
.cancelled{background:#fecaca}

@media(max-width:768px){
    .content{margin-left:0}
    .toggle-btn{display:block}
    .chart-grid{grid-template-columns:1fr}
}
</style>
</head>

<body>

<div class="layout">
<?php include 'partials/sidebar.php'; ?>

<div class="content">
    <div class="topbar">
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        <h1>Dashboard Admin YogyaStay</h1>
    </div>

    <!-- CARDS -->
    <div class="cards">
        <div class="card">
            <small>Reservasi Hari Ini</small>
            <h2><?= $total_booking ?> Unit</h2>
        </div>
        <div class="card refund">
            <small>Permintaan Refund</small>
            <h2><?= $total_refund ?></h2>
        </div>
        <div class="card income">
            <small>Pendapatan Bulan Ini</small>
            <h2>Rp <?= number_format($pendapatan,0,',','.') ?></h2>
        </div>
    </div>

    <!-- CHART -->
    <div class="chart-grid">
        <div class="chart-box">
            <h3>Pendapatan per Kabupaten</h3>
            <div id="chartPendapatanKab"></div>
        </div>
        <div class="chart-box">
            <h3>Jumlah Reservasi per Kabupaten</h3>
            <div id="chartReservasiKab"></div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-box">
        <h3>Transaksi Reservasi Terbaru</h3>
        <table>
            <tr>
                <th>ID Reservasi</th>
                <th>Penginapan</th>
                <th>Pelanggan</th>
                <th>Check-in</th>
                <th>Status</th>
            </tr>
            <?php while($r=mysqli_fetch_assoc($transaksi)): ?>
            <tr>
                <td>
                    <a class="id-link" href="detail_reservasi.php?id=<?= $r['id_booking'] ?>">
                        <?= $r['id_booking'] ?>
                    </a>
                </td>
                <td><?= $r['nama_penginapan'] ?></td>
                <td><?= $r['nama_pelanggan'] ?></td>
                <td><?= date('d-m-Y',strtotime($r['check_in'])) ?></td>
                <td>
                    <span class="status <?= strtolower($r['status']) ?>">
                        <?= ucfirst($r['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endwhile ?>
        </table>
    </div>
</div>
</div>

<!-- Logika -->
<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('active');
}

Highcharts.chart('chartPendapatanKab', {
    chart:{type:'column'},
    title:{text:null},
    xAxis:{categories:[
        <?php while($d=mysqli_fetch_assoc($dataPendapatan)) echo "'{$d['kabupaten']}',"; ?>
    ]},
    series:[{
        name:'Pendapatan',
        data:[
            <?php mysqli_data_seek($dataPendapatan,0);
            while($d=mysqli_fetch_assoc($dataPendapatan)) echo "{$d['total']},"; ?>
        ]
    }]
});

Highcharts.chart('chartReservasiKab', {
    chart:{type:'bar'},
    title:{text:null},
    xAxis:{categories:[
        <?php while($d=mysqli_fetch_assoc($dataReservasi)) echo "'{$d['kabupaten']}',"; ?>
    ]},
    series:[{
        name:'Reservasi',
        data:[
            <?php mysqli_data_seek($dataReservasi,0);
            while($d=mysqli_fetch_assoc($dataReservasi)) echo "{$d['total']},"; ?>
        ]
    }]
});
</script>

</body>
</html>
