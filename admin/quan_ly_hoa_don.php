
<?php
require_once __DIR__ . '/_core.php';
guard();

// ======================================
// HÀM XỬ LÝ HÓA ĐƠN
// ======================================

function hdTong($conn, $maLich) {
    $s = $conn->prepare("
        SELECT COALESCE(SUM(so_luong * don_gia), 0)
        FROM ct_dat_lich
        WHERE ma_lich = ?
    ");
    $s->execute([$maLich]);
    $tong = (float)$s->fetchColumn();

    $s = $conn->prepare("
        SELECT COALESCE(SUM(so_luong * don_gia), 0)
        FROM phat_sinh_tu_nhap
        WHERE ma_lich = ?
    ");
    $s->execute([$maLich]);

    return $tong + (float)$s->fetchColumn();
}

function hdLich($conn, $maLich, $lock = false) {
    $sql = "
        SELECT
            l.ma_lich, l.ngay_hen, l.gio_hen,
            l.trang_thai,
            k.ho_ten, k.sdt,
            x.hang_xe, x.model, x.bien_so
        FROM dat_lich l
        LEFT JOIN khach_hang k ON k.ma_kh = l.ma_kh
        LEFT JOIN xe x ON x.ma_xe = l.ma_xe
        WHERE l.ma_lich = ?
    ";

    if ($lock) {
        $sql .= " FOR UPDATE";
    }

    $s = $conn->prepare($sql);
    $s->execute([$maLich]);

    return $s->fetch(PDO::FETCH_ASSOC);
}

function hdBill($conn, $maLich, $lock = false) {
    $sql = "
        SELECT *
        FROM hoa_don
        WHERE ma_lich = ?
        LIMIT 1
    ";

    if ($lock) {
        $sql .= " FOR UPDATE";
    }

    $s = $conn->prepare($sql);
    $s->execute([$maLich]);

    return $s->fetch(PDO::FETCH_ASSOC);
}

function hdPositiveInt($value) {
    $n = filter_var($value, FILTER_VALIDATE_INT);

    if ($n === false || $n === null || $n < 1) {
        throw new Exception('Số lượng hoặc mã không hợp lệ.');
    }

    return $n;
}

function hdValidPrice($value) {
    return is_scalar($value)
        && preg_match('/^\d+(\.\d{1,2})?$/D', (string)$value)
        && (float)$value <= 999999999999;
}

// ======================================
// XỬ LÝ THAO TÁC POST
// ======================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify();

    $maLich = (int)($_POST['ma_lich'] ?? 0);
    $act = $_POST['act'] ?? '';

    try {
        if ($maLich < 1) {
            throw new Exception('Mã lịch không hợp lệ.');
        }

        $conn->beginTransaction();

        $lich = hdLich($conn, $maLich, true);

        if (!$lich || $lich['trang_thai'] !== 'Hoàn thành') {
            throw new Exception(
                'Chỉ xử lý hóa đơn của lịch đã hoàn thành.'
            );
        }

        $bill = hdBill($conn, $maLich, true);

        // ----------------------------------
        // XÁC NHẬN THANH TOÁN
        // ----------------------------------

        if ($act === 'pay') {
            if (
                !$bill ||
                $bill['trang_thai'] !== 'Chưa thanh toán'
            ) {
                throw new Exception(
                    'Hóa đơn không ở trạng thái chờ thanh toán.'
                );
            }

            $tong = hdTong($conn, $maLich);

            if ($tong <= 0) {
                throw new Exception(
                    'Tổng tiền hóa đơn phải lớn hơn 0.'
                );
            }

            $s = $conn->prepare("
                UPDATE hoa_don
                SET tong_tien = ?,
                    trang_thai = 'Đã thanh toán'
                WHERE ma_hd = ?
                  AND trang_thai = 'Chưa thanh toán'
            ");

            $s->execute([$tong, $bill['ma_hd']]);

            if ($s->rowCount() !== 1) {
                throw new Exception(
                    'Không thể xác nhận thanh toán.'
                );
            }

            $msg = 'Đã xác nhận thanh toán và khóa hóa đơn.';
        } else {

            if (
                $bill &&
                $bill['trang_thai'] !== 'Chưa thanh toán'
            ) {
                throw new Exception(
                    'Hóa đơn đã thanh toán, không thể chỉnh sửa.'
                );
            }

            // ----------------------------------
            // LẬP HÓA ĐƠN
            // ----------------------------------

            if ($act === 'create') {
                if ($bill) {
                    throw new Exception(
                        'Lịch này đã có hóa đơn.'
                    );
                }

                $tong = hdTong($conn, $maLich);

                if ($tong <= 0) {
                    throw new Exception(
                        'Chưa có dịch vụ tính tiền.'
                    );
                }

                $s = $conn->prepare("
                    INSERT INTO hoa_don
                    (
                        ma_lich,
                        ma_admin,
                        tong_tien,
                        ngay_lap,
                        trang_thai
                    )
                    VALUES (
                        ?, ?, ?, NOW(), 'Chưa thanh toán'
                    )
                ");

                $s->execute([
                    $maLich,
                    $_SESSION['admin_id'],
                    $tong
                ]);

                $msg = 'Lập hóa đơn thành công.';
            }

            // ----------------------------------
            // THÊM DỊCH VỤ CÓ SẴN
            // ----------------------------------

            elseif ($act === 'add_catalog') {
                $maDv = hdPositiveInt(
                    $_POST['ma_dv'] ?? null
                );

                $soLuong = hdPositiveInt(
                    $_POST['so_luong'] ?? null
                );

                $s = $conn->prepare("
                    SELECT don_gia
                    FROM dich_vu
                    WHERE ma_dv = ?
                ");
                $s->execute([$maDv]);

                $donGia = $s->fetchColumn();

                if ($donGia === false) {
                    throw new Exception(
                        'Không tìm thấy dịch vụ.'
                    );
                }

                $s = $conn->prepare("
                    SELECT COUNT(*)
                    FROM ct_dat_lich
                    WHERE ma_lich = ?
                      AND ma_dv = ?
                ");

                $s->execute([$maLich, $maDv]);

                if ((int)$s->fetchColumn() > 0) {
                    throw new Exception(
                        'Dịch vụ này đã có trong lịch. Nếu nâng cấp, hãy thêm tiền chênh lệch.'
                    );
                }

                $s = $conn->prepare("
                    INSERT INTO ct_dat_lich
                    (
                        ma_lich,
                        ma_dv,
                        so_luong,
                        don_gia,
                        loai_dich_vu
                    )
                    VALUES (?, ?, ?, ?, 'phat_sinh')
                ");

                $s->execute([
                    $maLich,
                    $maDv,
                    $soLuong,
                    $donGia
                ]);

                $msg = 'Đã thêm dịch vụ từ danh mục.';
            }

            // ----------------------------------
            // SỬA DỊCH VỤ PHÁT SINH TỪ DANH MỤC
            // ----------------------------------

            elseif ($act === 'edit_catalog') {
                $maDv = hdPositiveInt(
                    $_POST['ma_dv'] ?? null
                );

                $soLuong = hdPositiveInt(
                    $_POST['so_luong'] ?? null
                );

                $donGia = $_POST['don_gia'] ?? null;

                if (!hdValidPrice($donGia)) {
                    throw new Exception(
                        'Đơn giá không hợp lệ.'
                    );
                }

                $s = $conn->prepare("
                    SELECT COUNT(*)
                    FROM ct_dat_lich
                    WHERE ma_lich = ?
                      AND ma_dv = ?
                      AND loai_dich_vu = 'phat_sinh'
                ");

                $s->execute([$maLich, $maDv]);

                if ((int)$s->fetchColumn() === 0) {
                    throw new Exception(
                        'Không tìm thấy dịch vụ phát sinh.'
                    );
                }

                $s = $conn->prepare("
                    UPDATE ct_dat_lich
                    SET so_luong = ?,
                        don_gia = ?
                    WHERE ma_lich = ?
                      AND ma_dv = ?
                      AND loai_dich_vu = 'phat_sinh'
                ");

                $s->execute([
                    $soLuong,
                    $donGia,
                    $maLich,
                    $maDv
                ]);

                $msg = 'Đã sửa dịch vụ phát sinh.';
            }

            // ----------------------------------
            // XÓA DỊCH VỤ PHÁT SINH TỪ DANH MỤC
            // ----------------------------------

            elseif ($act === 'delete_catalog') {
                $maDv = hdPositiveInt(
                    $_POST['ma_dv'] ?? null
                );

                $s = $conn->prepare("
                    DELETE FROM ct_dat_lich
                    WHERE ma_lich = ?
                      AND ma_dv = ?
                      AND loai_dich_vu = 'phat_sinh'
                ");

                $s->execute([$maLich, $maDv]);

                if ($s->rowCount() < 1) {
                    throw new Exception(
                        'Không tìm thấy dịch vụ phát sinh.'
                    );
                }

                $msg = 'Đã xóa dịch vụ phát sinh.';
            }

            // ----------------------------------
            // THÊM / SỬA PHÁT SINH TỰ NHẬP
            // ----------------------------------

            elseif (
                $act === 'add_manual' ||
                $act === 'edit_manual'
            ) {
                $ten = trim(
                    (string)($_POST['ten'] ?? '')
                );

                $ghiChu = trim(
                    (string)($_POST['ghi_chu'] ?? '')
                );

                $soLuong = hdPositiveInt(
                    $_POST['so_luong'] ?? null
                );

                $donGia = $_POST['don_gia'] ?? null;

                if (
                    $ten === '' ||
                    mb_strlen($ten, 'UTF-8') > 255 ||
                    !hdValidPrice($donGia)
                ) {
                    throw new Exception(
                        'Tên hoặc giá phát sinh không hợp lệ.'
                    );
                }

                if ($act === 'add_manual') {
                    $loai = $_POST['loai'] ?? '';

                    if (!in_array(
                        $loai,
                        ['ngoai_danh_muc', 'chenh_lech'],
                        true
                    )) {
                        throw new Exception(
                            'Loại phát sinh không hợp lệ.'
                        );
                    }

                    $s = $conn->prepare("
                        INSERT INTO phat_sinh_tu_nhap
                        (
                            ma_lich,
                            ten_khoan,
                            loai,
                            so_luong,
                            don_gia,
                            ghi_chu
                        )
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $s->execute([
                        $maLich,
                        $ten,
                        $loai,
                        $soLuong,
                        $donGia,
                        $ghiChu
                    ]);

                    $msg = 'Đã thêm khoản phát sinh.';
                } else {
                    $id = hdPositiveInt(
                        $_POST['id'] ?? null
                    );

                    $s = $conn->prepare("
                        UPDATE phat_sinh_tu_nhap
                        SET ten_khoan = ?,
                            so_luong = ?,
                            don_gia = ?,
                            ghi_chu = ?
                        WHERE id = ?
                          AND ma_lich = ?
                    ");

                    $s->execute([
                        $ten,
                        $soLuong,
                        $donGia,
                        $ghiChu,
                        $id,
                        $maLich
                    ]);

                    $msg = 'Đã cập nhật khoản phát sinh.';
                }
            }

            // ----------------------------------
            // XÓA PHÁT SINH TỰ NHẬP
            // ----------------------------------

            elseif ($act === 'delete_manual') {
                $id = hdPositiveInt(
                    $_POST['id'] ?? null
                );

                $s = $conn->prepare("
                    DELETE FROM phat_sinh_tu_nhap
                    WHERE id = ?
                      AND ma_lich = ?
                ");

                $s->execute([$id, $maLich]);

                if ($s->rowCount() < 1) {
                    throw new Exception(
                        'Không tìm thấy khoản phát sinh.'
                    );
                }

                $msg = 'Đã xóa khoản phát sinh.';
            }

            else {
                throw new Exception(
                    'Thao tác không hợp lệ.'
                );
            }

            // Tự cập nhật tổng tiền khi sửa HĐ chưa thanh toán
            if ($bill && $act !== 'create') {
                $tongMoi = hdTong($conn, $maLich);

                $s = $conn->prepare("
                    UPDATE hoa_don
                    SET tong_tien = ?
                    WHERE ma_hd = ?
                      AND trang_thai = 'Chưa thanh toán'
                ");

                $s->execute([
                    $tongMoi,
                    $bill['ma_hd']
                ]);
            }
        }

        $conn->commit();

        notice($msg);

        redirect(
            'quan_ly_hoa_don.php?ma_lich=' .
            $maLich . '#chi-tiet'
        );

    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        notice('Lỗi: ' . $e->getMessage());

        redirect(
            'quan_ly_hoa_don.php' .
            ($maLich > 0
                ? '?ma_lich=' . $maLich . '#chi-tiet'
                : '')
        );
    }
}

// ======================================
// LẤY DỮ LIỆU HIỂN THỊ
// ======================================

$dsChoLap = $conn->query("
    SELECT l.ma_lich, k.ho_ten
    FROM dat_lich l
    LEFT JOIN khach_hang k
        ON k.ma_kh = l.ma_kh
    LEFT JOIN hoa_don h
        ON h.ma_lich = l.ma_lich
    WHERE l.trang_thai = 'Hoàn thành'
      AND h.ma_hd IS NULL
    ORDER BY l.ma_lich DESC
")->fetchAll(PDO::FETCH_ASSOC);

$dsHoaDon = $conn->query("
    SELECT
        h.ma_hd,
        h.ma_lich,
        h.tong_tien,
        h.ngay_lap,
        h.trang_thai,
        k.ho_ten,
        l.trang_thai AS lich_trang_thai
    FROM hoa_don h
    JOIN dat_lich l
        ON l.ma_lich = h.ma_lich
    LEFT JOIN khach_hang k
        ON k.ma_kh = l.ma_kh
    ORDER BY h.ma_hd DESC
")->fetchAll(PDO::FETCH_ASSOC);

$dsDichVu = $conn->query("
    SELECT ma_dv, ten_dv, don_gia
    FROM dich_vu
    ORDER BY ten_dv
")->fetchAll(PDO::FETCH_ASSOC);

// ======================================
// LẤY CHI TIẾT HÓA ĐƠN
// ======================================

$maLichXem = (int)($_GET['ma_lich'] ?? 0);

if (isset($_GET['ma_hd'])) {
    $s = $conn->prepare("
        SELECT ma_lich
        FROM hoa_don
        WHERE ma_hd = ?
    ");

    $s->execute([(int)$_GET['ma_hd']]);

    $maLichXem = (int)$s->fetchColumn();
}

$lichXem = $maLichXem > 0
    ? hdLich($conn, $maLichXem)
    : false;

$hoaDonXem = $lichXem
    ? hdBill($conn, $maLichXem)
    : false;

$duocSua = $lichXem
    && $lichXem['trang_thai'] === 'Hoàn thành'
    && (
        !$hoaDonXem ||
        $hoaDonXem['trang_thai'] === 'Chưa thanh toán'
    );

$chiTietDichVu = [];
$chiTietPhatSinh = [];

if ($lichXem) {
    $s = $conn->prepare("
        SELECT
            ct.ma_dv,
            ct.so_luong,
            ct.don_gia,
            ct.loai_dich_vu,
            d.ten_dv
        FROM ct_dat_lich ct
        LEFT JOIN dich_vu d
            ON d.ma_dv = ct.ma_dv
        WHERE ct.ma_lich = ?
        ORDER BY ct.loai_dich_vu, ct.ma_dv
    ");

    $s->execute([$maLichXem]);

    $chiTietDichVu = $s->fetchAll(PDO::FETCH_ASSOC);

    $s = $conn->prepare("
        SELECT *
        FROM phat_sinh_tu_nhap
        WHERE ma_lich = ?
        ORDER BY id DESC
    ");

    $s->execute([$maLichXem]);

    $chiTietPhatSinh = $s->fetchAll(PDO::FETCH_ASSOC);
}

$tongTien = 0;

if ($lichXem) {
    if (
        $hoaDonXem &&
        $hoaDonXem['trang_thai'] === 'Đã thanh toán'
    ) {
        $tongTien = (float)$hoaDonXem['tong_tien'];
    } else {
        $tongTien = hdTong($conn, $maLichXem);
    }
}

// ======================================
// GIAO DIỆN
// ======================================

layout('Quản lý hóa đơn');

// CSS riêng
echo '<link rel="stylesheet" href="../assets/css/quan_ly_hoa_don.css">';
?>

<section class="hd-card">
    <h2>1. Chọn lịch để lập hóa đơn</h2>

    <form method="GET" class="hd-actions">
        <select name="ma_lich" required>
            <option value="">
                -- Chọn lịch đã hoàn thành --
            </option>

            <?php foreach ($dsChoLap as $lich): ?>
                <option
                    value="<?= (int)$lich['ma_lich'] ?>"
                    <?= $maLichXem === (int)$lich['ma_lich']
                        ? 'selected' : '' ?>
                >
                    #<?= (int)$lich['ma_lich'] ?>
                    - <?= h($lich['ho_ten']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="hd-btn" type="submit">
            Xem dịch vụ
        </button>
    </form>
</section>

<?php if (
    $lichXem &&
    (
        $lichXem['trang_thai'] === 'Hoàn thành' ||
        $hoaDonXem
    )
): ?>

<section class="hd-card" id="chi-tiet">

    <div class="hd-heading">
        <h2>
            <?= $hoaDonXem
                ? 'Chi tiết hóa đơn #' . (int)$hoaDonXem['ma_hd']
                : '2. Kiểm tra và lập hóa đơn' ?>
        </h2>

        <a
            href="quan_ly_hoa_don.php"
            class="hd-btn hd-btn-secondary"
        >
            Đóng
        </a>
    </div>

    <div class="hd-grid">
        <div class="hd-info">
            <small>Khách hàng</small>
            <strong><?= h($lichXem['ho_ten']) ?></strong>
            <span><?= h($lichXem['sdt']) ?></span>
        </div>

        <div class="hd-info">
            <small>Xe</small>
            <strong>
                <?= h($lichXem['hang_xe']) ?>
                <?= h($lichXem['model']) ?>
            </strong>
            <span><?= h($lichXem['bien_so']) ?></span>
        </div>

        <div class="hd-info">
            <small>Lịch hẹn</small>
            <strong>
                #<?= (int)$maLichXem ?>
            </strong>
            <span>
                <?= h($lichXem['ngay_hen']) ?>
                <?= h($lichXem['gio_hen']) ?>
            </span>
        </div>
    </div>

    <?php if ($hoaDonXem): ?>
        <p class="hd-status-line">
            Trạng thái:

            <strong class="<?= $hoaDonXem['trang_thai'] === 'Đã thanh toán'
                ? 'hd-paid' : 'hd-unpaid' ?>">
                <?= h($hoaDonXem['trang_thai']) ?>
                <?= $hoaDonXem['trang_thai'] === 'Đã thanh toán'
                    ? ' 🔒' : '' ?>
            </strong>

            <span>
                · Ngày lập:
                <?= h($hoaDonXem['ngay_lap']) ?>
            </span>
        </p>
    <?php endif; ?>

    <!-- DỊCH VỤ KHÁCH CHỌN -->

    <h3>Dịch vụ khách đã chọn và dịch vụ phát sinh</h3>

    <div class="hd-table-wrap">
        <table class="hd-table">
            <thead>
                <tr>
                    <th>Dịch vụ</th>
                    <th>Loại</th>
                    <th>Số lượng</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($chiTietDichVu as $dv): ?>
                    <tr>
                        <td>
                            <?= h(
                                $dv['ten_dv'] ??
                                'Dịch vụ không còn trong danh mục'
                            ) ?>
                        </td>

                        <td>
                            <?= $dv['loai_dich_vu'] === 'phat_sinh'
                                ? 'Phát sinh'
                                : 'Khách đặt trước' ?>
                        </td>

                        <td><?= (int)$dv['so_luong'] ?></td>
                        <td><?= cash($dv['don_gia']) ?></td>

                        <td class="hd-money">
                            <?= cash(
                                $dv['so_luong'] * $dv['don_gia']
                            ) ?>
                        </td>

                        <td>
                            <?php if (
                                $duocSua &&
                                $dv['loai_dich_vu'] === 'phat_sinh'
                            ): ?>

                                <div class="hd-actions">
                                    <form method="POST" class="hd-edit-form">
                                        <input
                                            type="hidden"
                                            name="token"
                                            value="<?= h(token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="act"
                                            value="edit_catalog"
                                        >

                                        <input
                                            type="hidden"
                                            name="ma_lich"
                                            value="<?= (int)$maLichXem ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="ma_dv"
                                            value="<?= (int)$dv['ma_dv'] ?>"
                                        >

                                        <input
                                            type="number"
                                            name="so_luong"
                                            min="1"
                                            value="<?= (int)$dv['so_luong'] ?>"
                                            required
                                        >

                                        <input
                                            type="number"
                                            name="don_gia"
                                            min="0"
                                            step="0.01"
                                            value="<?= h($dv['don_gia']) ?>"
                                            required
                                        >

                                        <button class="hd-btn" type="submit">
                                            Lưu
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Xóa dịch vụ phát sinh này?')"
                                    >
                                        <input
                                            type="hidden"
                                            name="token"
                                            value="<?= h(token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="act"
                                            value="delete_catalog"
                                        >

                                        <input
                                            type="hidden"
                                            name="ma_lich"
                                            value="<?= (int)$maLichXem ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="ma_dv"
                                            value="<?= (int)$dv['ma_dv'] ?>"
                                        >

                                        <button
                                            class="hd-btn hd-btn-danger"
                                            type="submit"
                                        >
                                            Xóa
                                        </button>
                                    </form>
                                </div>

                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$chiTietDichVu): ?>
                    <tr>
                        <td colspan="6">
                            Chưa có dịch vụ nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- THÊM DỊCH VỤ TỪ DANH MỤC -->

    <?php if ($duocSua): ?>
        <h3>+ Thêm dịch vụ có sẵn trong danh mục</h3>

        <form method="POST" class="hd-grid hd-form-grid">
            <input
                type="hidden"
                name="token"
                value="<?= h(token()) ?>"
            >

            <input
                type="hidden"
                name="act"
                value="add_catalog"
            >

            <input
                type="hidden"
                name="ma_lich"
                value="<?= (int)$maLichXem ?>"
            >

            <div>
                <label>Dịch vụ</label>

                <select name="ma_dv" required>
                    <option value="">
                        -- Chọn dịch vụ --
                    </option>

                    <?php foreach ($dsDichVu as $dv): ?>
                        <option value="<?= (int)$dv['ma_dv'] ?>">
                            <?= h($dv['ten_dv']) ?>
                            - <?= cash($dv['don_gia']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Số lượng</label>

                <input
                    type="number"
                    name="so_luong"
                    min="1"
                    value="1"
                    required
                >
            </div>

            <div class="hd-form-bottom">
                <button class="hd-btn" type="submit">
                    + Thêm dịch vụ
                </button>
            </div>
        </form>
    <?php endif; ?>

    <!-- PHÁT SINH TỰ NHẬP -->

    <h3>Chi phí phát sinh tự nhập</h3>

    <div class="hd-table-wrap">
        <table class="hd-table">
            <thead>
                <tr>
                    <th>Nội dung</th>
                    <th>Loại</th>
                    <th>Số lượng</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($chiTietPhatSinh as $ps): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h($ps['ten_khoan']) ?>
                            </strong>

                            <?php if (!empty($ps['ghi_chu'])): ?>
                                <div class="hd-muted">
                                    <?= h($ps['ghi_chu']) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= $ps['loai'] === 'chenh_lech'
                                ? 'Chênh lệch nâng cấp'
                                : 'Ngoài danh mục' ?>
                        </td>

                        <td><?= (int)$ps['so_luong'] ?></td>
                        <td><?= cash($ps['don_gia']) ?></td>

                        <td class="hd-money">
                            <?= cash(
                                $ps['so_luong'] * $ps['don_gia']
                            ) ?>
                        </td>

                        <td>
                            <?php if ($duocSua): ?>
                                <div class="hd-actions">
                                    <form method="POST" class="hd-edit-form">
                                        <input
                                            type="hidden"
                                            name="token"
                                            value="<?= h(token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="act"
                                            value="edit_manual"
                                        >

                                        <input
                                            type="hidden"
                                            name="ma_lich"
                                            value="<?= (int)$maLichXem ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$ps['id'] ?>"
                                        >

                                        <input
                                            class="hd-input-name"
                                            name="ten"
                                            maxlength="255"
                                            value="<?= h($ps['ten_khoan']) ?>"
                                            required
                                        >

                                        <input
                                            type="number"
                                            name="so_luong"
                                            min="1"
                                            value="<?= (int)$ps['so_luong'] ?>"
                                            required
                                        >

                                        <input
                                            type="number"
                                            name="don_gia"
                                            min="0"
                                            step="0.01"
                                            value="<?= h($ps['don_gia']) ?>"
                                            required
                                        >

                                        <input
                                            class="hd-input-note"
                                            name="ghi_chu"
                                            value="<?= h($ps['ghi_chu']) ?>"
                                        >

                                        <button class="hd-btn" type="submit">
                                            Lưu
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Xóa khoản phát sinh này?')"
                                    >
                                        <input
                                            type="hidden"
                                            name="token"
                                            value="<?= h(token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="act"
                                            value="delete_manual"
                                        >

                                        <input
                                            type="hidden"
                                            name="ma_lich"
                                            value="<?= (int)$maLichXem ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$ps['id'] ?>"
                                        >

                                        <button
                                            class="hd-btn hd-btn-danger"
                                            type="submit"
                                        >
                                            Xóa
                                        </button>
                                    </form>
                                </div>

                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$chiTietPhatSinh): ?>
                    <tr>
                        <td colspan="6">
                            Chưa có khoản phát sinh tự nhập.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- THÊM PHÁT SINH TỰ NHẬP -->

    <?php if ($duocSua): ?>
        <h3>+ Thêm khoản phát sinh tự nhập</h3>

        <form method="POST" class="hd-grid hd-form-grid">
            <input
                type="hidden"
                name="token"
                value="<?= h(token()) ?>"
            >

            <input
                type="hidden"
                name="act"
                value="add_manual"
            >

            <input
                type="hidden"
                name="ma_lich"
                value="<?= (int)$maLichXem ?>"
            >

            <div>
                <label>Tên khoản phát sinh</label>

                <input
                    name="ten"
                    maxlength="255"
                    placeholder="VD: Nâng cấp dầu cao cấp"
                    required
                >
            </div>

            <div>
                <label>Loại phát sinh</label>

                <select name="loai">
                    <option value="chenh_lech">
                        Chênh lệch nâng cấp
                    </option>

                    <option value="ngoai_danh_muc">
                        Dịch vụ ngoài danh mục
                    </option>
                </select>
            </div>

            <div>
                <label>Đơn giá (VNĐ)</label>

                <input
                    type="number"
                    name="don_gia"
                    min="0"
                    step="0.01"
                    placeholder="500000"
                    required
                >
            </div>

            <div>
                <label>Số lượng</label>

                <input
                    type="number"
                    name="so_luong"
                    min="1"
                    value="1"
                    required
                >
            </div>

            <div>
                <label>Ghi chú</label>

                <input
                    name="ghi_chu"
                    placeholder="VD: Khách đã đồng ý"
                >
            </div>

            <div class="hd-form-bottom">
                <button class="hd-btn" type="submit">
                    + Thêm phát sinh
                </button>
            </div>
        </form>
    <?php endif; ?>

    <!-- TỔNG TIỀN -->

    <div class="hd-total-box">
        <span>
            Tổng tiền
            <?= $hoaDonXem ? 'hóa đơn' : 'dự kiến' ?>
        </span>

        <strong>
            <?= cash($tongTien) ?>
        </strong>
    </div>

    <!-- LẬP HÓA ĐƠN -->

    <?php if (!$hoaDonXem && $duocSua): ?>
        <form
            method="POST"
            onsubmit="return confirm('Xác nhận lập hóa đơn với tổng tiền hiện tại?')"
        >
            <input
                type="hidden"
                name="token"
                value="<?= h(token()) ?>"
            >

            <input
                type="hidden"
                name="act"
                value="create"
            >

            <input
                type="hidden"
                name="ma_lich"
                value="<?= (int)$maLichXem ?>"
            >

            <button class="hd-btn" type="submit">
                ✓ Xác nhận lập hóa đơn
            </button>
        </form>

    <?php elseif (
        $hoaDonXem &&
        $hoaDonXem['trang_thai'] === 'Chưa thanh toán'
    ): ?>

        <p class="hd-unpaid">
            Hóa đơn chưa thanh toán: có thể thêm,
            sửa hoặc xóa các khoản phát sinh phía trên.
        </p>

        <form
            method="POST"
            onsubmit="return confirm('Xác nhận khách đã thanh toán? Sau khi xác nhận sẽ khóa hóa đơn.')"
        >
            <input
                type="hidden"
                name="token"
                value="<?= h(token()) ?>"
            >

            <input
                type="hidden"
                name="act"
                value="pay"
            >

            <input
                type="hidden"
                name="ma_lich"
                value="<?= (int)$maLichXem ?>"
            >

            <button class="hd-btn" type="submit">
                ✓ Xác nhận thanh toán
            </button>
        </form>

    <?php elseif ($hoaDonXem): ?>
        <p class="hd-paid">
            ✓ Hóa đơn đã thanh toán và được khóa.
            Chỉ được xem chi tiết.
        </p>
    <?php endif; ?>

</section>

<?php endif; ?>

<!-- DANH SÁCH HÓA ĐƠN -->

<section class="hd-card">
    <h2>3. Danh sách hóa đơn</h2>

    <div class="hd-table-wrap">
        <table class="hd-table">
            <thead>
                <tr>
                    <th>Mã HĐ</th>
                    <th>Lịch</th>
                    <th>Khách hàng</th>
                    <th>Tổng tiền</th>
                    <th>Ngày lập</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($dsHoaDon as $hd): ?>
                    <tr>
                        <td>
                            #<?= (int)$hd['ma_hd'] ?>
                        </td>

                        <td>
                            #<?= (int)$hd['ma_lich'] ?>
                        </td>

                        <td>
                            <?= h($hd['ho_ten']) ?>
                        </td>

                        <td class="hd-money">
                            <?= cash($hd['tong_tien']) ?>
                        </td>

                        <td>
                            <?= h($hd['ngay_lap']) ?>
                        </td>

                        <td>
                            <span class="<?= $hd['trang_thai'] === 'Đã thanh toán'
                                ? 'hd-paid'
                                : 'hd-unpaid' ?>">
                                <?= h($hd['trang_thai']) ?>

                                <?= $hd['trang_thai'] === 'Đã thanh toán'
                                    ? ' 🔒'
                                    : '' ?>
                            </span>
                        </td>

                        <td>
                            <div class="hd-actions">
                                <a
                                    class="hd-btn hd-btn-secondary"
                                    href="quan_ly_hoa_don.php?ma_hd=<?= (int)$hd['ma_hd'] ?>#chi-tiet"
                                >
                                    <?= $hd['trang_thai'] === 'Chưa thanh toán'
                                        ? 'Xem / Chỉnh sửa'
                                        : 'Xem chi tiết' ?>
                                </a>

                                <?php if (
                                    $hd['trang_thai'] === 'Chưa thanh toán' &&
                                    $hd['lich_trang_thai'] === 'Hoàn thành'
                                ): ?>
                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Xác nhận thanh toán? Hóa đơn sẽ được khóa.')"
                                    >
                                        <input
                                            type="hidden"
                                            name="token"
                                            value="<?= h(token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="act"
                                            value="pay"
                                        >

                                        <input
                                            type="hidden"
                                            name="ma_lich"
                                            value="<?= (int)$hd['ma_lich'] ?>"
                                        >

                                        <button class="hd-btn" type="submit">
                                            Thanh toán
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$dsHoaDon): ?>
                    <tr>
                        <td colspan="7">
                            Chưa có hóa đơn nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php done(); ?>
