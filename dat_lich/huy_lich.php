
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Phai dang nhap moi duoc huy lich
if (!isset($_SESSION['ma_kh'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Chi chap nhan yeu cau POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: lich_cua_toi.php');
    exit;
}

// Kiem tra token chong yeu cau gia mao
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_huy_lich']) ||
    !hash_equals($_SESSION['csrf_huy_lich'], $token)
) {
    header('Location: lich_cua_toi.php?loi=token');
    exit;
}

$maKh = (int)$_SESSION['ma_kh'];
$maLich = filter_input(
    INPUT_POST,
    'ma_lich',
    FILTER_VALIDATE_INT
);

if (!$maLich || $maLich < 1) {
    header('Location: lich_cua_toi.php?loi=malich');
    exit;
}

try {
    // Chi cho huy lich cua dung khach hang
    // va dang o trang thai Cho xac nhan
    $stmt = $conn->prepare(
        "UPDATE dat_lich
         SET trang_thai = 'Đã hủy'
         WHERE ma_lich = ?
           AND ma_kh = ?
           AND trang_thai = 'Chờ xác nhận'
           AND TIMESTAMP(ngay_hen, gio_hen) > NOW()"
    );

    $stmt->execute([$maLich, $maKh]);

    if ($stmt->rowCount() === 1) {
        header('Location: lich_cua_toi.php?huy=thanhcong');
        exit;
    }

    header('Location: lich_cua_toi.php?loi=khongthehuy');
    exit;

} catch (PDOException $e) {
    header('Location: lich_cua_toi.php?loi=hethong');
    exit;
}
