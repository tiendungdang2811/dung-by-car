
<?php
require_once __DIR__ . '/_core.php';
guard();

$maLich = filter_input(INPUT_GET, 'ma_lich', FILTER_VALIDATE_INT);
if (!$maLich || $maLich < 1) {
    exit('Mã lịch không hợp lệ.');
}

function layLich($conn, $id) {
    $s = $conn->prepare("
        SELECT l.ma_lich, l.ngay_hen, l.gio_hen, l.trang_thai,
               k.ho_ten, k.sdt, x.bien_so, x.hang_xe, x.model
        FROM dat_lich l
        LEFT JOIN khach_hang k ON k.ma_kh = l.ma_kh
        LEFT JOIN xe x ON x.ma_xe = l.ma_xe
        WHERE l.ma_lich = ?
    ");
    $s->execute([$id]);
    return $s->fetch(PDO::FETCH_ASSOC);
}

function layHoaDon($conn, $id) {
    $s = $conn->prepare("
        SELECT ma_hd, trang_thai, tong_tien
        FROM hoa_don
        WHERE ma_lich = ?
        LIMIT 1
    ");
    $s->execute([$id]);
    return $s->fetch(PDO::FETCH_ASSOC);
}

function tinhTong($conn, $id) {
    $s = $conn->prepare("
        SELECT COALESCE(SUM(so_luong * don_gia), 0)
        FROM ct_dat_lich
        WHERE ma_lich = ?
    ");
    $s->execute([$id]);
    $coBan = (float)$s->fetchColumn();

    $s = $conn->prepare("
        SELECT COALESCE(SUM(so_luong * don_gia), 0)
        FROM phat_sinh_tu_nhap
        WHERE ma_lich = ?
    ");
    $s->execute([$id]);

    return $coBan + (float)$s->fetchColumn();
}

function giaHopLe($value) {
    return is_scalar($value)
        && preg_match('/^\d+(?:\.\d{1,2})?$/D', (string)$value)
        && (float)$value <= 999999999999;
}

$lich = layLich($conn, $maLich);

if (!$lich) {
    exit('Không tìm thấy lịch hẹn.');
}

$loi = '';

// =====================================
// XỬ LÝ THÊM, SỬA, XÓA
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify();

    try {
        $conn->beginTransaction();

        $s = $conn->prepare("
            SELECT trang_thai
            FROM dat_lich
            WHERE ma_lich = ?
            FOR UPDATE
        ");
        $s->execute([$maLich]);
        $trangThaiLich = $s->fetchColumn();

        if ($trangThaiLich === false ||
            $trangThaiLich === 'Đã hủy') {
            throw new Exception(
                'Lịch không tồn tại hoặc đã hủy.'
            );
        }

        $s = $conn->prepare("
            SELECT ma_hd, trang_thai
            FROM hoa_don
            WHERE ma_lich = ?
            FOR UPDATE
        ");
        $s->execute([$maLich]);
        $hoaDon = $s->fetch(PDO::FETCH_ASSOC);

        if ($hoaDon &&
            $hoaDon['trang_thai'] === 'Đã thanh toán') {
            throw new Exception(
                'Hóa đơn đã thanh toán, không thể sửa dịch vụ.'
            );
        }

        $act = $_POST['hanh_dong'] ?? '';

        // =====================================
        // THÊM DỊCH VỤ TỪ DANH MỤC
        // =====================================

        if ($act === 'them_danh_muc') {
            $maDv = filter_var(
                $_POST['ma_dv'] ?? null,
                FILTER_VALIDATE_INT
            );

            $soLuong = filter_var(
                $_POST['so_luong'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$maDv || !$soLuong || $soLuong < 1) {
                throw new Exception(
                    'Dịch vụ hoặc số lượng không hợp lệ.'
                );
            }

            $s = $conn->prepare("
                SELECT don_gia
                FROM dich_vu
                WHERE ma_dv = ?
            ");
            $s->execute([$maDv]);
            $gia = $s->fetchColumn();

            if ($gia === false) {
                throw new Exception('Dịch vụ không tồn tại.');
            }

            $s = $conn->prepare("
                SELECT COUNT(*)
                FROM ct_dat_lich
                WHERE ma_lich = ? AND ma_dv = ?
            ");
            $s->execute([$maLich, $maDv]);

            if ($s->fetchColumn()) {
                throw new Exception(
                    'Dịch vụ đã có trong lịch. ' .
                    'Nếu nâng cấp, hãy dùng khoản chênh lệch.'
                );
            }

            $s = $conn->prepare("
                INSERT INTO ct_dat_lich
                (ma_lich, ma_dv, so_luong, don_gia, loai_dich_vu)
                VALUES (?, ?, ?, ?, 'phat_sinh')
            ");

            $s->execute([
                $maLich,
                $maDv,
                $soLuong,
                $gia
            ]);

            $msg = 'Đã thêm dịch vụ từ danh mục.';
        }

        // =====================================
        // THÊM PHÁT SINH NHẬP THỦ CÔNG
        // =====================================

        elseif ($act === 'them_tu_nhap') {
            $ten = trim((string)($_POST['ten'] ?? ''));
            $ghiChu = trim((string)($_POST['ghi_chu'] ?? ''));
            $soLuong = filter_var(
                $_POST['so_luong'] ?? null,
                FILTER_VALIDATE_INT
            );
            $gia = $_POST['don_gia'] ?? '';
            $loai = $_POST['loai'] ?? '';

            if (
                $ten === '' ||
                mb_strlen($ten, 'UTF-8') > 255 ||
                !$soLuong ||
                $soLuong < 1 ||
                !giaHopLe($gia)
            ) {
                throw new Exception(
                    'Tên, số lượng hoặc đơn giá không hợp lệ.'
                );
            }

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
                (ma_lich, ten_khoan, loai, so_luong, don_gia, ghi_chu)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $s->execute([
                $maLich,
                $ten,
                $loai,
                $soLuong,
                $gia,
                $ghiChu
            ]);

            $msg = 'Đã thêm khoản phát sinh thủ công.';
        }

        // =====================================
        // SỬA DỊCH VỤ PHÁT SINH TỪ DANH MỤC
        // =====================================

        elseif ($act === 'sua_danh_muc') {
            $maDv = filter_var(
                $_POST['ma_dv'] ?? null,
                FILTER_VALIDATE_INT
            );
            $soLuong = filter_var(
                $_POST['so_luong'] ?? null,
                FILTER_VALIDATE_INT
            );
            $gia = $_POST['don_gia'] ?? '';

            if (
                !$maDv ||
                !$soLuong ||
                $soLuong < 1 ||
                !giaHopLe($gia)
            ) {
                throw new Exception(
                    'Dữ liệu sửa không hợp lệ.'
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

            if (!$s->fetchColumn()) {
                throw new Exception(
                    'Không tìm thấy dịch vụ phát sinh.'
                );
            }

            $s = $conn->prepare("
                UPDATE ct_dat_lich
                SET so_luong = ?, don_gia = ?
                WHERE ma_lich = ?
                  AND ma_dv = ?
                  AND loai_dich_vu = 'phat_sinh'
            ");

            $s->execute([
                $soLuong,
                $gia,
                $maLich,
                $maDv
            ]);

            $msg = 'Đã sửa dịch vụ phát sinh.';
        }

        // =====================================
        // XÓA DỊCH VỤ PHÁT SINH TỪ DANH MỤC
        // =====================================

        elseif ($act === 'xoa_danh_muc') {
            $maDv = filter_var(
                $_POST['ma_dv'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$maDv) {
                throw new Exception('Mã dịch vụ không hợp lệ.');
            }

            $s = $conn->prepare("
                DELETE FROM ct_dat_lich
                WHERE ma_lich = ?
                  AND ma_dv = ?
                  AND loai_dich_vu = 'phat_sinh'
            ");

            $s->execute([$maLich, $maDv]);

            if (!$s->rowCount()) {
                throw new Exception(
                    'Không tìm thấy dịch vụ phát sinh.'
                );
            }

            $msg = 'Đã xóa dịch vụ phát sinh.';
        }

        // =====================================
        // SỬA PHÁT SINH NHẬP THỦ CÔNG
        // =====================================

        elseif ($act === 'sua_tu_nhap') {
            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $ten = trim((string)($_POST['ten'] ?? ''));
            $ghiChu = trim((string)($_POST['ghi_chu'] ?? ''));

            $soLuong = filter_var(
                $_POST['so_luong'] ?? null,
                FILTER_VALIDATE_INT
            );

            $gia = $_POST['don_gia'] ?? '';

            if (
                !$id ||
                $ten === '' ||
                mb_strlen($ten, 'UTF-8') > 255 ||
                !$soLuong ||
                $soLuong < 1 ||
                !giaHopLe($gia)
            ) {
                throw new Exception(
                    'Dữ liệu sửa không hợp lệ.'
                );
            }

            $s = $conn->prepare("
                SELECT COUNT(*)
                FROM phat_sinh_tu_nhap
                WHERE id = ? AND ma_lich = ?
            ");
            $s->execute([$id, $maLich]);

            if (!$s->fetchColumn()) {
                throw new Exception(
                    'Không tìm thấy khoản phát sinh.'
                );
            }

            $s = $conn->prepare("
                UPDATE phat_sinh_tu_nhap
                SET ten_khoan = ?,
                    so_luong = ?,
                    don_gia = ?,
                    ghi_chu = ?
                WHERE id = ? AND ma_lich = ?
            ");

            $s->execute([
                $ten,
                $soLuong,
                $gia,
                $ghiChu,
                $id,
                $maLich
            ]);

            $msg = 'Đã sửa khoản phát sinh.';
        }

        // =====================================
        // XÓA PHÁT SINH NHẬP THỦ CÔNG
        // =====================================

        elseif ($act === 'xoa_tu_nhap') {
            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id) {
                throw new Exception(
                    'Mã khoản phát sinh không hợp lệ.'
                );
            }

            $s = $conn->prepare("
                DELETE FROM phat_sinh_tu_nhap
                WHERE id = ? AND ma_lich = ?
            ");

            $s->execute([$id, $maLich]);

            if (!$s->rowCount()) {
                throw new Exception(
                    'Không tìm thấy khoản phát sinh.'
                );
            }

            $msg = 'Đã xóa khoản phát sinh.';
        }

        else {
            throw new Exception('Thao tác không hợp lệ.');
        }

        // =====================================
        // CẬP NHẬT HÓA ĐƠN CHƯA THANH TOÁN
        // =====================================

        if ($hoaDon) {
            $s = $conn->prepare("
                UPDATE hoa_don
                SET tong_tien = ?
                WHERE ma_hd = ?
                  AND trang_thai = 'Chưa thanh toán'
            ");

            $s->execute([
                tinhTong($conn, $maLich),
                $hoaDon['ma_hd']
            ]);
        }

        $conn->commit();

        notice($msg);

        redirect(
            'dich_vu_phat_sinh.php?ma_lich=' . $maLich
        );

    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $loi = $e->getMessage();
    }
}

// =====================================
// LẤY DỮ LIỆU HIỂN THỊ
// =====================================

$lich = layLich($conn, $maLich);
$hoaDon = layHoaDon($conn, $maLich);

$dsDichVu = $conn->query("
    SELECT ma_dv, ten_dv, don_gia
    FROM dich_vu
    ORDER BY ten_dv
")->fetchAll(PDO::FETCH_ASSOC);

$s = $conn->prepare("
    SELECT ct.ma_dv, ct.so_luong, ct.don_gia,
           ct.loai_dich_vu, d.ten_dv
    FROM ct_dat_lich ct
    JOIN dich_vu d ON d.ma_dv = ct.ma_dv
    WHERE ct.ma_lich = ?
    ORDER BY ct.loai_dich_vu, ct.ma_dv
");

$s->execute([$maLich]);
$chiTiet = $s->fetchAll(PDO::FETCH_ASSOC);

$s = $conn->prepare("
    SELECT *
    FROM phat_sinh_tu_nhap
    WHERE ma_lich = ?
    ORDER BY id DESC
");

$s->execute([$maLich]);
$tuNhap = $s->fetchAll(PDO::FETCH_ASSOC);

$tongTien = tinhTong($conn, $maLich);

$khoaSua =
    $lich['trang_thai'] === 'Đã hủy' ||
    ($hoaDon &&
     $hoaDon['trang_thai'] === 'Đã thanh toán');

layout('Dịch vụ phát sinh - Lịch #' . $maLich);
?>

<style>
.ps-card {
    background: #102237;
    border: 1px solid #29415a;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    color: #f8fafc;
}

.ps-card h2 {
    margin: 0 0 16px;
    color: #fff;
}

.ps-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 12px;
    align-items: end;
}

.ps-card label {
    display: block;
    margin-bottom: 6px;
    color: #dce6f3;
}

.ps-card input,
.ps-card select,
.ps-card textarea {
    box-sizing: border-box;
    width: 100%;
    padding: 10px;
    background: #071321;
    color: white;
    border: 1px solid #40536a;
    border-radius: 6px;
}

.ps-card textarea {
    min-height: 65px;
}

.ps-btn {
    background: #ff782b;
    color: #111;
    border: 0;
    border-radius: 7px;
    padding: 10px 13px;
    font-weight: 700;
    cursor: pointer;
}

.ps-danger {
    background: #d83d4d;
    color: #fff;
}

.ps-table-wrap {
    overflow-x: auto;
}

.ps-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 820px;
}

.ps-table th,
.ps-table td {
    padding: 11px;
    border-bottom: 1px solid #34445a;
    text-align: left;
    vertical-align: middle;
}

.ps-table th {
    color: #ff995b;
}

.ps-table form {
    display: flex;
    gap: 7px;
    align-items: center;
}

.ps-table input {
    width: 110px;
}

.ps-table .ps-name {
    width: 180px;
}

.ps-table .ps-note {
    width: 200px;
}

.ps-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.ps-total {
    font-size: 26px;
    color: #ff782b;
    font-weight: 800;
}

.ps-hint {
    color: #b9c8d8;
    font-size: 13px;
    line-height: 1.6;
}

.ps-back {
    display: inline-block;
    margin-bottom: 16px;
    color: #ff995b;
    text-decoration: none;
}

@media (max-width: 760px) {
    .ps-grid {
        grid-template-columns: 1fr;
    }

    .ps-card {
        padding: 14px;
    }
}
</style>

<a class="ps-back" href="quan_ly_lich.php">
    ← Quay lại quản lý lịch hẹn
</a>

<?php if ($loi !== ''): ?>
    <p class="notice" style="color:#ff9999">
        <?= h($loi) ?>
    </p>
<?php endif; ?>

<section class="ps-card">
    <h2>Thông tin lịch hẹn #<?= (int)$maLich ?></h2>

    <p>
        <strong>Khách hàng:</strong>
        <?= h($lich['ho_ten']) ?>
        · <?= h($lich['sdt']) ?>
    </p>

    <p>
        <strong>Xe:</strong>
        <?= h($lich['hang_xe']) ?>
        <?= h($lich['model']) ?>
        - <?= h($lich['bien_so']) ?>
    </p>

    <p>
        <strong>Ngày hẹn:</strong>
        <?= h($lich['ngay_hen']) ?>
        <?= h($lich['gio_hen']) ?>
    </p>

    <p>
        <strong>Trạng thái:</strong>
        <?= h($lich['trang_thai']) ?>
    </p>

    <p>
        <strong>Hóa đơn:</strong>
        <?= $hoaDon
            ? h($hoaDon['trang_thai'])
            : 'Chưa lập hóa đơn' ?>
    </p>
</section>

<?php if ($khoaSua): ?>

    <p class="notice">
        Lịch đã hủy hoặc hóa đơn đã thanh toán.
        Không thể thêm, sửa, xóa dịch vụ.
    </p>

<?php else: ?>

    <!-- THÊM DỊCH VỤ TỪ DANH MỤC -->

    <section class="ps-card">
        <h2>1. Thêm dịch vụ có trong danh mục</h2>

        <form method="post">
            <input
                type="hidden"
                name="token"
                value="<?= h(token()) ?>"
            >

            <input
                type="hidden"
                name="hanh_dong"
                value="them_danh_muc"
            >

            <div class="ps-grid">
                <div>
                    <label>Chọn dịch vụ</label>

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

                <div>
                    <button class="ps-btn" type="submit">
                        + Thêm dịch vụ
                    </button>
                </div>
            </div>
        </form>
    </section>

    <!-- THÊM PHÁT SINH THỦ CÔNG -->

    <section class="ps-card">
        <h2>
            2. Thêm phát sinh ngoài danh mục /
            chênh lệch nâng cấp
        </h2>

        <p class="ps-hint">
            Ví dụ: Dầu thường 500.000đ → dầu cao cấp
            1.000.000đ: chọn
            <strong>Chênh lệch nâng cấp</strong>
            và nhập 500000.
            Nếu thay cảm biến 850.000đ chưa có trong
            danh mục: chọn
            <strong>Dịch vụ ngoài danh mục</strong>
            và nhập 850000.
            Chỉ ghi nhận sau khi khách đồng ý.
        </p>

        <form method="post">
            <input
                type="hidden"
                name="token"
                value="<?= h(token()) ?>"
            >

            <input
                type="hidden"
                name="hanh_dong"
                value="them_tu_nhap"
            >

            <div class="ps-grid">
                <div>
                    <label>Tên khoản phát sinh</label>
                    <input
                        name="ten"
                        maxlength="255"
                        placeholder="VD: Nâng cấp dầu động cơ cao cấp"
                        required
                    >
                </div>

                <div>
                    <label>Loại phát sinh</label>
                    <select name="loai" required>
                        <option value="chenh_lech">
                            Chênh lệch nâng cấp
                        </option>

                        <option value="ngoai_danh_muc">
                            Dịch vụ ngoài danh mục
                        </option>
                    </select>
                </div>

                <div>
                    <label>Đơn giá phát sinh (VNĐ)</label>
                    <input
                        type="number"
                        name="don_gia"
                        min="0"
                        step="1"
                        required
                        placeholder="500000"
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

                <div style="grid-column:span 2">
                    <label>Ghi chú / xác nhận của khách</label>
                    <input
                        name="ghi_chu"
                        placeholder="VD: Khách đồng ý qua điện thoại lúc 10:30"
                    >
                </div>

                <div>
                    <button class="ps-btn" type="submit">
                        + Thêm phát sinh
                    </button>
                </div>
            </div>
        </form>
    </section>

<?php endif; ?>

<!-- DANH SÁCH DỊCH VỤ TRONG DANH MỤC -->

<section class="ps-card">
    <h2>Chi tiết dịch vụ trong danh mục</h2>

    <div class="ps-table-wrap">
        <table class="ps-table">
            <thead>
                <tr>
                    <th>Dịch vụ</th>
                    <th>Loại</th>
                    <th>SL</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($chiTiet as $ct): ?>
                    <tr>
                        <td><?= h($ct['ten_dv']) ?></td>

                        <td>
                            <?= $ct['loai_dich_vu'] === 'phat_sinh'
                                ? 'Phát sinh'
                                : 'Đặt trước' ?>
                        </td>

                        <td><?= (int)$ct['so_luong'] ?></td>
                        <td><?= cash($ct['don_gia']) ?></td>

                        <td>
                            <?= cash(
                                $ct['so_luong'] * $ct['don_gia']
                            ) ?>
                        </td>

                        <td>
                            <?php if (
                                !$khoaSua &&
                                $ct['loai_dich_vu'] === 'phat_sinh'
                            ): ?>

                                <div class="ps-actions">
                                    <form method="post">
                                        <input
                                            type="hidden"
                                            name="token"
                                            value="<?= h(token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="hanh_dong"
                                            value="sua_danh_muc"
                                        >

                                        <input
                                            type="hidden"
                                            name="ma_dv"
                                            value="<?= (int)$ct['ma_dv'] ?>"
                                        >

                                        <input
                                            type="number"
                                            name="so_luong"
                                            min="1"
                                            value="<?= (int)$ct['so_luong'] ?>"
                                            required
                                        >

                                        <input
                                            type="number"
                                            name="don_gia"
                                            min="0"
                                            step="1"
                                            value="<?= h($ct['don_gia']) ?>"
                                            required
                                        >

                                        <button
                                            class="ps-btn"
                                            type="submit"
                                        >
                                            Lưu
                                        </button>
                                    </form>

                                    <form
                                        method="post"
                                        onsubmit="return confirm('Xóa dịch vụ phát sinh này?')"
                                    >
                                        <input
                                            type="hidden"
                                            name="token"
                                            value="<?= h(token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="hanh_dong"
                                            value="xoa_danh_muc"
                                        >

                                        <input
                                            type="hidden"
                                            name="ma_dv"
                                            value="<?= (int)$ct['ma_dv'] ?>"
                                        >

                                        <button
                                            class="ps-btn ps-danger"
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

                <?php if (!$chiTiet): ?>
                    <tr>
                        <td colspan="6">
                            Chưa có dịch vụ nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- DANH SÁCH PHÁT SINH THỦ CÔNG -->

<section class="ps-card">
    <h2>Khoản phát sinh nhập thủ công</h2>

    <div class="ps-table-wrap">
        <table class="ps-table">
            <thead>
                <tr>
                    <th>Tên / Ghi chú</th>
                    <th>Loại</th>
                    <th>SL</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($tuNhap as $p): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h($p['ten_khoan']) ?>
                            </strong>

                            <?php if ($p['ghi_chu'] !== ''): ?>
                                <div class="ps-hint">
                                    <?= h($p['ghi_chu']) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= $p['loai'] === 'chenh_lech'
                                ? 'Chênh lệch nâng cấp'
                                : 'Ngoài danh mục' ?>
                        </td>

                        <td><?= (int)$p['so_luong'] ?></td>
                        <td><?= cash($p['don_gia']) ?></td>

                        <td>
                            <?= cash(
                                $p['so_luong'] * $p['don_gia']
                            ) ?>
                        </td>

                        <td>
                            <?php if (!$khoaSua): ?>
                                <div class="ps-actions">
                                    <form method="post">
                                        <input
                                            type="hidden"
                                            name="token"
                                            value="<?= h(token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="hanh_dong"
                                            value="sua_tu_nhap"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$p['id'] ?>"
                                        >

                                        <input
                                            class="ps-name"
                                            name="ten"
                                            maxlength="255"
                                            value="<?= h($p['ten_khoan']) ?>"
                                            required
                                        >

                                        <input
                                            type="number"
                                            name="so_luong"
                                            min="1"
                                            value="<?= (int)$p['so_luong'] ?>"
                                            required
                                        >

                                        <input
                                            type="number"
                                            name="don_gia"
                                            min="0"
                                            step="1"
                                            value="<?= h($p['don_gia']) ?>"
                                            required
                                        >

                                        <input
                                            class="ps-note"
                                            name="ghi_chu"
                                            value="<?= h($p['ghi_chu']) ?>"
                                            placeholder="Ghi chú"
                                        >

                                        <button
                                            class="ps-btn"
                                            type="submit"
                                        >
                                            Lưu
                                        </button>
                                    </form>

                                    <form
                                        method="post"
                                        onsubmit="return confirm('Xóa khoản phát sinh này?')"
                                    >
                                        <input
                                            type="hidden"
                                            name="token"
                                            value="<?= h(token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="hanh_dong"
                                            value="xoa_tu_nhap"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$p['id'] ?>"
                                        >

                                        <button
                                            class="ps-btn ps-danger"
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

                <?php if (!$tuNhap): ?>
                    <tr>
                        <td colspan="6">
                            Chưa có khoản phát sinh thủ công.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- TỔNG TIỀN -->

<section class="ps-card">
    <h2>Tổng tiền hiện tại</h2>

    <div class="ps-total">
        <?= cash($tongTien) ?>
    </div>

    <p class="ps-hint">
        Tổng = dịch vụ đặt trước
        + dịch vụ phát sinh trong danh mục
        + khoản phát sinh nhập thủ công.
    </p>
</section>

<?php done(); ?>
