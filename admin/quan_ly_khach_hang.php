
<?php
require_once __DIR__ . '/_core.php';
guard();

// =============================================
// DUNG BY CAR - QUAN LY KHACH HANG
// =============================================

// Tìm kiếm khách hàng
$timKiem = trim((string)($_GET['q'] ?? ''));

// Mã khách hàng cần xem chi tiết
$maKh = filter_input(
    INPUT_GET,
    'ma_kh',
    FILTER_VALIDATE_INT
);

if (!$maKh || $maKh < 1) {
    $maKh = 0;
}

// =============================================
// LẤY DANH SÁCH KHÁCH HÀNG
// =============================================

$sql = "
    SELECT
        k.ma_kh,
        k.ho_ten,
        k.sdt,
        k.email,

        (
            SELECT COUNT(*)
            FROM xe x
            WHERE x.ma_kh = k.ma_kh
        ) AS so_xe,

        (
            SELECT COUNT(*)
            FROM dat_lich l
            WHERE l.ma_kh = k.ma_kh
        ) AS so_lich

    FROM khach_hang k
";

$params = [];

if ($timKiem !== '') {
    $sql .= "
        WHERE k.ho_ten LIKE ?
           OR k.sdt LIKE ?
           OR k.email LIKE ?
    ";

    $keyword = '%' . $timKiem . '%';

    $params = [
        $keyword,
        $keyword,
        $keyword
    ];
}

$sql .= " ORDER BY k.ma_kh DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$dsKhachHang = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =============================================
// LẤY THÔNG TIN CHI TIẾT KHÁCH HÀNG
// =============================================

$khachChiTiet = null;
$dsXe = [];
$dsLich = [];

