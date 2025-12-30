<?php
require_once '../config.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 'admin') {
    header("Location: ../autentikasi/login.php");
    exit();
}

function getTotal($conn, $query)
{
    $res = mysqli_query($conn, $query);
    if (!$res)
        return 0;
    $row = mysqli_fetch_assoc($res);
    return $row['total'] ?? 0;
}

/* ================= CARD ================= */
$total_booking = getTotal($conn, "
    SELECT COUNT(*) AS total 
    FROM booking 
    WHERE DATE(created_at)=CURDATE()
");

$total_refund = getTotal($conn, "
    SELECT COUNT(*) AS total 
    FROM refund 
    WHERE status_refund='diproses'
");

$pendapatan = getTotal($conn, "
    SELECT SUM(total_bayar) AS total 
    FROM pembayaran 
    WHERE status_pembayaran='paid'
    AND MONTH(tanggal_bayar)=MONTH(CURDATE())
    AND YEAR(tanggal_bayar)=YEAR(CURDATE())
");

/* ================= CHART DATA - Pendapatan per Kabupaten per Bulan ================= */
// Ambil semua kabupaten dari database
$kabupatenQuery = mysqli_query($conn, "SELECT id_kabupaten, nama_kabupaten FROM kabupaten ORDER BY id_kabupaten");
$kabupatenList = [];
while ($kab = mysqli_fetch_assoc($kabupatenQuery)) {
    $kabupatenList[] = $kab['nama_kabupaten'];
}

// Query pendapatan per kabupaten per bulan
$chartPendapatan = mysqli_query($conn, "
    SELECT 
        kab.nama_kabupaten AS kabupaten,
        MONTH(p.tanggal_bayar) AS bulan,
        COALESCE(SUM(p.total_bayar), 0) AS total
    FROM kabupaten kab
    LEFT JOIN penginapan pen ON kab.id_kabupaten = pen.id_kabupaten
    LEFT JOIN booking b ON pen.id_penginapan = b.id_penginapan
    LEFT JOIN pembayaran p ON b.id_booking = p.id_booking 
        AND p.status_pembayaran = 'paid'
        AND YEAR(p.tanggal_bayar) = YEAR(CURDATE())
    WHERE kab.id_kabupaten IN (1,2,3,4,5)
    GROUP BY kab.id_kabupaten, kab.nama_kabupaten, MONTH(p.tanggal_bayar)
    ORDER BY kab.id_kabupaten, MONTH(p.tanggal_bayar)
");

// Siapkan data untuk chart pendapatan (inisialisasi semua bulan dengan 0)
$dataPendapatanPerBulan = [];
foreach ($kabupatenList as $kab) {
    $dataPendapatanPerBulan[$kab] = array_fill(1, 12, 0);
}

// Isi data dari query
while ($row = mysqli_fetch_assoc($chartPendapatan)) {
    if ($row['bulan'] !== null && $row['bulan'] > 0) {
        $kab = $row['kabupaten'];
        $bulan = (int) $row['bulan'];
        $total = (float) $row['total'];
        $dataPendapatanPerBulan[$kab][$bulan] = $total;
    }
}

/* ================= CHART DATA - Reservasi per Kabupaten per Bulan ================= */
$chartReservasi = mysqli_query($conn, "
    SELECT 
        kab.nama_kabupaten AS kabupaten,
        MONTH(b.created_at) AS bulan,
        COUNT(b.id_booking) AS total
    FROM kabupaten kab
    LEFT JOIN penginapan pen ON kab.id_kabupaten = pen.id_kabupaten
    LEFT JOIN booking b ON pen.id_penginapan = b.id_penginapan 
        AND YEAR(b.created_at) = YEAR(CURDATE())
    WHERE kab.id_kabupaten IN (1,2,3,4,5)
    GROUP BY kab.id_kabupaten, kab.nama_kabupaten, MONTH(b.created_at)
    ORDER BY kab.id_kabupaten, MONTH(b.created_at)
");

// Siapkan data untuk chart reservasi (inisialisasi semua bulan dengan 0)
$dataReservasiPerBulan = [];
foreach ($kabupatenList as $kab) {
    $dataReservasiPerBulan[$kab] = array_fill(1, 12, 0);
}

// Isi data dari query
while ($row = mysqli_fetch_assoc($chartReservasi)) {
    if ($row['bulan'] !== null && $row['bulan'] > 0) {
        $kab = $row['kabupaten'];
        $bulan = (int) $row['bulan'];
        $total = (int) $row['total'];
        $dataReservasiPerBulan[$kab][$bulan] = $total;
    }
}

/* ================= TRANSAKSI - DENGAN FILTER ================= */
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'semua';

$whereClause = "";
if ($filter_status !== 'semua') {
    $whereClause = "WHERE b.status_reservasi = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}

$transaksi = mysqli_query($conn, "
    SELECT 
        b.id_booking,
        b.kode_booking,
        b.tanggal_checkin AS check_in,
        b.status_reservasi AS status,
        pen.nama_penginapan,
        u.nama AS nama_pelanggan
    FROM booking b
    JOIN penginapan pen ON b.id_penginapan = pen.id_penginapan
    JOIN users u ON b.id_user = u.id_user
    $whereClause
    ORDER BY b.created_at DESC
");

// Hitung jumlah untuk setiap status
$count_semua = getTotal($conn, "SELECT COUNT(*) AS total FROM booking");
$count_dipesan = getTotal($conn, "SELECT COUNT(*) AS total FROM booking WHERE status_reservasi='dipesan'");
$count_checkin = getTotal($conn, "SELECT COUNT(*) AS total FROM booking WHERE status_reservasi='check-in'");
$count_selesai = getTotal($conn, "SELECT COUNT(*) AS total FROM booking WHERE status_reservasi='selesai'");
$count_dibatalkan = getTotal($conn, "SELECT COUNT(*) AS total FROM booking WHERE status_reservasi='dibatalkan'");
$count_menunggu = getTotal($conn, "SELECT COUNT(*) AS total FROM booking WHERE status_reservasi='menunggu_pembatalan'");

// Warna untuk setiap kabupaten (sesuai mockup)
$colors = [
    'Kota Yogyakarta' => '#3b82f6',    // Biru
    'Sleman' => '#f59e0b',             // Orange
    'Bantul' => '#10b981',             // Hijau
    'Gunungkidul' => '#ec4899',        // Pink
    'Kulon Progo' => '#8b5cf6'         // Ungu
];

$bulanNames = ['', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin-YogyaStay</title>
    <link rel="icon" type="image/jpeg" href="../assets/img/logonw.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <script src="https://code.highcharts.com/highcharts.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #fff7ed;
            color: #1f2937;
        }

        .layout {
            display: flex;
            min-height: 100vh
        }

        .content {
            margin-left: 240px;
            padding: 30px;
            width: 100%;
            max-width: 1400px
        }

        .topbar {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 30px;
        }

        .toggle-btn {
            display: none;
            font-size: 26px;
            background: none;
            border: none;
            cursor: pointer
        }

        h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            border-bottom: 4px solid #f59e0b;
            display: inline-block;
            padding-bottom: 8px;
        }

        /* Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
            margin-bottom: 40px;
        }

        .card {
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
            border-left: 6px solid #3b82f6;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, .12);
        }

        .card.refund {
            border-color: #f59e0b
        }

        .card.income {
            border-color: #6366f1
        }

        .card small {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            display: block;
            margin-bottom: 10px;
        }

        .card h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        /* Chart Grid */
        .chart-grid {
            margin-top: 35px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 40px;
        }

        .chart-box {
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
        }

        .chart-box h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .chart-container {
            height: 350px;
            width: 100%;
        }

        /* Table Section */
        .table-box {
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
        }

        .table-box h3 {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .table-wrapper {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 330px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .table-wrapper::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 4px;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 20px;
            border-radius: 8px;
            background: #f3f4f6;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border: 2px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-tab:hover {
            background: #e5e7eb;
            color: #374151;
        }

        .filter-tab.active {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }

        .filter-tab .badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .filter-tab.active .badge {
            background: rgba(255, 255, 255, 0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        th,
        td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        td {
            font-size: 14px;
            color: #4b5563;
        }

        a.id-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        a.id-link:hover {
            text-decoration: underline;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status.dipesan {
            background: #fef3c7;
            color: #92400e;
        }

        .status.confirmed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status.check-in {
            background: #dbeafe;
            color: #1e40af;
        }

        .status.selesai {
            background: #d1fae5;
            color: #065f46;
        }

        .status.dibatalkan {
            background: #fee2e2;
            color: #991b1b;
        }

        .status.cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .status.menunggu_pembatalan {
            background: #fce7f3;
            color: #831843;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
            font-size: 14px;
        }

        
        @media(max-width:768px) {
            .content {
                margin-left: 0;
                padding: 20px
            }

            .toggle-btn {
                display: block
            }

            .cards {
                grid-template-columns: 1fr
            }

            .chart-grid {
                grid-template-columns: 1fr
            }

            h1 {
                font-size: 24px
            }

            .filter-tabs {
                gap: 8px
            }

            .filter-tab {
                padding: 8px 15px;
                font-size: 13px
            }
        }
    </style>
</head>

<body>

    <div class="layout">
        <?php include 'partials/sidebar.php'; ?>

        <div class="content">
            <div class="topbar">
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
                    <h2>Rp <?= number_format($pendapatan, 0, ',', '.') ?></h2>
                </div>
            </div>

            <!-- CHART -->
            <div class="chart-grid">
                <div class="chart-box">
                    <h3>Pendapatan per Kabupaten</h3>
                    <div id="chartPendapatan" class="chart-container"></div>
                </div>
                <div class="chart-box">
                    <h3>Jumlah Reservasi per Kabupaten</h3>
                    <div id="chartReservasi" class="chart-container"></div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="table-box">
                <h3>Semua Transaksi Reservasi</h3>

                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <a href="?status=semua" class="filter-tab <?= $filter_status === 'semua' ? 'active' : '' ?>">
                        Semua
                        <span class="badge"><?= $count_semua ?></span>
                    </a>
                    <a href="?status=dipesan" class="filter-tab <?= $filter_status === 'dipesan' ? 'active' : '' ?>">
                        Dipesan
                        <span class="badge"><?= $count_dipesan ?></span>
                    </a>
                    <a href="?status=check-in" class="filter-tab <?= $filter_status === 'check-in' ? 'active' : '' ?>">
                        Check-In
                        <span class="badge"><?= $count_checkin ?></span>
                    </a>
                    <a href="?status=selesai" class="filter-tab <?= $filter_status === 'selesai' ? 'active' : '' ?>">
                        Selesai
                        <span class="badge"><?= $count_selesai ?></span>
                    </a>
                    <a href="?status=dibatalkan"
                        class="filter-tab <?= $filter_status === 'dibatalkan' ? 'active' : '' ?>">
                        Dibatalkan
                        <span class="badge"><?= $count_dibatalkan ?></span>
                    </a>
                    <a href="?status=menunggu_pembatalan"
                        class="filter-tab <?= $filter_status === 'menunggu_pembatalan' ? 'active' : '' ?>">
                        Menunggu Pembatalan
                        <span class="badge"><?= $count_menunggu ?></span>
                    </a>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID RESERVASI</th>
                                <th>NAMA PENGINAPAN</th>
                                <th>PELANGGAN</th>
                                <th>CHECK-IN</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($transaksi) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($transaksi)): ?>
                                    <tr>
                                        <td>
                                            <a
                                                href="<?= $baseUrl ?>/admin/reservasi/detail_reservasi.php?id=<?= $r['id_booking'] ?>">
                                                <?= htmlspecialchars($r['kode_booking']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($r['nama_penginapan']) ?></td>
                                        <td><?= htmlspecialchars($r['nama_pelanggan']) ?></td>
                                        <td><?= date('d-m-Y', strtotime($r['check_in'])) ?></td>
                                        <td>
                                            <span class="status <?= strtolower($r['status']) ?>">
                                                <?= ucfirst($r['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="no-data">
                                        Tidak ada transaksi dengan status "<?= ucfirst($filter_status) ?>"
                                    </td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data untuk Chart Pendapatan
        const dataPendapatan = <?= json_encode($dataPendapatanPerBulan) ?>;
        const kabupatenList = <?= json_encode($kabupatenList) ?>;
        const colors = <?= json_encode($colors) ?>;
        const bulanNames = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Siapkan series untuk Highcharts Pendapatan
        const seriesPendapatan = kabupatenList.map(kab => {
            const data = [];
            for (let i = 7; i <= 12; i++) {
                data.push(dataPendapatan[kab] ? (dataPendapatan[kab][i] || 0) : 0);
            }
            return {
                name: kab,
                data: data,
                color: colors[kab] || '#94a3b8'
            };
        });

        // Chart Pendapatan
        Highcharts.chart('chartPendapatan', {
            chart: {
                type: 'line',
                backgroundColor: 'transparent',
                style: {
                    fontFamily: 'Poppins, sans-serif'
                }
            },
            title: {
                text: null
            },
            xAxis: {
                categories: bulanNames,
                lineColor: '#e5e7eb',
                tickColor: '#e5e7eb',
                labels: {
                    style: {
                        color: '#6b7280',
                        fontSize: '12px'
                    }
                }
            },
            yAxis: {
                title: {
                    text: 'Total Pendapatan',
                    style: {
                        color: '#6b7280',
                        fontSize: '12px'
                    }
                },
                gridLineColor: '#f3f4f6',
                labels: {
                    style: {
                        color: '#6b7280',
                        fontSize: '12px'
                    },
                    formatter: function () {
                        return 'Rp ' + (this.value / 1000000).toFixed(1) + 'jt';
                    }
                }
            },
            tooltip: {
                shared: true,
                backgroundColor: '#ffffff',
                borderColor: '#e5e7eb',
                borderRadius: 8,
                shadow: false,
                useHTML: true,
                style: {
                    fontSize: '12px'
                },
                formatter: function () {
                    let s = '<b>' + this.x + '</b><br/>';
                    this.points.forEach(point => {
                        s += '<span style="color:' + point.color + '">●</span> ' +
                            point.series.name + ': <b>Rp ' +
                            point.y.toLocaleString('id-ID') + '</b><br/>';
                    });
                    return s;
                }
            },
            plotOptions: {
                line: {
                    lineWidth: 3,
                    marker: {
                        enabled: true,
                        radius: 5,
                        symbol: 'circle'
                    }
                }
            },
            legend: {
                align: 'center',
                verticalAlign: 'bottom',
                itemStyle: {
                    color: '#4b5563',
                    fontSize: '12px',
                    fontWeight: '500'
                },
                itemHoverStyle: {
                    color: '#1f2937'
                }
            },
            series: seriesPendapatan,
            credits: {
                enabled: false
            }
        });

        // Data untuk Chart Reservasi
        const dataReservasi = <?= json_encode($dataReservasiPerBulan) ?>;

        // Siapkan series untuk Highcharts Reservasi
        const seriesReservasi = kabupatenList.map(kab => {
            const data = [];
            for (let i = 7; i <= 12; i++) {
                data.push(dataReservasi[kab] ? (dataReservasi[kab][i] || 0) : 0);
            }
            return {
                name: kab,
                data: data,
                color: colors[kab] || '#94a3b8'
            };
        });

        // Chart Reservasi
        Highcharts.chart('chartReservasi', {
            chart: {
                type: 'column',
                backgroundColor: 'transparent',
                style: {
                    fontFamily: 'Poppins, sans-serif'
                }
            },
            title: {
                text: null
            },
            xAxis: {
                categories: bulanNames,
                lineColor: '#e5e7eb',
                tickColor: '#e5e7eb',
                labels: {
                    style: {
                        color: '#6b7280',
                        fontSize: '12px'
                    }
                }
            },
            yAxis: {
                title: {
                    text: 'Jumlah Reservasi',
                    style: {
                        color: '#6b7280',
                        fontSize: '12px'
                    }
                },
                gridLineColor: '#f3f4f6',
                labels: {
                    style: {
                        color: '#6b7280',
                        fontSize: '12px'
                    }
                },
                allowDecimals: false
            },
            tooltip: {
                shared: true,
                backgroundColor: '#ffffff',
                borderColor: '#e5e7eb',
                borderRadius: 8,
                shadow: false,
                useHTML: true,
                style: {
                    fontSize: '12px'
                },
                formatter: function () {
                    let s = '<b>' + this.x + '</b><br/>';
                    this.points.forEach(point => {
                        s += '<span style="color:' + point.color + '">●</span> ' +
                            point.series.name + ': <b>' + point.y + ' reservasi</b><br/>';
                    });
                    return s;
                }
            },
            plotOptions: {
                column: {
                    borderRadius: 4,
                    borderWidth: 0,
                    groupPadding: 0.15,
                    pointPadding: 0.05
                }
            },
            legend: {
                align: 'center',
                verticalAlign: 'bottom',
                itemStyle: {
                    color: '#4b5563',
                    fontSize: '12px',
                    fontWeight: '500'
                },
                itemHoverStyle: {
                    color: '#1f2937'
                }
            },
            series: seriesReservasi,
            credits: {
                enabled: false
            }
        });
    </script>

</body>

</html>