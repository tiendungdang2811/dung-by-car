
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['ma_kh'])) {
    header('Location: ../index.php');
    exit;
}

$loi = '';
$thanhCong = '';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hoTen = trim($_POST['ho_ten'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matKhau = $_POST['mat_khau'] ?? '';
    $xacNhan = $_POST['xac_nhan'] ?? '';

    if (
        $hoTen === '' ||
        $sdt === '' ||
        $email === '' ||
        $matKhau === '' ||
        $xacNhan === ''
    ) {
        $loi = 'Vui lòng nhập đầy đủ thông tin.';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loi = 'Email không hợp lệ.';
    }
    elseif (!preg_match('/^0[0-9]{9}$/', $sdt)) {
        $loi = 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.';
    }
    elseif (strlen($matKhau) < 8) {
        $loi = 'Mật khẩu phải có ít nhất 8 ký tự.';
    }
    elseif ($matKhau !== $xacNhan) {
        $loi = 'Mật khẩu xác nhận không khớp.';
    }
    else {
        $stmt = $conn->prepare(
            "SELECT ma_kh FROM khach_hang
             WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $loi = 'Email này đã được đăng ký.';
        } else {

            $matKhauMaHoa = password_hash(
                $matKhau,
                PASSWORD_DEFAULT
            );

            try {
                $stmt = $conn->prepare(
                    "INSERT INTO khach_hang
                     (ho_ten, sdt, email, mat_khau)
                     VALUES (?, ?, ?, ?)"
                );

                $stmt->execute([
                    $hoTen,
                    $sdt,
                    $email,
                    $matKhauMaHoa
                ]);

                $thanhCong = 'Đăng ký tài khoản thành công!';
            }
            catch (PDOException $e) {
                $loi = 'Không thể đăng ký. Vui lòng thử lại.';
            }
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

    <title>Đăng ký - DŨNG BY CAR</title>

    <link rel="stylesheet"
          href="../assets/css/auth.css">
</head>

<body>

<div class="auth-box">

    <a href="../index.php" class="back">
        ← Quay về trang chủ
    </a>

    <h1>DŨNG <span>BY CAR</span></h1>

    <h2>Đăng ký tài khoản</h2>

    <p class="subtitle">
        Tạo tài khoản để đặt lịch sửa chữa và bảo dưỡng ô tô
    </p>

    <?php if ($loi): ?>
        <div class="alert error">
            <?= e($loi) ?>
        </div>
    <?php endif; ?>

    <?php if ($thanhCong): ?>

        <div class="alert success">
            <?= e($thanhCong) ?>
        </div>

        <a href="login.php" class="button">
            Đăng nhập ngay
        </a>

    <?php else: ?>

    <form method="POST">

        <label>Họ và tên</label>
        <input
            type="text"
            name="ho_ten"
            placeholder="Nhập họ và tên"
            maxlength="100"
            value="<?= e($_POST['ho_ten'] ?? '') ?>"
            required
        >

        <label>Số điện thoại</label>
        <input
            type="tel"
            name="sdt"
            placeholder="Nhập số điện thoại"
            maxlength="10"
            value="<?= e($_POST['sdt'] ?? '') ?>"
            required
        >

        <label>Email</label>
        <input
            type="email"
            name="email"
            placeholder="Nhập địa chỉ email"
            value="<?= e($_POST['email'] ?? '') ?>"
            required
        >

        <label>Mật khẩu</label>
        <input
            type="password"
            name="mat_khau"
            placeholder="Nhập mật khẩu (ít nhất 8 ký tự)"
            minlength="8"
            required
        >

        <label>Xác nhận mật khẩu</label>
        <input
            type="password"
            name="xac_nhan"
            placeholder="Nhập lại mật khẩu"
            minlength="8"
            required
        >

        <button type="submit" class="button">
            Đăng ký
        </button>

    </form>

    <?php endif; ?>

    <p class="bottom-text">
        Đã có tài khoản?
        <a href="login.php">Đăng nhập</a>
    </p>

</div>

</body>
</html>
