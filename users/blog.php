<?php
require_once '../config.php';

$query = mysqli_query($conn, "
    SELECT id_blog, judul, konten, thumbnail, tanggal_publish
    FROM blog
    WHERE status = 'publish'
    ORDER BY tanggal_publish DESC
");

$page_title = 'Blog';
include 'header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blog YogyaStay</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
* {
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    margin: 0;
    background: #FFF9F2;
}

/* HEADER */
.blog-header {
    max-width: 1200px;
    margin: 60px auto 40px;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 40px;
    align-items: center;
}

.blog-header h1 {
    font-size: 40px;
    margin-bottom: 12px;
}

.blog-header p {
    color: #555;
    line-height: 1.7;
}

.blog-header img {
    width: 100%;
    border-radius: 18px;
    object-fit: cover;
}

/* TITLE */
.section-title {
    text-align: center;
    font-size: 35px;
    font-weight: 700;
    margin-bottom: 40px;
    text-decoration: underline;
}

/* BLOG GRID */
.blog-container {
    max-width: 1200px;
    margin: auto;
    padding: 0 20px 80px;
}

.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
}

.blog-card {
    background: white;
    border-radius: 18px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform .3s ease;
}

.blog-card:hover {
    transform: translateY(-6px);
}

.blog-card img {
    width: 100%;
    height: 190px;
    object-fit: cover;
}

.blog-card-content {
    padding: 20px;
}

.blog-card h3 {
    font-size: 17px;
    margin-bottom: 12px;
}

.blog-card p {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
}

.blog-card a {
    display: inline-block;
    margin-top: 12px;
    font-size: 14px;
    color: #E0B84A;
    font-weight: 600;
    text-decoration: none;
}

.read-more {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 12px;

    font-size: 14px;
    font-weight: 600;
    color: #8B9DC3;
    text-decoration: none;

    transition: 
        color 0.25s ease,
        transform 0.25s ease;
}

.read-more i {
    transition: transform 0.25s ease;
}

.read-more:hover {
    color: #E8D06F;
    transform: translateX(3px);
}

.read-more:hover i {
    transform: translateX(4px);
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .blog-header {
        grid-template-columns: 1fr;
        text-align: center;
    }
}
</style>
</head>

<body>
<section class="blog-header">
    <div>
        <h1>Blog Tips &<br>Info Akomodasi</h1>
        <p>
            Blog Dashboard — pusat inspirasi dan info seputar staycation,
            travel, dan pengalaman menginap di YogyaStay.
        </p>
    </div>
    <img src="../assets/blog/blog-hero.jpg" alt="Blog Hero">
</section>

<h2 class="section-title">Daftar Artikel dan Berita</h2>

<div class="blog-container">
    <div class="blog-grid">
        <?php while ($row = mysqli_fetch_assoc($query)) : ?>
            <div class="blog-card">
                <img src="../assets/blog/<?= $row['thumbnail']; ?>" alt="<?= $row['judul']; ?>">
                <div class="blog-card-content">
                    <h3><?= htmlspecialchars($row['judul']); ?></h3>
                    <p>
                        <?= substr(strip_tags($row['konten']), 0, 120); ?>...
                    </p>
                    <a href="detailblog.php?id=<?= $row['id_blog']; ?>" class="read-more">
                        Baca Selengkapnya
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>