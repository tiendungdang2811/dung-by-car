
<?php
require_once __DIR__ . '/_core.php';

// Nếu admin đã đăng nhập thì chuyển đến trang tổng quan
if (!empty($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

$error = '';

// Xử lý khi admin gửi form đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Kiểm tra CSRF token
    verify();

    // Lấy dữ liệu từ form
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Tìm tài khoản admin
    $stmt = $conn->prepare("
        SELECT *
        FROM admin
        WHERE ten_dang_nhap = ?
        LIMIT 1
    ");

    $stmt->execute([$username]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kiểm tra mật khẩu
    if (
        $admin &&
        password_verify($password, $admin['mat_khau'])
    ) {

        // Tạo lại session ID để bảo mật
        session_regenerate_id(true);

        // Lưu thông tin admin vào session
        $_SESSION['admin_id'] = $admin['ma_admin'];
        $_SESSION['admin_name'] = $admin['ho_ten'];

        // Chuyển đến trang tổng quan
        redirect('dashboard.php');
    }

    // Thông báo khi đăng nhập thất bại
    $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Đăng nhập Admin | DŨNG BY CAR</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >
</head>

<body>

    <div class="login">

        <h1>
            DŨNG <em>BY CAR</em>
        </h1>

        <h2>Đăng nhập quản trị</h2>

        <?php if ($error !== ''): ?>
            <p style="color: #ff7777;">
                <?= h($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="token"
                value="<?= h(token()) ?>"
            >

            <label for="username">
                Tên đăng nhập
            </label>

            <input
                type="text"
                id="username"
                name="username"
                required
                autocomplete="username"
            >

            <label for="password">
                Mật khẩu
            </label>

            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
            >

            <button type="submit">
                Đăng nhập
            </button>

        </form>

        <a href="../index.php">
            ← Trang chủ
        </a>

    </div>

</body>
</html>
