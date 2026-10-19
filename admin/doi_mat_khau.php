
<?php
require_once __DIR__ . '/_core.php';

// Chỉ admin đã đăng nhập mới được truy cập
guard();

$loi = '';
$thanhCong = '';

// Xử lý khi admin gửi form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Kiểm tra CSRF token
    verify();

    $matKhauCu = $_POST['mat_khau_cu'] ?? '';
    $matKhauMoi = $_POST['mat_khau_moi'] ?? '';
    $xacNhan = $_POST['xac_nhan'] ?? '';

    // Lấy thông tin admin hiện tại
    $stmt = $conn->prepare("
        SELECT ma_admin, mat_khau
        FROM admin
        WHERE ma_admin = ?
        LIMIT 1
    ");

    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kiểm tra dữ liệu
    if (
        $matKhauCu === '' ||
        $matKhauMoi === '' ||
        $xacNhan === ''
    ) {
        $loi = 'Vui lòng nhập đầy đủ thông tin.';
    } elseif (
        !$admin ||
        !password_verify($matKhauCu, $admin['mat_khau'])
    ) {
        $loi = 'Mật khẩu hiện tại không chính xác.';
    } elseif (strlen($matKhauMoi) < 8) {
        $loi = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
    } elseif (
        !preg_match('/[A-Z]/', $matKhauMoi) ||
        !preg_match('/[a-z]/', $matKhauMoi) ||
        !preg_match('/[0-9]/', $matKhauMoi) ||
        !preg_match('/[^A-Za-z0-9]/', $matKhauMoi)
    ) {
        $loi = 'Mật khẩu mới phải có chữ hoa, chữ thường, số và ký tự đặc biệt.';
    } elseif ($matKhauMoi !== $xacNhan) {
        $loi = 'Xác nhận mật khẩu mới không khớp.';
    } elseif ($matKhauMoi === $matKhauCu) {
        $loi = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
    } else {

        // Mã hóa mật khẩu mới
        $matKhauMaHoa = password_hash(
            $matKhauMoi,
            PASSWORD_DEFAULT
        );

        // Chỉ cập nhật nếu mật khẩu hiện tại vẫn khớp
        $stmt = $conn->prepare("
            UPDATE admin
            SET mat_khau = ?
            WHERE ma_admin = ?
              AND mat_khau = ?
        ");

        $stmt->execute([
            $matKhauMaHoa,
            $_SESSION['admin_id'],
            $admin['mat_khau']
        ]);

        if ($stmt->rowCount() === 1) {

            // Làm mới phiên đăng nhập
            session_regenerate_id(true);
            $_SESSION['admin_token'] = bin2hex(random_bytes(32));

            $thanhCong = 'Đổi mật khẩu thành công!';
        } else {
            $loi = 'Mật khẩu đã thay đổi. Vui lòng thử lại.';
        }
    }
}

// Hiển thị giao diện admin
layout('Đổi mật khẩu');
?>

<section class="panel password-panel">

    <h2>Đổi mật khẩu quản trị viên</h2>

    <p>
        Để bảo vệ tài khoản, hãy sử dụng mật khẩu mạnh
        và không chia sẻ mật khẩu cho người khác.
    </p>

    <?php if ($loi !== ''): ?>
        <div class="password-error">
            <?= h($loi) ?>
        </div>
    <?php endif; ?>

    <?php if ($thanhCong !== ''): ?>
        <div class="password-success">
            <?= h($thanhCong) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <input
            type="hidden"
            name="token"
            value="<?= h(token()) ?>"
        >

        <div class="password-field">
            <label for="mat_khau_cu">
                Mật khẩu hiện tại
            </label>

            <input
                type="password"
                id="mat_khau_cu"
                name="mat_khau_cu"
                autocomplete="current-password"
                required
            >
        </div>

        <div class="password-field">
            <label for="mat_khau_moi">
                Mật khẩu mới
            </label>

            <input
                type="password"
                id="mat_khau_moi"
                name="mat_khau_moi"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <div class="password-field">
            <label for="xac_nhan">
                Xác nhận mật khẩu mới
            </label>

            <input
                type="password"
                id="xac_nhan"
                name="xac_nhan"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <p class="password-hint">
            Mật khẩu phải có ít nhất 8 ký tự,
            gồm chữ hoa, chữ thường, số và ký tự đặc biệt.
        </p>

        <button type="submit" class="password-submit">
            Đổi mật khẩu
        </button>

    </form>

</section>

<?php done(); ?>
