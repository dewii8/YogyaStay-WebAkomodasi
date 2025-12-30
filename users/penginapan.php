<?php
require_once '../config.php';

// Cek koneksi database
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

/* ================= AJAX: KECAMATAN ================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'kecamatan') {
    $id_kabupaten = mysqli_real_escape_string($conn, $_GET['id_kabupaten']);
    $q = mysqli_query($conn, "SELECT * FROM kecamatan WHERE id_kabupaten='$id_kabupaten'");

    if (!$q) {
        echo "<option value=''>Error loading data</option>";
        exit;
    }

    // Langsung tampilkan list kecamatan
    while ($d = mysqli_fetch_assoc($q)) {
        echo "<option value='{$d['id_kecamatan']}'>{$d['nama_kecamatan']}</option>";
    }
    exit;
}

/* ================= FILTER ================= */
$kabupaten = isset($_GET['kabupaten']) ? mysqli_real_escape_string($conn, $_GET['kabupaten']) : '';
$kecamatan = isset($_GET['kecamatan']) ? mysqli_real_escape_string($conn, $_GET['kecamatan']) : '';
$kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : 'semua';
$harga_min = isset($_GET['harga_min']) && $_GET['harga_min'] !== '' ? intval($_GET['harga_min']) : null;
$harga_max = isset($_GET['harga_max']) && $_GET['harga_max'] !== '' ? intval($_GET['harga_max']) : null;
$checkin = isset($_GET['checkin']) ? mysqli_real_escape_string($conn, $_GET['checkin']) : '';
$checkout = isset($_GET['checkout']) ? mysqli_real_escape_string($conn, $_GET['checkout']) : '';

//Handle data tamu dari beranda
$rooms = isset($_GET['rooms']) ? intval($_GET['rooms']) : 1;
$adults = isset($_GET['adults']) ? intval($_GET['adults']) : 1;
$children = isset($_GET['children']) ? intval($_GET['children']) : 0;

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Cek apakah sedang melakukan filtering
$isFiltering = !empty($kabupaten) || !empty($kecamatan) || ($kategori !== 'semua') || (!empty($harga_min) && !empty($harga_max));

/* ================= QUERY ================= */
// Build WHERE clause
$where_clause = "WHERE 1=1";

// Filter berdasarkan kabupaten
if (!empty($kabupaten)) {
    $where_clause .= " AND p.id_kabupaten = '$kabupaten'";
}

// Filter berdasarkan kecamatan
if (!empty($kecamatan)) {
    $where_clause .= " AND p.id_kecamatan = '$kecamatan'";
}

// Filter berdasarkan tipe_penginapan 
if ($kategori !== 'semua') {
    $where_clause .= " AND LOWER(p.tipe_penginapan) = LOWER('$kategori')";
}

// Filter berdasarkan rentang harga
if ($harga_min !== null && $harga_max !== null) {
    $where_clause .= " AND p.harga_mulai BETWEEN $harga_min AND $harga_max";
}

// Filter berdasarkan kapasitas kamar & orang
$having_clause = "";
if ($rooms > 0 || $adults > 0 || $children > 0) {
    $total_guests = $adults + $children;

    $having_conditions = [];

    if ($rooms > 0) {
        $having_conditions[] = "SUM(tk.jumlah_kamar) >= $rooms";
    }

    if ($total_guests > 0) {
        $having_conditions[] = "MAX(tk.kapasitas_orang) >= $total_guests";
    }

    if (!empty($having_conditions)) {
        $having_clause = " HAVING " . implode(" AND ", $having_conditions);
    }
}

// Count total results untuk pagination
$count_sql = "SELECT COUNT(DISTINCT p.id_penginapan) as total 
              FROM penginapan p
              JOIN kecamatan kc ON p.id_kecamatan = kc.id_kecamatan
              LEFT JOIN tipe_kamar tk ON p.id_penginapan = tk.id_penginapan
              $where_clause";

if ($having_clause) {
    $count_sql = "SELECT COUNT(*) as total FROM (
                    SELECT p.id_penginapan
                    FROM penginapan p
                    JOIN kecamatan kc ON p.id_kecamatan = kc.id_kecamatan
                    LEFT JOIN tipe_kamar tk ON p.id_penginapan = tk.id_penginapan
                    $where_clause
                    GROUP BY p.id_penginapan
                    $having_clause
                  ) as filtered";
}

$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_results = $count_row['total'];
$total_pages = ceil($total_results / $items_per_page);

// Main query dengan JOIN ke tipe_kamar dan fasilitas
$sql = "SELECT p.*, kc.nama_kecamatan,
        GROUP_CONCAT(DISTINCT tk.nama_tipe SEPARATOR ', ') as tipe_kamar_list,
        SUM(tk.jumlah_kamar) as total_kamar,
        MAX(tk.kapasitas_orang) as max_kapasitas,
        GROUP_CONCAT(DISTINCT f.nama_fasilitas SEPARATOR '|') as fasilitas_list,
        (SELECT path_gambar FROM gambar_penginapan WHERE id_penginapan = p.id_penginapan ORDER BY is_thumbnail DESC LIMIT 1) as gambar_thumbnail
        FROM penginapan p
        JOIN kecamatan kc ON p.id_kecamatan = kc.id_kecamatan
        LEFT JOIN tipe_kamar tk ON p.id_penginapan = tk.id_penginapan
        LEFT JOIN penginapan_fasilitas pf ON p.id_penginapan = pf.id_penginapan
        LEFT JOIN fasilitas f ON pf.id_fasilitas = f.id_fasilitas
        $where_clause
        GROUP BY p.id_penginapan";

