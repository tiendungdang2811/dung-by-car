
<?php
require_once __DIR__ . '/_core.php';

// Kiểm tra quyền đăng nhập admin
guard();

// ==========================================
// LẤY SỐ LIỆU THỐNG KÊ
// ==========================================

$bangThongKe = [
    'khach_hang' => 'Khách hàng',
    'xe'         => 'Xe',
    'dich_vu'    => 'Dịch vụ',
    'dat_lich'   => 'Lịch hẹn',
    'hoa_don'    => 'Hóa đơn'
];

$thongKe = [];

foreach ($bangThongKe as $tenBang => $nhan) {

    $stmt = $conn->query("
        SELECT COUNT(*)
        FROM `$tenBang`
    ");

    $thongKe[] = [
        'nhan' => $nhan,
        'so_luong' => $stmt->fetchColumn()
    ];
}

// ==========================================
// TÍNH DOANH THU ĐÃ THANH TOÁN
// ==========================================

$stmt = $conn->query("
    SELECT COALESCE(SUM(tong_tien), 0)
    FROM hoa_don
    WHERE trang_thai = 'Đã thanh toán'
");

$doanhThu = $stmt->fetchColumn();

// ==========================================
// LẤY 10 LỊCH HẸN MỚI NHẤT
// ==========================================

$stmt = $conn->query("
    SELECT
        l.ma_lich,
        l.ngay_hen,
        l.trang_thai,
        k.ho_ten
    FROM dat_lich l
    LEFT JOIN khach_hang k
        ON k.ma_kh = l.ma_kh
    ORDER BY l.ma_lich DESC
    LIMIT 10
");

$lichHenMoi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// HIỂN THỊ GIAO DIỆN
// ==========================================

layout('Tổng quan');
?>

<!-- ======================================
     CÁC Ô THỐNG KÊ
====================================== -->

<?php foreach ($thongKe as $item): ?>

    <section class="stat">

        <span>
            <?= h($item['nhan']) ?>
        </span>

        <strong>
            <?= h($item['so_luong']) ?>
        </strong>

    </section>

<?php endforeach; ?>

<!-- DOANH THU ĐÃ THANH TOÁN -->

<section class="stat">

    <span>
        Doanh thu đã thanh toán
    </span>

    <strong>
        <?= cash($doanhThu) ?>
    </strong>

</section>

<!-- ======================================
     DANH SÁCH LỊCH HẸN MỚI
====================================== -->

<section class="panel">

    <h2>Lịch hẹn mới</h2>

    <table>

        <thead>
            <tr>
                <th>Mã</th>
                <th>Khách hàng</th>
                <th>Ngày</th>
                <th>Trạng thái</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($lichHenMoi as $lich): ?>

                <tr>

                    <td>
                        #<?= h($lich['ma_lich']) ?>
                    </td>

                    <td>
                        <?= h($lich['ho_ten']) ?>
                    </td>

                    <td>
                        <?= h($lich['ngay_hen']) ?>
                    </td>

                    <td>
                        <?= h($lich['trang_thai']) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

            <?php if (empty($lichHenMoi)): ?>

                <tr>
                    <td colspan="4">
                        Chưa có lịch hẹn nào.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</section>

<?php done(); ?>
