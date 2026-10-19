
<?php
require_once __DIR__ . '/_core.php';

// Xóa thông tin đăng nhập của admin
unset(
    $_SESSION['admin_id'],
    $_SESSION['admin_name'],
    $_SESSION['admin_token']
);

// Tạo lại Session ID để bảo mật
session_regenerate_id(true);

// Chuyển về trang đăng nhập admin
redirect('login.php');
