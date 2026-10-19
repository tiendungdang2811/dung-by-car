
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['ma_kh'])) {
    header('Location: ../auth/login.php');
    exit;
}

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function tien($value) {
    return number_format(
        (float)$value,
        0,
        ',',
        '.'
    ) . 'đ';
}

$maKh = (int)$_SESSION['ma_kh'];

// ========================================
// LẤY LỊCH HẸN CỦA KHÁCH HÀNG
// KÈM THÔNG TIN HÓA ĐƠN
// ========================================

$stmt = $conn->prepare("
    SELECT
        dl.ma_lich,
        dl.ngay_hen,
        dl.gio_hen,
        dl.trang_thai,
        x.bien_so,
        x.hang_xe,
        x.model,
        x.nam_sx,

        hd.ma_hd,
        hd.tong_tien AS tong_hoa_don,
        hd.trang_thai AS trang_thai_hoa_don,
        hd.ngay_lap AS ngay_lap_hoa_don

    FROM dat_lich dl

    INNER JOIN xe x
        ON dl.ma_xe = x.ma_xe

    LEFT JOIN hoa_don hd
        ON hd.ma_lich = dl.ma_lich

    WHERE dl.ma_kh = ?
      AND x.ma_kh = ?

    ORDER BY
        dl.ngay_hen DESC,
        dl.gio_hen DESC,
        dl.ma_lich DESC
");

$stmt->execute([$maKh, $maKh]);

$lichHen = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// LẤY DỊCH VỤ ĐÃ CHỌN
// BAO GỒM DỊCH VỤ PHÁT SINH TỪ DANH MỤC
// ========================================

$stmtChiTiet = $conn->prepare("
    SELECT
        dv.ten_dv,
        ct.so_luong,
        ct.don_gia,
        ct.loai_dich_vu

    FROM ct_dat_lich ct

    LEFT JOIN dich_vu dv
        ON ct.ma_dv = dv.ma_dv

    WHERE ct.ma_lich = ?

    ORDER BY
        ct.loai_dich_vu,
        ct.ma_dv
");

// ========================================
// LẤY CHI PHÍ PHÁT SINH TỰ NHẬP
// ========================================

$stmtPhatSinh = $conn->prepare("
    SELECT
        ten_khoan,
        loai,
        so_luong,
        don_gia,
        ghi_chu

    FROM phat_sinh_tu_nhap

    WHERE ma_lich = ?

    ORDER BY id ASC
");

// ========================================
// TÍNH TỔNG TIỀN TỪNG LỊCH
// ========================================

foreach ($lichHen as &$lich) {

    $maLich = (int)$lich['ma_lich'];

    // Dịch vụ trong danh mục
    $stmtChiTiet->execute([$maLich]);

    $lich['dich_vu'] =
        $stmtChiTiet->fetchAll(PDO::FETCH_ASSOC);

    // Phát sinh tự nhập
    $stmtPhatSinh->execute([$maLich]);

    $lich['phat_sinh'] =
        $stmtPhatSinh->fetchAll(PDO::FETCH_ASSOC);

    $tongDichVu = 0;

    foreach ($lich['dich_vu'] as $dv) {
        $tongDichVu +=
            (int)$dv['so_luong'] *
            (float)$dv['don_gia'];
    }

    $tongPhatSinh = 0;

    foreach ($lich['phat_sinh'] as $ps) {
        $tongPhatSinh +=
            (int)$ps['so_luong'] *
            (float)$ps['don_gia'];
    }

    $lich['tong_dich_vu'] = $tongDichVu;
    $lich['tong_phat_sinh'] = $tongPhatSinh;

    $lich['tong_tinh_toan'] =
        $tongDichVu + $tongPhatSinh;

    // Nếu đã có hóa đơn:
    // Lấy tổng tiền đã lưu trong hóa đơn.
    // Điều này đặc biệt quan trọng với
    // hóa đơn đã thanh toán.

    if ($lich['ma_hd'] !== null) {
        $lich['tong_tien'] =
            (float)$lich['tong_hoa_don'];
    } else {
        // Chưa có hóa đơn:
        // Hiển thị tổng tạm tính
        $lich['tong_tien'] =
            $lich['tong_tinh_toan'];
    }
}

unset($lich);

// ========================================
// THÔNG BÁO
// ========================================

$thongBao = '';

if (
    isset($_GET['huy']) &&
    $_GET['huy'] === 'thanhcong'
) {
    $thongBao = 'Đã hủy lịch hẹn thành công.';
}

// ========================================
// CSRF HỦY LỊCH
// ========================================

if (empty($_SESSION['csrf_huy_lich'])) {
    $_SESSION['csrf_huy_lich'] =
        bin2hex(random_bytes(32));
}

// ========================================
// KIỂM TRA ĐIỀU KIỆN HỦY
// ========================================

function coTheHuy($lich) {

    // Không cho hủy khi đã có hóa đơn
    if (!empty($lich['ma_hd'])) {
        return false;
    }

    $trangThai = mb_strtolower(
        trim($lich['trang_thai']),
        'UTF-8'
    );

    if (!in_array(
        $trangThai,
        ['chờ xác nhận', 'cho xac nhan'],
        true
    )) {
        return false;
    }

    $thoiGian = strtotime(
        $lich['ngay_hen'] . ' ' .
        $lich['gio_hen']
    );

    return $thoiGian !== false &&
           $thoiGian > time();
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

    <title>Lịch của tôi - DŨNG BY CAR</title>

    <link
        rel="stylesheet"
        href="../assets/css/booking.css?v=2"
    >
</head>

<body>

<!-- HEADER -->

<header class="booking-header">

    <div class="header-inner">

        <a href="../index.php" class="logo">
            <img
                src="../assets/images/dung-by-car-logo.png"
                alt="DŨNG BY CAR"
            >
        </a>

        <div class="booking-nav">
            <a href="dat_lich.php">
                Đặt lịch mới
            </a>

            <a href="../index.php">
                ← Trang chủ
            </a>
        </div>

    </div>

</header>

<!-- NỘI DUNG -->

<main class="booking-container">

    <div class="booking-title">

        <p>
            DŨNG BY CAR · QUẢN LÝ LỊCH HẸN
        </p>

        <h1>
            Lịch hẹn <span>của tôi</span>
        </h1>

        <div>
            Xin chào,

            <strong>
                <?= e(
                    $_SESSION['ho_ten'] ??
                    'Khách hàng'
                ) ?>
            </strong>.

            Đây là những lịch hẹn bạn đã đặt.
        </div>

    </div>

    <!-- THÔNG BÁO -->

    <?php if ($thongBao): ?>

        <div class="alert success">
            <?= e($thongBao) ?>
        </div>

    <?php endif; ?>

    <?php if (isset($_GET['loi'])): ?>

        <div class="alert error">
            Không thể hủy lịch hẹn.
            Lịch có thể đã được xử lý
            hoặc không còn đủ điều kiện hủy.
        </div>

    <?php endif; ?>

    <div class="page-actions">

        <a
            href="dat_lich.php"
            class="submit-btn"
        >
            + Đặt lịch mới
        </a>

    </div>

    <!-- CHƯA CÓ LỊCH -->

    <?php if (empty($lichHen)): ?>

        <div class="empty-booking">

            <div class="empty-icon">
                📅
            </div>

            <h2>
                Bạn chưa có lịch hẹn nào
            </h2>

            <p>
                Hãy đặt lịch để DŨNG BY CAR
                chăm sóc chiếc xe của bạn.
            </p>

            <a
                href="dat_lich.php"
                class="submit-btn"
            >
                Đặt lịch ngay →
            </a>

        </div>

    <?php else: ?>

        <!-- DANH SÁCH LỊCH -->

        <div class="booking-list">

            <?php foreach ($lichHen as $lich): ?>

                <?php
                $trangThai =
                    trim($lich['trang_thai']);

                $daHuy = mb_strtolower(
                    $trangThai,
                    'UTF-8'
                ) === 'đã hủy';

                $coHoaDon =
                    $lich['ma_hd'] !== null;

                $daThanhToan =
                    $coHoaDon &&
                    $lich['trang_thai_hoa_don']
                        === 'Đã thanh toán';
                ?>

                <article class="appointment-card">

                    <!-- THÔNG TIN XE -->

                    <div class="appointment-top">

                        <div>

                            <span class="appointment-id">
                                MÃ LỊCH
                                #<?= (int)$lich['ma_lich'] ?>
                            </span>

                            <h2>
                                <?= e($lich['hang_xe']) ?>
                                <?= e($lich['model']) ?>
                            </h2>

                            <p class="plate-number">
                                Biển số:
                                <?= e($lich['bien_so']) ?>
                            </p>

                        </div>

                        <span class="status-badge <?= $daHuy
                            ? 'status-cancelled'
                            : '' ?>">
                            <?= e($trangThai) ?>
                        </span>

                    </div>

                    <!-- THÔNG TIN LỊCH -->

                    <div class="appointment-info">

                        <div>
                            <small>
                                Ngày hẹn
                            </small>

                            <strong>
                                <?= date(
                                    'd/m/Y',
                                    strtotime($lich['ngay_hen'])
                                ) ?>
                            </strong>
                        </div>

                        <div>
                            <small>
                                Giờ hẹn
                            </small>

                            <strong>
                                <?= e(
                                    substr(
                                        $lich['gio_hen'],
                                        0,
                                        5
                                    )
                                ) ?>
                            </strong>
                        </div>

                        <div>
                            <small>
                                Năm sản xuất
                            </small>

                            <strong>
                                <?= e($lich['nam_sx']) ?>
                            </strong>
                        </div>

                    </div>

                    <!-- DỊCH VỤ TRONG DANH MỤC -->

                    <div class="appointment-services">

                        <h3>
                            Dịch vụ đã chọn
                        </h3>

                        <?php foreach (
                            $lich['dich_vu'] as $dv
                        ): ?>

                            <div class="appointment-service-row">

                                <span>

                                    <?= e(
                                        $dv['ten_dv'] ??
                                        'Dịch vụ'
                                    ) ?>

                                    × <?= (int)$dv['so_luong'] ?>

                                    <?php if (
                                        $dv['loai_dich_vu']
                                        === 'phat_sinh'
                                    ): ?>

                                        <small
                                            style="color:#ff995b"
                                        >
                                            (Phát sinh)
                                        </small>

                                    <?php endif; ?>

                                </span>

                                <strong>
                                    <?= tien(
                                        (float)$dv['don_gia'] *
                                        (int)$dv['so_luong']
                                    ) ?>
                                </strong>

                            </div>

                        <?php endforeach; ?>

                        <?php if (
                            empty($lich['dich_vu'])
                        ): ?>

                            <p>
                                Chưa có dịch vụ.
                            </p>

                        <?php endif; ?>

                    </div>

                    <!-- PHÁT SINH TỰ NHẬP -->

                    <?php if (
                        !empty($lich['phat_sinh'])
                    ): ?>

                        <div class="appointment-services">

                            <h3>
                                Chi phí phát sinh
                            </h3>

                            <?php foreach (
                                $lich['phat_sinh'] as $ps
                            ): ?>

                                <div class="appointment-service-row">

                                    <span>

                                        <?= e(
                                            $ps['ten_khoan']
                                        ) ?>

                                        × <?= (int)$ps['so_luong'] ?>

                                        <small
                                            style="color:#ff995b"
                                        >
                                            (<?= $ps['loai']
                                                === 'chenh_lech'
                                                ? 'Chênh lệch nâng cấp'
                                                : 'Ngoài danh mục' ?>)
                                        </small>

                                        <?php if (
                                            !empty($ps['ghi_chu'])
                                        ): ?>

                                            <small
                                                style="
                                                    display:block;
                                                    color:#94a3b8;
                                                    margin-top:5px;
                                                "
                                            >
                                                <?= e(
                                                    $ps['ghi_chu']
                                                ) ?>
                                            </small>

                                        <?php endif; ?>

                                    </span>

                                    <strong>
                                        <?= tien(
                                            (float)$ps['don_gia'] *
                                            (int)$ps['so_luong']
                                        ) ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                    <!-- TỔNG TIỀN VÀ TRẠNG THÁI HÓA ĐƠN -->

                    <div class="appointment-bottom">

                        <div>

                            <small>

                                <?php if ($coHoaDon): ?>

                                    Tổng tiền hóa đơn

                                <?php else: ?>

                                    Tổng tiền tạm tính

                                <?php endif; ?>

                            </small>

                            <strong class="appointment-total">
                                <?= tien(
                                    $lich['tong_tien']
                                ) ?>
                            </strong>

                            <?php if ($coHoaDon): ?>

                                <div
                                    style="
                                        margin-top:12px;
                                        font-size:14px;
                                    "
                                >

                                    Hóa đơn
                                    #<?= (int)$lich['ma_hd'] ?>:

                                    <?php if ($daThanhToan): ?>

                                        <strong
                                            style="color:#86efac"
                                        >
                                            ✓ Đã thanh toán
                                        </strong>

                                    <?php else: ?>

                                        <strong
                                            style="color:#fbbf24"
                                        >
                                            Chưa thanh toán
                                        </strong>

                                    <?php endif; ?>

                                </div>

                            <?php else: ?>

                                <div
                                    style="
                                        margin-top:10px;
                                        color:#94a3b8;
                                        font-size:13px;
                                    "
                                >
                                    Chưa lập hóa đơn
                                </div>

                            <?php endif; ?>

                        </div>

                        <!-- HỦY LỊCH -->

                        <?php if (coTheHuy($lich)): ?>

                            <form
                                method="POST"
                                action="huy_lich.php"
                                onsubmit="return confirm('Bạn chắc chắn muốn hủy lịch hẹn này?');"
                            >

                                <input
                                    type="hidden"
                                    name="ma_lich"
                                    value="<?= (int)$lich['ma_lich'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(
                                        $_SESSION['csrf_huy_lich']
                                    ) ?>"
                                >

                                <button
                                    type="submit"
                                    class="cancel-appointment"
                                >
                                    Hủy lịch hẹn
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<!-- FOOTER -->

<footer class="booking-footer">

    © <?= date('Y') ?> DŨNG BY CAR.
    Chăm sóc xế yêu,
    an tâm trên mọi hành trình.

</footer>

</body>
</html>
