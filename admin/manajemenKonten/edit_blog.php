<?php
require_once '../../config.php';
require_once '../log/functions.php';



// Pastikan ID blog ada di URL
if (!isset($_GET['id'])) {
    die("ID blog tidak ditemukan.");
}

$id_blog = (int) $_GET['id'];

// Ambil data blog lama
$query = mysqli_query($conn, "SELECT * FROM blog WHERE id_blog = $id_blog");
if (!$query) {
    die("Error: " . mysqli_error($conn));
}

$blog = mysqli_fetch_assoc($query);
if (!$blog) {
    die("Blog tidak ditemukan.");
}

// Menangani update data blog
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = $_POST['judul'];
    $konten = $_POST['konten'];
    $status = $_POST['status'];
    $old_status = $blog['status']; // status sebelum update

    // Update thumbnail jika ada
    if (!empty($_FILES['thumbnail']['name'])) {
        $target_dir = "../../assets/blog/";
        $file_name = str_replace(" ", "_", basename($_FILES["thumbnail"]["name"]));
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
            $update_query = "UPDATE blog SET judul='$judul', konten='$konten', thumbnail='$file_name', status='$status' WHERE id_blog=$id_blog";
        } else {
            die("Gagal meng-upload gambar.");
        }
    } else {
        $update_query = "UPDATE blog SET judul='$judul', konten='$konten', status='$status' WHERE id_blog=$id_blog";
    }

    $result = mysqli_query($conn, $update_query);
    if ($result) {
        // ===== LOG ADMIN =====
        $aksi = "Edit Blog";
        $deskripsi = "Admin ID " . $_SESSION['user_id'] . " mengubah blog ID $id_blog berjudul '$judul'";
        if ($old_status !== $status) {
            $deskripsi .= " dari status '$old_status' menjadi '$status'";
        }
        addAdminLog($conn, $_SESSION['user_id'], $aksi, $deskripsi);
        // ===== END LOG =====

        header("Location: konten.php");
        exit;
    } else {
        die("Error update blog: " . mysqli_error($conn));
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Blog</title>
    <link rel="icon" type="image/jpeg" href="../../assets/img/logonw.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fff7ed;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin: 0 auto;
            padding: 40px;
            width: 80%;
            max-width: 1200px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .main-content h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 30px;
        }

        .blog-form {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .blog-form label {
            font-size: 16px;
            margin-bottom: 8px;
            display: block;
            color: #555;
        }

        .blog-form input,
        .blog-form textarea,
        .blog-form select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 14px;
            color: #333;
        }

        .blog-form input[type="file"] {
            padding: 8px;
        }

        .blog-form button {
            padding: 12px 24px;
            background-color: #5e6b8d;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .blog-form button:hover {
            background-color: #4a5363;
        }

        .btn-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .back-button,
        .delete-button {
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            transition: 0.3s;
        }

        .back-button {
            background-color: #f59e0b;
        }

        .back-button:hover {
            background-color: #f59e0b;
        }

        .delete-button {
            background-color: #dc3545;
        }

        .delete-button:hover {
            background-color: #c82333;
        }

        .delete-button i {
            margin-right: 8px;
        }

        @media (max-width:768px) {
            .main-content {
                padding: 20px;
                width: 95%;
            }

            .blog-form {
                padding: 20px;
            }

            .back-button,
            .delete-button {
                width: 100%;
                text-align: center;
                margin-top: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="main-content">
        <h1>Edit Postingan</h1>
        <form action="edit_blog.php?id=<?= $id_blog ?>" method="POST" enctype="multipart/form-data" class="blog-form">
            <label for="judul">Judul Postingan</label>
            <input type="text" name="judul" value="<?= htmlspecialchars($blog['judul']); ?>" required>

            <label for="konten">Isi Blog</label>
            <textarea name="konten" required><?= htmlspecialchars($blog['konten']); ?></textarea>

            <label for="thumbnail">Unggah Gambar (Kosongkan jika tidak ingin mengganti)</label>
            <input type="file" name="thumbnail" accept="image/*">

            <label for="status">Status</label>
            <select name="status" required>
                <option value="publish" <?= $blog['status'] == 'publish' ? 'selected' : ''; ?>>Publish-Tampilkan di website
                </option>
                <option value="draft" <?= $blog['status'] == 'draft' ? 'selected' : ''; ?>>Draft-Simpan sebagai draft
                </option>
            </select>

            <button type="submit">Update Postingan</button>
        </form>

        <div class="btn-container">
            <a href="konten.php" class="back-button"><i class="fas fa-arrow-left"></i> Kembali</a>
            <a href="delete_blog.php?id=<?= $id_blog ?>" class="delete-button"
                onclick="return confirm('Apakah Anda yakin ingin menghapus?');"><i class="fas fa-trash"></i> Hapus
                Postingan</a>
        </div>
    </div>
</body>

</html>