// Tambahkan HAVING jika ada filter kapasitas
if ($having_clause) {
    $sql .= $having_clause;
}

// Cek apakah kolom rating ada
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM penginapan LIKE 'rating'");
if (mysqli_num_rows($check_column) > 0) {
    $sql .= " ORDER BY p.rating DESC";
} else {
    $sql .= " ORDER BY p.id_penginapan DESC";
}

// Add LIMIT untuk pagination
$sql .= " LIMIT $items_per_page OFFSET $offset";

$result = mysqli_query($conn, $sql);

// Error handling
if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

$current_results = mysqli_num_rows($result);
$showing_start = $offset + 1;
$showing_end = min($offset + $items_per_page, $total_results);

// Include header
$page_title = 'Penginapan';
require_once 'header.php';
?>

<!-- ========= CUSTOM STYLES FOR THIS PAGE ========= -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
    /* ========= SEARCH BAR ========= */
    .search-container {
        max-width: 1200px;
        margin: 30px auto 0;
        padding: 0 20px;
        position: relative;
        z-index: 10;
    }

    .search-bar {
        background: white;
        border-radius: 50px;
        padding: 20px 30px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
    }

    .search-field {
        flex: 1;
        min-width: 150px;
        position: relative;
    }

    .search-field label {
        display: block;
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .search-field select,
    .search-field input {
        width: 100%;
        border: none;
        outline: none;
        font-size: 14px;
        color: #333;
        background: transparent;
        padding: 5px 0;
        cursor: pointer;
    }

    .search-divider {
        width: 1px;
        height: 40px;
        background: #e0e0e0;
    }

    .btn-search {
        background: #fde047;
        border: none;
        padding: 12px 35px;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s;
    }

    .btn-search:hover {
        background: #fcd34d;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(253, 224, 71, 0.4);
    }

    /* ========= GUEST DROPDOWN ========= */
    .guest-dropdown {
        position: relative;
    }

    .guest-display {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 10px 12px;
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        transition: all 0.3s;
        font-size: 13px;
    }

    .guest-display:hover {
        border-color: #f5a742;
        background: #fef9e7;
    }

    .guest-dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        border: 2px solid #e5e7eb;
        padding: 20px;
        min-width: 300px;
        display: none;
        z-index: 100;
        margin-top: 10px;
    }

    .guest-dropdown-menu.active {
        display: block;
    }

    .guest-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        margin-bottom: 10px;
        background: #f9fafb;
        transition: all 0.3s;
    }

    .guest-row:hover {
        border-color: #f5a742;
        background: #fef9e7;
    }

    .guest-row:last-child {
        margin-bottom: 0;
    }

    .guest-label {
        display: flex;
        flex-direction: column;
    }

    .guest-label strong {
        font-size: 14px;
        color: #333;
        font-weight: 600;
    }

    .guest-label small {
        font-size: 12px;
        color: #666;
        margin-top: 2px;
    }

    .guest-controls {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .guest-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 2px solid #e5e7eb;
        background: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 600;
        transition: all 0.3s;
        color: #333;
    }

    .guest-btn:hover {
        border-color: #f5a742;
        background: #fef9e7;
        color: #f5a742;
        transform: scale(1.1);
    }

    .guest-btn:active {
        transform: scale(0.95);
    }

    .guest-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
        transform: none;
    }

    .guest-count {
        min-width: 40px;
        text-align: center;
        font-weight: 700;
        font-size: 16px;
        color: #333;
    }

    /* ========= MAIN CONTENT ========= */
    .container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .section-header {
        margin-bottom: 10px;
    }

    .section-title {
        font-size: 32px;
        font-weight: bold;
        color: #1a1a1a;
        margin-bottom: 5px;
    }

    .section-subtitle {
        color: #666;
        font-size: 16px;
        margin-bottom: 15px;
    }

    .results-count {
        color: #666;
        font-size: 14px;
        margin-bottom: 20px;
    }

    .main {
        display: flex;
        gap: 30px;
    }

    /* ========= SIDEBAR FILTER ========= */
    .sidebar {
        width: 280px;
        background: white;
        padding: 25px;
        border-radius: 20px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        height: fit-content;
        position: sticky;
        top: 20px;
    }

    .sidebar h3 {
        font-size: 20px;
        margin-bottom: 20px;
        color: #1a1a1a;
        padding-bottom: 15px;
        border-bottom: 3px solid #f5a742;
    }

    .filter-section {
        margin-bottom: 25px;
    }

    .filter-section h4 {
        font-size: 16px;
        margin-bottom: 15px;
        color: #333;
        font-weight: 600;
    }

    .filter-option {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        cursor: pointer;
    }

    .filter-option input[type="radio"] {
        margin-right: 10px;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .filter-option label {
        font-size: 14px;
        color: #555;
        cursor: pointer;
    }

    .price-inputs {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
    }

    .price-input {
        flex: 1;
    }

    .price-input input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
    }

    /* ========= PRICE RANGE SLIDER ========= */
    .price-slider-container {
        padding: 10px 0 20px;
    }

    .price-slider-wrapper {
        position: relative;
        height: 5px;
        background: #e5e7eb;
        border-radius: 5px;
        margin: 20px 0;
    }

    .price-slider-track {
        position: absolute;
        height: 100%;
        background: linear-gradient(90deg, #f5a742, #f8c471);
        border-radius: 5px;
    }

    .price-slider {
        position: relative;
        width: 100%;
    }

    .price-slider input[type="range"] {
        position: absolute;
        width: 100%;
        height: 5px;
        background: transparent;
        pointer-events: none;
        -webkit-appearance: none;
        top: -20px;
    }

    .price-slider input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        pointer-events: auto;
        width: 20px;
        height: 20px;
        background: white;
        border: 3px solid #f5a742;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        transition: all 0.3s;
    }

    .price-slider input[type="range"]::-webkit-slider-thumb:hover {
        transform: scale(1.2);
        box-shadow: 0 3px 10px rgba(245, 167, 66, 0.4);
    }

    .price-slider input[type="range"]::-moz-range-thumb {
        pointer-events: auto;
        width: 20px;
        height: 20px;
        background: white;
        border: 3px solid #f5a742;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        transition: all 0.3s;
    }

    .price-slider input[type="range"]::-moz-range-thumb:hover {
        transform: scale(1.2);
        box-shadow: 0 3px 10px rgba(245, 167, 66, 0.4);
    }

    .price-manual-inputs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .price-manual-input {
        flex: 1;
    }

    .price-manual-input label {
        display: block;
        font-size: 11px;
        color: #666;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .price-manual-input input {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .price-manual-input input:focus {
        outline: none;
        border-color: #f5a742;
        box-shadow: 0 0 0 3px rgba(245, 167, 66, 0.1);
    }

    .price-manual-input input:hover {
        border-color: #f5a742;
        background: #fef9e7;
    }

    /* ========= CUSTOM CALENDAR ========= */
    .date-picker-wrapper {
        position: relative;
    }

    .date-input-custom {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .date-input-custom:hover {
        border-color: #f5a742;
        background: #fef9e7;
    }

    .date-input-custom.focused {
        border-color: #f5a742;
        background: white;
        box-shadow: 0 0 0 3px rgba(245, 167, 66, 0.1);
    }

    .date-icon {
        font-size: 16px;
        color: #f5a742;
    }

    .date-content {
        flex: 1;
    }

    .date-label {
        font-size: 11px;
        color: #666;
        margin-bottom: 2px;
        white-space: nowrap;
    }

    .date-value {
        font-size: 14px;
        color: #333;
        font-weight: 600;
    }

    .date-input-custom input[type="text"] {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        cursor: pointer;
    }

    /* Style untuk kabupaten dan kecamatan dropdown */
    .search-field select {
        width: 100%;
        border: 2px solid #e5e7eb;
        outline: none;
        font-size: 14px;
        color: #333;
        background: white;
        padding: 10px 12px;
        cursor: pointer;
        border-radius: 12px;
        transition: all 0.3s;
    }

    .search-field select:hover {
        border-color: #f5a742;
        background: #fef9e7;
    }

    .search-field select:focus {
        border-color: #f5a742;
        background: white;
        box-shadow: 0 0 0 3px rgba(245, 167, 66, 0.1);
    }

    /* ========= FLATPICKR CUSTOM STYLING ========= */
    .flatpickr-calendar {
        border-radius: 8px !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid #e5e7eb !important;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    }

    .flatpickr-months {
        padding: 10px 0;
    }

    .flatpickr-current-month {
        font-size: 15px !important;
        font-weight: 500 !important;
    }

    .flatpickr-weekday {
        font-weight: 600 !important;
        font-size: 12px !important;
    }

    .flatpickr-day {
        font-size: 13px !important;
        font-weight: 400 !important;
    }

    .flatpickr-day.today {
        font-weight: 600 !important;
    }

    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange {
        font-weight: 500 !important;
    }

    /* Animation */
    .flatpickr-calendar.open {
        animation: slideInCalendar 0.3s ease-out;
    }

    @keyframes slideInCalendar {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .btn-filter {
        width: 100%;
        background: #6b7280;
        color: white;
        border: none;
        padding: 12px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        margin-top: 15px;
        transition: all 0.3s;
    }

    .btn-filter:hover {
        background: #4b5563;
    }

    /* ========= CARDS GRID ========= */
    .cards {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }

    .card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s;
        cursor: pointer;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .card-image {
        position: relative;
        height: 200px;
        overflow: hidden;
    }

    .card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: white;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        color: #555;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .card-body {
        padding: 20px;
    }

    .card-title {
        font-size: 18px;
        font-weight: bold;
        color: #1a1a1a;
        margin-bottom: 8px;
    }

    .card-location {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
    }

    .card-amenities {
        display: flex;
        gap: 12px;
        margin-bottom: 12px;
    }

    .amenity-icon {
        font-size: 18px;
        color: #70777eff !important;
    }


    .card-rating {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 12px;
    }

    .rating-star {
        color: #fbbf24;
        font-size: 16px;
    }

    .rating-text {
        font-size: 14px;
        font-weight: 600;
        color: #333;
    }

    .rating-count {
        font-size: 13px;
        color: #666;
    }

    .card-price {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .price {
        font-size: 18px;
        font-weight: bold;
        color: #1a1a1a;
    }

    .price-period {
        font-size: 13px;
        color: #666;
        font-weight: normal;
    }

    .btn-detail {
        width: 100%;
        background: #fde047;
        border: none;
        padding: 12px;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s;
    }

    .btn-detail:hover {
        background: #fcd34d;
        transform: translateY(-2px);
    }

    /* ========= PAGINATION ========= */
    .pagination-container {
        grid-column: 1/-1;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        padding: 40px 0 20px;
    }

    .pagination {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .page-item {
        list-style: none;
    }

    .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 8px 12px;
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        color: #333;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s;
    }

    .page-link:hover {
        background: #fef9e7;
        border-color: #f5a742;
        color: #f5a742;
        transform: translateY(-2px);
    }

    .page-item.active .page-link {
        background: linear-gradient(135deg, #f5a742 0%, #f8c471 100%);
        border-color: #f5a742;
        color: white;
        box-shadow: 0 4px 12px rgba(245, 167, 66, 0.4);
    }

    .page-item.disabled .page-link {
        opacity: 0.3;
        cursor: not-allowed;
        pointer-events: none;
    }

    .page-link.prev-next {
        font-size: 18px;
    }

    .pagination-info {
        margin-top: 15px;
        text-align: center;
        color: #666;
        font-size: 14px;
    }

    /* ========= RESPONSIVE DESIGN ========= */
    @media (max-width: 1024px) {
        .search-bar {
            padding: 15px 20px;
        }

        .cards {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .search-container {
            padding: 0 15px;
            margin: 20px auto 0;
        }

        .search-bar {
            flex-direction: column;
            align-items: stretch;
            padding: 20px;
            border-radius: 30px;
        }

        .search-field {
            min-width: 100%;
        }

        .search-divider {
            display: none;
        }

        .btn-search {
            width: 100%;
            margin-top: 10px;
        }

        .guest-dropdown-menu {
            right: auto;
            left: 0;
            min-width: 100%;
        }

        .container {
            margin: 20px auto;
            padding: 0 15px;
        }

        .section-title {
            font-size: 24px;
        }

        .section-subtitle {
            font-size: 14px;
        }

        .main {
            flex-direction: column;
            gap: 20px;
        }

        .sidebar {
            width: 100%;
            position: static;
            order: -1;
            margin-bottom: 20px;
            margin-top: 0;
        }

        .cards {
            grid-template-columns: 1fr;
            order: 0;
        }

        .card-image {
            height: 180px;
        }

        .card-title {
            font-size: 16px;
        }

        .pagination {
            flex-wrap: wrap;
            gap: 5px;
        }

        .page-link {
            min-width: 36px;
            height: 36px;
            font-size: 13px;
            padding: 6px 10px;
        }

        .pagination-info {
            font-size: 12px;
        }
    }

    @media (max-width: 480px) {
        .search-bar {
            padding: 15px;
            gap: 10px;
        }

        .search-field label {
            font-size: 11px;
        }

        .search-field select,
        .search-field input {
            font-size: 13px;
        }

        .date-input-custom {
            padding: 10px 12px;
        }

        .date-label {
            font-size: 10px;
        }

        .date-value {
            font-size: 13px;
        }

        .guest-display {
            font-size: 12px;
            padding: 8px 10px;
        }

        .btn-search {
            padding: 10px 20px;
            font-size: 14px;
        }

        .guest-dropdown-menu {
            min-width: 280px;
            padding: 15px;
        }

        .guest-row {
            padding: 12px;
        }

        .guest-label strong {
            font-size: 13px;
        }

        .guest-label small {
            font-size: 11px;
        }

        .guest-btn {
            width: 32px;
            height: 32px;
            font-size: 18px;
        }

        .guest-count {
            min-width: 35px;
            font-size: 15px;
        }

        .section-title {
            font-size: 20px;
        }

        .section-subtitle {
            font-size: 13px;
        }

        .results-count {
            font-size: 12px;
        }

        .sidebar {
            padding: 20px 15px;
            margin-bottom: 15px;
        }

        .sidebar h3 {
            font-size: 18px;
        }

        .filter-section h4 {
            font-size: 15px;
        }

        .filter-option label {
            font-size: 13px;
        }

        .card-body {
            padding: 15px;
        }

        .card-title {
            font-size: 15px;
        }

        .card-location {
            font-size: 13px;
        }

        .amenity-icon {
            font-size: 16px;
        }

        .rating-text {
            font-size: 13px;
        }

        .price {
            font-size: 16px;
        }

        .price-period {
            font-size: 12px;
        }

        .btn-detail {
            padding: 10px;
            font-size: 13px;
        }

        .page-link {
            min-width: 32px;
            height: 32px;
            font-size: 12px;
            padding: 5px 8px;
        }

        .page-link.prev-next {
            font-size: 16px;
        }

        .price-manual-input label {
            font-size: 10px;
        }

        .price-manual-input input {
            font-size: 12px;
            padding: 6px 8px;
        }
    }

    @media (max-width: 768px) and (orientation: landscape) {
        .cards {
            grid-template-columns: repeat(2, 1fr);
        }

        .card-image {
            height: 150px;
        }
    }

    @media (min-width: 769px) and (max-width: 1024px) {
        .cards {
            grid-template-columns: repeat(2, 1fr);
        }

        .sidebar {
            width: 250px;
        }
    }
</style>

<!-- ========= SEARCH BAR ========= -->
<div class="search-container">
    <form method="GET" action="">
        <div class="search-bar">
            <div class="search-field">
                <label>Kabupaten</label>
                <select name="kabupaten" id="kabupaten">
                    <option value="" <?= ($kabupaten == '') ? 'selected' : '' ?>>Semua Kabupaten</option>
                    <?php
                    $qkab = mysqli_query($conn, "SELECT * FROM kabupaten");
                    if ($qkab) {
                        while ($k = mysqli_fetch_assoc($qkab)) {
                            $sel = ($kabupaten == $k['id_kabupaten']) ? 'selected' : '';
                            echo "<option value='{$k['id_kabupaten']}' $sel>{$k['nama_kabupaten']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="search-divider"></div>

            <div class="search-field">
                <label>Kecamatan</label>
                <select name="kecamatan" id="kecamatan">
                    <?php
                    if ($kabupaten) {
                        // Jika sudah pilih kabupaten: tampilkan "Semua Kecamatan" dulu
                        $sel_semua = ($kecamatan == '') ? 'selected' : '';
                        echo "<option value='' $sel_semua>Semua Kecamatan</option>";

                        // Lalu tampilkan list kecamatan
                        $qkc = mysqli_query($conn, "SELECT * FROM kecamatan WHERE id_kabupaten='$kabupaten'");
                        if ($qkc) {
                            while ($kc = mysqli_fetch_assoc($qkc)) {
                                $sel = ($kecamatan == $kc['id_kecamatan']) ? 'selected' : '';
                                echo "<option value='{$kc['id_kecamatan']}' $sel>{$kc['nama_kecamatan']}</option>";
                            }
                        }
                    } else {
                        // Kondisi awal atau semua kabupaten: tampilkan "Semua Kecamatan"
                        echo "<option value='' selected>Semua Kecamatan</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="search-divider"></div>

            <div class="search-field date-picker-wrapper">
                <label>Check-in</label>
                <div class="date-input-custom" id="checkin-custom">
                    <div class="date-content">
                        <div class="date-value" id="checkin-display">Pilih tanggal</div>
                    </div>
                    <input type="text" name="checkin" id="checkin-input" value="<?= $checkin ?>" readonly>
                </div>
            </div>

            <div class="search-divider"></div>

            <div class="search-field date-picker-wrapper">
                <label>Check-out</label>
                <div class="date-input-custom" id="checkout-custom">
                    <div class="date-content">
                        <div class="date-value" id="checkout-display">Pilih tanggal</div>
                    </div>
                    <input type="text" name="checkout" id="checkout-input" value="<?= $checkout ?>" readonly>
                </div>
            </div>

            <div class="search-divider"></div>

            <div class="search-field guest-dropdown">
                <label>Kamar dan Tamu</label>
                <div class="guest-display" onclick="toggleGuestMenu()">
                    <span id="guest-summary">1 Kamar · 1 Dewasa</span>
                </div>

                <div class="guest-dropdown-menu" id="guest-menu">
                    <div class="guest-row">
                        <div class="guest-label">
                            <strong>Kamar</strong>
                        </div>
                        <div class="guest-controls">
                            <button type="button" class="guest-btn" onclick="changeGuest('room', -1)">−</button>
                            <span class="guest-count" id="room-count">1</span>
                            <button type="button" class="guest-btn" onclick="changeGuest('room', 1)">+</button>
                        </div>
                    </div>

                    <div class="guest-row">
                        <div class="guest-label">
                            <strong>Dewasa</strong>
                            <small>Usia 18 tahun ke atas</small>
                        </div>
                        <div class="guest-controls">
                            <button type="button" class="guest-btn" onclick="changeGuest('adult', -1)">−</button>
                            <span class="guest-count" id="adult-count">1</span>
                            <button type="button" class="guest-btn" onclick="changeGuest('adult', 1)">+</button>
                        </div>
                    </div>

                    <div class="guest-row">
                        <div class="guest-label">
                            <strong>Anak</strong>
                            <small>Usia 0-17 tahun</small>
                        </div>
                        <div class="guest-controls">
                            <button type="button" class="guest-btn" onclick="changeGuest('child', -1)">−</button>
                            <span class="guest-count" id="child-count">0</span>
                            <button type="button" class="guest-btn" onclick="changeGuest('child', 1)">+</button>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="rooms" id="rooms-input" value="<?= $rooms ?>">
            <input type="hidden" name="adults" id="adults-input" value="<?= $adults ?>">
            <input type="hidden" name="children" id="children-input" value="<?= $children ?>">

            <button type="submit" class="btn-search">Cari</button>
        </div>
    </form>
</div>

<!-- ========= MAIN CONTENT ========= -->
<div class="container">
    <div class="section-header">
        <h2 class="section-title">Jelajahi Penginapan</h2>
        <p class="section-subtitle">
            <?php
            if ($isFiltering) {
                $filter_info = [];
                if ($kategori !== 'semua') {
                    $filter_info[] = ucfirst($kategori);
                }
                if (!empty($kabupaten)) {
                    $q_kab_name = mysqli_query($conn, "SELECT nama_kabupaten FROM kabupaten WHERE id_kabupaten='$kabupaten'");
                    if ($kab_data = mysqli_fetch_assoc($q_kab_name)) {
                        $filter_info[] = $kab_data['nama_kabupaten'];
                    }
                }
                if (!empty($kecamatan)) {
                    $q_kec_name = mysqli_query($conn, "SELECT nama_kecamatan FROM kecamatan WHERE id_kecamatan='$kecamatan'");
                    if ($kec_data = mysqli_fetch_assoc($q_kec_name)) {
                        $filter_info[] = $kec_data['nama_kecamatan'];
                    }
                }

                if (!empty($filter_info)) {
                    echo "Menampilkan " . implode(" di ", $filter_info);
                } else {
                    echo "Hasil pencarian";
                }
            } else {
                echo "Temukan penginapan terbaik di Yogyakarta";
            }
            ?>
        </p>
        <p class="results-count">Menampilkan <?= $showing_start ?>-<?= $showing_end ?> dari <?= $total_results ?>
            penginapan</p>
    </div>

    <div class="main">
        <!-- ========= SIDEBAR FILTER ========= -->
        <div class="sidebar">
            <h3>Filter</h3>

            <form method="GET" action="">
                <div class="filter-section">
                    <h4>Kategori</h4>
                    <div class="filter-option">
                        <input type="radio" name="kategori" id="semua" value="semua" <?= $kategori == 'semua' ? 'checked' : '' ?>>
                        <label for="semua">Semua</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="kategori" id="hotel" value="hotel" <?= $kategori == 'hotel' ? 'checked' : '' ?>>
                        <label for="hotel">Hotel</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="kategori" id="villa" value="villa" <?= $kategori == 'villa' ? 'checked' : '' ?>>
                        <label for="villa">Villa</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="kategori" id="homestay" value="homestay"
                            <?= $kategori == 'homestay' ? 'checked' : '' ?>>
                        <label for="homestay">Homestay</label>
                    </div>
                </div>

                <div class="filter-section">
                    <h4>Rentang Harga</h4>
                    <div class="price-slider-container">
                        <div class="price-manual-inputs">
                            <div class="price-manual-input">
                                <label>Harga Minimum</label>
                                <input type="number" name="harga_min" id="harga_min" placeholder="0"
                                    value="<?= $harga_min ?>" step="50000" min="0" max="5000000">
                            </div>
                            <div class="price-manual-input">
                                <label>Harga Maksimum</label>
                                <input type="number" name="harga_max" id="harga_max" placeholder="5000000"
                                    value="<?= $harga_max ?>" step="50000" min="0" max="5000000">
                            </div>
                        </div>

                        <div class="price-slider-wrapper">
                            <div class="price-slider-track" id="price-track"></div>
                        </div>

                        <div class="price-slider">
                            <input type="range" id="min-range" min="0" max="5000000" step="50000"
                                value="<?= $harga_min ?: 0 ?>">
                            <input type="range" id="max-range" min="0" max="5000000" step="50000"
                                value="<?= $harga_max ?: 5000000 ?>">
                        </div>
                    </div>
                </div>

                <input type="hidden" name="kabupaten" value="<?= $kabupaten ?>">
                <input type="hidden" name="kecamatan" value="<?= $kecamatan ?>">
                <input type="hidden" name="checkin" value="<?= $checkin ?>">
                <input type="hidden" name="checkout" value="<?= $checkout ?>">
                <input type="hidden" name="rooms" value="<?= $rooms ?>">
                <input type="hidden" name="adults" value="<?= $adults ?>">
                <input type="hidden" name="children" value="<?= $children ?>">

                <button type="submit" class="btn-filter">Terapkan Filter</button>
            </form>
        </div>

        <!-- ========= CARDS GRID ========= -->
        <div class="cards">
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    // Mapping fasilitas ke ikon - SAMA PERSIS DENGAN BERANDA
                    $icon_map = [
                        'WiFi' => 'bi-wifi',
                        'AC' => 'bi-snow',
                        'TV' => 'bi-tv',
                        'Kamar Mandi Dalam' => 'bi-droplet',
                        'Air Panas' => 'bi-fire',
                        'Kolam Renang' => 'bi-water',
                        'Parkir' => 'bi-car-front',
                        'Resepsionis 24 Jam' => 'bi-clock',
                        'Sarapan' => 'bi-cup-hot',
                        'Dapur' => 'bi-basket',
                        'Private Pool' => 'bi-water',
                        'Balkon' => 'bi-building',
                        'View Alam' => 'bi-tree',
                        'Lift' => 'bi-arrow-up-circle',
                        'Gym' => 'bi-person-arms-up',
                        'Meeting Room' => 'bi-people'
                    ];
                    ?>
                    <div class="card">
                        <div class="card-image">
                            <img src="../<?= $row['gambar_thumbnail'] ?>" alt="<?= $row['nama_penginapan'] ?>">
                            <span class="badge"><?= ucfirst($row['tipe_penginapan']) ?></span>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?= $row['nama_penginapan'] ?></h3>
                            <p class="card-location">
                                <i class="bi bi-geo-alt-fill" style="color: #7e7871ff;"></i>
                                <?= $row['nama_kecamatan'] ?>, Yogyakarta
                            </p>

                            <!-- Bagian Fasilitas/Amenities yang diperbarui -->
                            <div class="card-amenities">
                                <?php
                                $prioritas_fasilitas = ['WiFi', 'AC', 'TV'];
                                $fasilitas_ditampilkan = [];

                                if (!empty($row['fasilitas_list'])) {
                                    $fasilitas_array = explode('|', $row['fasilitas_list']);
                                    $fasilitas_array = array_unique($fasilitas_array);

                                    // Cek apakah ada fasilitas prioritas
                                    foreach ($prioritas_fasilitas as $prio_fasilitas) {
                                        if (in_array($prio_fasilitas, $fasilitas_array) && count($fasilitas_ditampilkan) < 3) {
                                            $fasilitas_ditampilkan[] = $prio_fasilitas;
                                        }
                                    }

                                    // Jika masih kurang dari 3, tambahkan fasilitas lain
                                    if (count($fasilitas_ditampilkan) < 3) {
                                        foreach ($fasilitas_array as $fasilitas) {
                                            if (!in_array($fasilitas, $fasilitas_ditampilkan) && count($fasilitas_ditampilkan) < 3) {
                                                $fasilitas_ditampilkan[] = $fasilitas;
                                            }
                                        }
                                    }

                                    // Tampilkan fasilitas dengan format yang sama seperti di beranda
                                    foreach ($fasilitas_ditampilkan as $nama_fasilitas) {
                                        $icon_class = isset($icon_map[$nama_fasilitas])
                                            ? $icon_map[$nama_fasilitas]
                                            : 'bi-check-circle';

                                        echo '<div class="facility" style="display:inline-block; margin-right:15px;">';
                                        echo '<i class="bi ' . $icon_class . '" style="color: #70777eff; font-size:16px;"></i>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>


                            <div class="card-rating">
                                <i class="bi bi-star-fill rating-star"></i>
                                <span class="rating-text">4.8</span>
                                <span class="rating-count">(127)</span>
                            </div>

                            <div class="card-price">
                                <span class="price">Mulai Rp <?= number_format($row['harga_mulai'], 0, ',', '.') ?> <span
                                        class="price-period">/malam</span></span>
                            </div>

                            <button class="btn-detail"
                                onclick="window.location.href='detail.php?id=<?= $row['id_penginapan'] ?>'">Lihat
                                Detail</button>
                        </div>
                    </div>
                <?php
                }
            } else {
                echo "<p style='grid-column: 1/-1; text-align:center; padding:40px; color:#666;'>Tidak ada penginapan yang sesuai dengan filter Anda.</p>";
            }
            ?>


            <!-- ========= PAGINATION ========= -->
            <?php if ($total_pages > 1) { ?>
                <div class="pagination-container">
                    <nav>
                        <ul class="pagination">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link prev-next"
                                    href="<?= ($page > 1) ? '?page=' . ($page - 1) . '&' . http_build_query(array_filter($_GET, function ($key) {
                                        return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) : '#' ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>

                            <?php
                            $show_pages = 5;
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1&' . http_build_query(array_filter($_GET, function ($key) {
                                    return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) . '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active = ($i == $page) ? 'active' : '';
                                echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . '&' . http_build_query(array_filter($_GET, function ($key) {
                                    return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) . '">' . $i . '</a></li>';
                            }

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&' . http_build_query(array_filter($_GET, function ($key) {
                                    return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link prev-next"
                                    href="<?= ($page < $total_pages) ? '?page=' . ($page + 1) . '&' . http_build_query(array_filter($_GET, function ($key) {
                                        return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)) : '#' ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>

<script>
    const minRange = document.getElementById('min-range');
    const maxRange = document.getElementById('max-range');
    const priceTrack = document.getElementById('price-track');
    const minPriceInput = document.getElementById('harga_min');
    const maxPriceInput = document.getElementById('harga_max');

    function updatePriceSlider() {
        let minVal = parseInt(minRange.value);
        let maxVal = parseInt(maxRange.value);

        if (maxVal - minVal < 100000) {
            if (event && event.target === minRange) {
                minVal = maxVal - 100000;
                minRange.value = minVal;
            } else if (event && event.target === maxRange) {
                maxVal = minVal + 100000;
                maxRange.value = maxVal;
            }
        }

        minPriceInput.value = minVal;
        maxPriceInput.value = maxVal;

        const percentMin = (minVal / 5000000) * 100;
        const percentMax = (maxVal / 5000000) * 100;
        priceTrack.style.left = percentMin + '%';
        priceTrack.style.width = (percentMax - percentMin) + '%';
    }

    minRange.addEventListener('input', updatePriceSlider);
    maxRange.addEventListener('input', updatePriceSlider);

    minPriceInput.addEventListener('change', function () {
        let val = parseInt(this.value) || 0;
        if (val < 0) val = 0;
        if (val > 5000000) val = 5000000;
        this.value = val;
        minRange.value = val;
        updatePriceSliderManual();
    });

    maxPriceInput.addEventListener('change', function () {
        let val = parseInt(this.value) || 0;
        if (val < 0) val = 0;
        if (val > 5000000) val = 5000000;
        this.value = val;
        maxRange.value = val;
        updatePriceSliderManual();
    });

    function updatePriceSliderManual() {
        let minVal = parseInt(minRange.value);
        let maxVal = parseInt(maxRange.value);

        if (maxVal - minVal < 100000) {
            if (minVal + 100000 <= 5000000) {
                maxVal = minVal + 100000;
                maxRange.value = maxVal;
                maxPriceInput.value = maxVal;
            } else {
                minVal = maxVal - 100000;
                minRange.value = minVal;
                minPriceInput.value = minVal;
            }
        }

        const percentMin = (minVal / 5000000) * 100;
        const percentMax = (maxVal / 5000000) * 100;
        priceTrack.style.left = percentMin + '%';
        priceTrack.style.width = (percentMax - percentMin) + '%';
    }

    updatePriceSlider();

    // ========= FLATPICKR CALENDAR =========
    const checkinInput = document.getElementById('checkin-input');
    const checkoutInput = document.getElementById('checkout-input');
    const checkinDisplay = document.getElementById('checkin-display');
    const checkoutDisplay = document.getElementById('checkout-display');
    const checkinCustom = document.getElementById('checkin-custom');
    const checkoutCustom = document.getElementById('checkout-custom');

    function formatDateDisplay(dateString) {
        if (!dateString) return 'Pilih tanggal';
        const date = new Date(dateString);

        // Nama hari dalam 3 huruf
        const days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        const dayName = days[date.getDay()];

        // Format dd/mm/yyyy
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();

        return `${dayName}, ${day}/${month}/${year}`;
    }

    const checkinPicker = flatpickr(checkinInput, {
        locale: "id",
        dateFormat: "Y-m-d",
        minDate: "today",
        onChange: function (selectedDates, dateStr, instance) {
            checkinDisplay.textContent = formatDateDisplay(dateStr);

            if (checkoutPicker) {
                const minCheckout = new Date(selectedDates[0]);
                minCheckout.setDate(minCheckout.getDate() + 1);
                checkoutPicker.set('minDate', minCheckout);

                if (!checkoutInput.value || new Date(checkoutInput.value) <= selectedDates[0]) {
                    checkoutPicker.setDate(minCheckout);
                }
            }
        },
        onOpen: function () {
            checkinCustom.classList.add('focused');
        },
        onClose: function () {
            checkinCustom.classList.remove('focused');
        }
    });

    const checkoutPicker = flatpickr(checkoutInput, {
        locale: "id",
        dateFormat: "Y-m-d",
        minDate: "today",
        onChange: function (selectedDates, dateStr, instance) {
            checkoutDisplay.textContent = formatDateDisplay(dateStr);
        },
        onOpen: function () {
            checkoutCustom.classList.add('focused');
        },
        onClose: function () {
            checkoutCustom.classList.remove('focused');
        }
    });

    if (checkinInput.value) {
        checkinDisplay.textContent = formatDateDisplay(checkinInput.value);
    }
    if (checkoutInput.value) {
        checkoutDisplay.textContent = formatDateDisplay(checkoutInput.value);
    }

    // ========= DROPDOWN KECAMATAN =========
    document.getElementById('kabupaten').addEventListener('change', function () {
        const kabupatenId = this.value;
        const kecamatanSelect = document.getElementById('kecamatan');

        if (kabupatenId) {
            // Jika pilih kabupaten tertentu, load kecamatan dari database
            fetch('?ajax=kecamatan&id_kabupaten=' + kabupatenId)
                .then(res => res.text())
                .then(data => {
                    kecamatanSelect.innerHTML = '<option value="">Semua Kecamatan</option>' + data;
                });
        } else {
            kecamatanSelect.innerHTML = '<option value="" selected>Semua Kecamatan</option>';
        }
    });


    // ========= GUEST DROPDOWN =========
    let guestCounts = {
        room: <?= $rooms ?>,
        adult: <?= $adults ?>,
        child: <?= $children ?>
    };

    function initGuestDisplay() {
        document.getElementById('room-count').textContent = guestCounts.room;
        document.getElementById('adult-count').textContent = guestCounts.adult;
        document.getElementById('child-count').textContent = guestCounts.child;

        document.getElementById('rooms-input').value = guestCounts.room;
        document.getElementById('adults-input').value = guestCounts.adult;
        document.getElementById('children-input').value = guestCounts.child;

        updateGuestSummary();
    }

    function toggleGuestMenu() {
        const menu = document.getElementById('guest-menu');
        menu.classList.toggle('active');
    }

    function changeGuest(type, delta) {
        const minValues = { room: 1, adult: 1, child: 0 };
        const maxValues = { room: 10, adult: 20, child: 10 };

        guestCounts[type] = Math.max(minValues[type], Math.min(maxValues[type], guestCounts[type] + delta));

        document.getElementById(`${type}-count`).textContent = guestCounts[type];

        document.getElementById('rooms-input').value = guestCounts.room;
        document.getElementById('adults-input').value = guestCounts.adult;
        document.getElementById('children-input').value = guestCounts.child;

        updateGuestSummary();
    }

    function updateGuestSummary() {
        const summary = `${guestCounts.room} Kamar · ${guestCounts.adult} Dewasa${guestCounts.child > 0 ? ' · ' + guestCounts.child + ' Anak' : ''}`;
        document.getElementById('guest-summary').textContent = summary;
    }

    document.addEventListener('click', function (event) {
        const guestDropdown = document.querySelector('.guest-dropdown');
        const guestMenu = document.getElementById('guest-menu');

        if (guestDropdown && !guestDropdown.contains(event.target)) {
            guestMenu.classList.remove('active');
        }
    });

    initGuestDisplay();

    // ========= SMOOTH SCROLL =========
    document.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', function (e) {
            if (!this.closest('.disabled')) {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });
    });
</script>

<?php
require_once 'footer.php';
?>