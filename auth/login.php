
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['ma_kh'])) {
    header('Location: ../index.php');
    exit;
}

$loi = '';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $matKhau = $_POST['mat_khau'] ?? '';

    if ($email === '' || $matKhau === '') {
        $loi = 'Vui lòng nhập email và mật khẩu.';
    } else {

        $stmt = $conn->prepare(
            "SELECT ma_kh, ho_ten, email, mat_khau
             FROM khach_hang
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $khachHang = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $khachHang &&
            password_verify(
                $matKhau,
                $khachHang['mat_khau']
            )
        ) {

            session_regenerate_id(true);

            $_SESSION['ma_kh'] = $khachHang['ma_kh'];
            $_SESSION['ho_ten'] = $khachHang['ho_ten'];
            $_SESSION['email'] = $khachHang['email'];

            header('Location: ../index.php');
            exit;

        } else {
            $loi = 'Email hoặc mật khẩu không chính xác.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Đăng nhập - DŨNG BY CAR</title>

    <link rel="stylesheet"
          href="../assets/css/auth.css">
</head>

<body>

<div class="auth-box">

    <a href="../index.php" class="back">
        ← Quay về trang chủ
    </a>

    <h1>DŨNG <span>BY CAR</span></h1>

    <h2>Đăng nhập</h2>

    <p class="subtitle">
        Chào mừng bạn quay trở lại DŨNG BY CAR
    </p>

    <?php if ($loi): ?>
        <div class="alert error">
            <?= e($loi) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Email</label>
        <input
            type="email"
            name="email"
            placeholder="Nhập email"
            value="<?= e($_POST['email'] ?? '') ?>"
            required
        >

        <label>Mật khẩu</label>
        <input
            type="password"
            name="mat_khau"
            placeholder="Nhập mật khẩu"
            required
        >

        <button type="submit" class="button">
            Đăng nhập
        </button>

    </form>

    <p class="bottom-text">
        Chưa có tài khoản?
        <a href="dangki.php">Đăng ký ngay</a>
    </p>

</div>

</body>
</html>
