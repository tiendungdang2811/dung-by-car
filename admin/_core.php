
<?php
// ==========================================
// KHỞI TẠO SESSION VÀ KẾT NỐI DATABASE
// ==========================================

session_start();

require_once __DIR__ . '/../config/database.php';

// Bật chế độ báo lỗi PDO
$conn->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

// ==========================================
// HÀM HIỂN THỊ DỮ LIỆU AN TOÀN
// ==========================================

function h($v)
{
    return htmlspecialchars(
        (string)($v ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// ==========================================
// HÀM CHUYỂN TRANG
// ==========================================

function redirect($p)
{
    header('Location: ' . $p);
    exit;
}

// ==========================================
// KIỂM TRA ĐĂNG NHẬP ADMIN
// ==========================================

function guard()
{
    if (empty($_SESSION['admin_id'])) {
        redirect('login.php');
    }
}

// ==========================================
// TẠO CSRF TOKEN
// ==========================================

function token()
{
    return $_SESSION['admin_token']
        ??= bin2hex(random_bytes(32));
}

// ==========================================
// KIỂM TRA CSRF TOKEN
// ==========================================

function verify()
{
    if (!hash_equals(token(), $_POST['token'] ?? '')) {
        exit('CSRF không hợp lệ');
    }
}

// ==========================================
// GIAO DIỆN CHUNG CỦA ADMIN
// ==========================================

function layout($title)
{
    // Các mục trên thanh điều hướng
    $links = [
        'dashboard.php'          => 'Tổng quan',
        'quan_ly_lich.php'       => 'Lịch hẹn',
        'quan_ly_dich_vu.php'    => 'Dịch vụ',
        'quan_ly_khach_hang.php' => 'Khách hàng',
        'quan_ly_hoa_don.php'    => 'Hóa đơn'
    ];

    // Phần đầu HTML
    echo '<!DOCTYPE html>
    <html lang="vi">

    <head>
        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <link
            rel="stylesheet"
            href="../assets/css/admin.css"
        >

        <title>' . h($title) . ' | DŨNG BY CAR</title>
    </head>

    <body>

    <header>

        <b>
            DŨNG <em>BY CAR</em> | ADMIN
        </b>

        <nav>';

    // Hiển thị các mục menu
    foreach ($links as $url => $name) {
        echo '<a href="' . h($url) . '">'
            . h($name)
            . '</a>';
    }

    // Nút đăng xuất và phần nội dung chính
    echo '</nav>

        <a href="logout.php">
            Đăng xuất
        </a>

    </header>

    <main>

        <h1>' . h($title) . '</h1>';

    // Hiển thị thông báo từ session
    if (isset($_SESSION['notice'])) {

        echo '<p class="notice">'
            . h($_SESSION['notice'])
            . '</p>';

        unset($_SESSION['notice']);
    }
}

// ==========================================
// KẾT THÚC GIAO DIỆN ADMIN
// ==========================================

function done()
{
    echo '</main>
    </body>
    </html>';
}

// ==========================================
// LƯU THÔNG BÁO
// ==========================================

function notice($s)
{
    $_SESSION['notice'] = $s;
}

// ==========================================
// ĐỊNH DẠNG TIỀN VNĐ
// ==========================================

function cash($n)
{
    return number_format(
        (float)$n,
        0,
        ',',
        '.'
    ) . ' đ';
}
