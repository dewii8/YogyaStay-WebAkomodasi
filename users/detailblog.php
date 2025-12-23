<?php
require_once '../config.php';

$id = $_GET['id'] ?? 0;

$query = mysqli_query($conn, "
    SELECT * FROM blog
    WHERE id_blog = '$id' AND status = 'publish'
");

$data = mysqli_fetch_assoc($query);
if (!$data) {
    echo "Artikel tidak ditemukan";
    exit;
}

$page_title = $data['judul'];
include 'header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $data['judul']; ?></title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
* {
    font-family: 'Poppins', sans-serif;
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #FFF9F2;
}

.detail-container {
    max-width: 1000px;
    margin: 60px auto;
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.detail-container img {
    width: 100%;
    border-radius: 16px;
    margin-bottom: 25px;
}

.meta {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: #777;
    margin-bottom: 12px;
}

.badge {
    background: #F2D965;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.detail-container h1 {
    font-size: 28px;
    margin-bottom: 20px;
}

.detail-content {
    font-size: 15px;
    line-height: 1.8;
    color: #333;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .detail-container {
        padding: 25px;
        margin: 30px 15px;
    }

    .detail-container h1 {
        font-size: 22px;
    }
}
</style>
</head>

<body>

<div class="detail-container">
    <img src="../assets/blog/<?= $data['thumbnail']; ?>" alt="<?= $data['judul']; ?>">

    <div class="meta">
        <span class="badge">Hotel</span>
        <span><?= date('d F Y', strtotime($data['tanggal_publish'])); ?></span>
        <span>Admin</span>
    </div>

    <h1><?= htmlspecialchars($data['judul']); ?></h1>

    <div class="detail-content">
        <?= nl2br($data['konten']); ?>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>