if ($maKh > 0) {

    // Thông tin khách hàng
    $stmt = $conn->prepare("
        SELECT ma_kh, ho_ten, sdt, email
        FROM khach_hang
        WHERE ma_kh = ?
    ");

    $stmt->execute([$maKh]);

    $khachChiTiet = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($khachChiTiet) {

        // Danh sách xe khách hàng sở hữu
        // Đã bổ sung năm sản xuất
        $stmt = $conn->prepare("
            SELECT
                ma_xe,
                bien_so,
                hang_xe,
                model,
                nam_sx
            FROM xe
            WHERE ma_kh = ?
            ORDER BY ma_xe DESC
        ");

        $stmt->execute([$maKh]);

        $dsXe = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lịch sử đặt lịch và hóa đơn
        $stmt = $conn->prepare("
            SELECT
                l.ma_lich,
                l.ngay_hen,
                l.gio_hen,
                l.trang_thai,
                x.bien_so,
                x.hang_xe,
                x.model,
                h.ma_hd,
                h.tong_tien,
                h.trang_thai AS trang_thai_hd

            FROM dat_lich l

            LEFT JOIN xe x
                ON x.ma_xe = l.ma_xe

            LEFT JOIN hoa_don h
                ON h.ma_lich = l.ma_lich

            WHERE l.ma_kh = ?

            ORDER BY l.ngay_hen DESC,
                     l.gio_hen DESC,
                     l.ma_lich DESC
        ");

        $stmt->execute([$maKh]);

        $dsLich = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// =============================================
// GIAO DIỆN
// =============================================

layout('Quản lý khách hàng');
?>

<style>
.kh-card {
    background: #102237;
    border: 1px solid #29415a;
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 22px;
    color: #f8fafc;
}

.kh-card h2 {
    margin: 0 0 18px;
    color: #fff;
}

.kh-search {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.kh-search input {
    flex: 1;
    min-width: 220px;
    padding: 12px 14px;
    background: #071321;
    color: white;
    border: 1px solid #40536a;
    border-radius: 8px;
    font-size: 14px;
}

.kh-btn {
    display: inline-block;
    padding: 10px 15px;
    background: #ff782b;
    color: #111;
    border: none;
    border-radius: 7px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
}

.kh-btn:hover {
    background: #ff9857;
}

.kh-btn-secondary {
    background: #253e58;
    color: white;
}

.kh-btn-secondary:hover {
    background: #365674;
}

.kh-table-wrap {
    overflow-x: auto;
}

.kh-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 750px;
}

.kh-table th,
.kh-table td {
    padding: 13px 12px;
    border-bottom: 1px solid #34445a;
    text-align: left;
}

.kh-table th {
    color: #ff995b;
    font-weight: 700;
}

.kh-table tr:hover td {
    background: rgba(255, 255, 255, 0.035);
}

.kh-count {
    display: inline-block;
    padding: 5px 10px;
    background: #253e58;
    border-radius: 6px;
    font-weight: 700;
}

.kh-info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 18px;
}

.kh-info-box {
    background: #071321;
    border: 1px solid #29415a;
    border-radius: 10px;
    padding: 15px;
}

.kh-info-box span {
    display: block;
    color: #a8bbcf;
    font-size: 13px;
    margin-bottom: 7px;
}

.kh-info-box strong {
    font-size: 17px;
    color: white;
    overflow-wrap: anywhere;
}

.kh-status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    background: #253e58;
    color: #dce6f3;
}

.kh-status-done {
    background: #dcfce7;
    color: #166534;
}

.kh-status-cancel {
    background: #fee2e2;
    color: #991b1b;
}

.kh-status-paid {
    background: #dcfce7;
    color: #166534;
}

.kh-status-unpaid {
    background: #fef3c7;
    color: #92400e;
}

.kh-total {
    color: #ff995b;
    font-weight: 700;
    white-space: nowrap;
}

.kh-muted {
    color: #a8bbcf;
    font-size: 13px;
}

.kh-detail-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.kh-detail-header h2 {
    margin: 0;
}

@media (max-width: 800px) {
    .kh-info-grid {
        grid-template-columns: 1fr;
    }

    .kh-card {
        padding: 15px;
    }

    .kh-search {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<!-- TÌM KIẾM KHÁCH HÀNG -->

<section class="kh-card">

    <h2>Quản lý khách hàng</h2>

    <form method="GET" class="kh-search">

        <input
            type="text"
            name="q"
            placeholder="Tìm theo tên, số điện thoại hoặc email..."
            value="<?= h($timKiem) ?>"
        >

        <button type="submit" class="kh-btn">
            🔍 Tìm kiếm
        </button>

        <a
            href="quan_ly_khach_hang.php"
            class="kh-btn kh-btn-secondary"
        >
            Xem tất cả
        </a>

    </form>

</section>

<!-- DANH SÁCH KHÁCH HÀNG -->

<section class="kh-card">

    <h2>
        Danh sách khách hàng
        (<?= count($dsKhachHang) ?>)
    </h2>

    <div class="kh-table-wrap">

        <table class="kh-table">

            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Họ tên</th>
                    <th>Điện thoại</th>
                    <th>Email</th>
                    <th>Số xe</th>
                    <th>Số lịch</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($dsKhachHang as $kh): ?>

                    <tr>

                        <td>
                            #<?= (int)$kh['ma_kh'] ?>
                        </td>

                        <td>
                            <strong>
                                <?= h($kh['ho_ten']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= h($kh['sdt']) ?>
                        </td>

                        <td>
                            <?= h($kh['email']) ?>
                        </td>

                        <td>
                            <span class="kh-count">
                                <?= (int)$kh['so_xe'] ?>
                            </span>
                        </td>

                        <td>
                            <span class="kh-count">
                                <?= (int)$kh['so_lich'] ?>
                            </span>
                        </td>

                        <td>
                            <a
                                class="kh-btn"
                                href="quan_ly_khach_hang.php?ma_kh=<?= (int)$kh['ma_kh'] ?>&amp;q=<?= urlencode($timKiem) ?>#chi-tiet"
                            >
                                Xem chi tiết
                            </a>
                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($dsKhachHang)): ?>

                    <tr>
                        <td colspan="7">
                            Không tìm thấy khách hàng phù hợp.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>

<!-- CHI TIẾT KHÁCH HÀNG -->

<?php if ($maKh > 0): ?>

<section class="kh-card" id="chi-tiet">

    <?php if (!$khachChiTiet): ?>

        <h2>Không tìm thấy khách hàng</h2>

        <p>
            Khách hàng này không tồn tại trong hệ thống.
        </p>

    <?php else: ?>

        <div class="kh-detail-header">

            <h2>
                Chi tiết khách hàng
                #<?= (int)$khachChiTiet['ma_kh'] ?>
            </h2>

            <a
                href="quan_ly_khach_hang.php<?= $timKiem !== '' ? '?q=' . urlencode($timKiem) : '' ?>"
                class="kh-btn kh-btn-secondary"
            >
                ✕ Đóng chi tiết
            </a>

        </div>

        <!-- THÔNG TIN KHÁCH HÀNG -->

        <div class="kh-info-grid">

            <div class="kh-info-box">
                <span>Họ và tên</span>

                <strong>
                    <?= h($khachChiTiet['ho_ten']) ?>
                </strong>
            </div>

            <div class="kh-info-box">
                <span>Số điện thoại</span>

                <strong>
                    <?= h($khachChiTiet['sdt']) ?>
                </strong>
            </div>

            <div class="kh-info-box">
                <span>Email</span>

                <strong>
                    <?= h($khachChiTiet['email']) ?>
                </strong>
            </div>

        </div>

        <!-- DANH SÁCH XE -->

        <h2>
            🚗 Danh sách xe
            (<?= count($dsXe) ?>)
        </h2>

        <div class="kh-table-wrap">

            <table class="kh-table">

                <thead>
                    <tr>
                        <th>Mã xe</th>
                        <th>Biển số</th>
                        <th>Hãng xe</th>
                        <th>Model</th>
                        <th>Năm sản xuất</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($dsXe as $xe): ?>

                        <tr>

                            <td>
                                #<?= (int)$xe['ma_xe'] ?>
                            </td>

                            <td>
                                <strong>
                                    <?= h($xe['bien_so']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= h($xe['hang_xe']) ?>
                            </td>

                            <td>
                                <?= h($xe['model']) ?>
                            </td>

                            <td>
                                <?= $xe['nam_sx'] !== null
                                    && $xe['nam_sx'] !== ''
                                    ? h($xe['nam_sx'])
                                    : 'Chưa cập nhật' ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (empty($dsXe)): ?>

                        <tr>
                            <td colspan="5">
                                Khách hàng chưa có xe nào.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- LỊCH SỬ ĐẶT LỊCH -->

        <h2 style="margin-top:30px;">
            📅 Lịch sử đặt lịch
            (<?= count($dsLich) ?>)
        </h2>

        <div class="kh-table-wrap">

            <table class="kh-table">

                <thead>
                    <tr>
                        <th>Mã lịch</th>
                        <th>Ngày hẹn</th>
                        <th>Xe</th>
                        <th>Trạng thái lịch</th>
                        <th>Hóa đơn</th>
                        <th>Tổng tiền</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($dsLich as $lich): ?>

                        <tr>

                            <td>
                                #<?= (int)$lich['ma_lich'] ?>
                            </td>

                            <td>
                                <?= h($lich['ngay_hen']) ?>

                                <div class="kh-muted">
                                    <?= h($lich['gio_hen']) ?>
                                </div>
                            </td>

                            <td>
                                <?= h($lich['hang_xe']) ?>
                                <?= h($lich['model']) ?>

                                <div class="kh-muted">
                                    <?= h($lich['bien_so']) ?>
                                </div>
                            </td>

                            <td>

                                <?php
                                $statusClass = 'kh-status';

                                if ($lich['trang_thai'] === 'Hoàn thành') {
                                    $statusClass .= ' kh-status-done';
                                }

                                if ($lich['trang_thai'] === 'Đã hủy') {
                                    $statusClass .= ' kh-status-cancel';
                                }
                                ?>

                                <span class="<?= $statusClass ?>">
                                    <?= h($lich['trang_thai']) ?>
                                </span>

                            </td>

                            <td>

                                <?php if ($lich['ma_hd']): ?>

                                    <div>
                                        #<?= (int)$lich['ma_hd'] ?>
                                    </div>

                                    <?php if (
                                        $lich['trang_thai_hd'] === 'Đã thanh toán'
                                    ): ?>

                                        <span class="kh-status kh-status-paid">
                                            Đã thanh toán
                                        </span>

                                    <?php else: ?>

                                        <span class="kh-status kh-status-unpaid">
                                            Chưa thanh toán
                                        </span>

                                    <?php endif; ?>

                                <?php else: ?>

                                    <span class="kh-muted">
                                        Chưa lập hóa đơn
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($lich['ma_hd']): ?>

                                    <span class="kh-total">
                                        <?= cash($lich['tong_tien']) ?>
                                    </span>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (empty($dsLich)): ?>

                        <tr>
                            <td colspan="6">
                                Khách hàng chưa có lịch hẹn nào.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>

<?php endif; ?>

<?php done(); ?>
