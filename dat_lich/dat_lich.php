
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Khach chua dang nhap phai dang nhap truoc
if (!isset($_SESSION['ma_kh'])) {
    header('Location: ../auth/login.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$maKh = (int)$_SESSION['ma_kh'];
$hoTen = $_SESSION['ho_ten'] ?? 'Khách hàng';

$loi = '';
$thanhCong = '';
$maLichMoi = null;

// Lay danh sach dich vu tu MySQL
$stmt = $conn->query(
    "SELECT ma_dv, ten_dv, mo_ta, don_gia
     FROM dich_vu ORDER BY ma_dv ASC"
);
$danhSachDichVu = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tao danh sach ma dich vu hop le
$dichVuHopLe = [];
foreach ($danhSachDichVu as $dv) {
    $dichVuHopLe[(int)$dv['ma_dv']] = $dv;
}

// Xu ly khi khach bam Xac nhan dat lich
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $bienSo = strtoupper(trim($_POST['bien_so'] ?? ''));
    $hangXe = trim($_POST['hang_xe'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $namSx = trim($_POST['nam_sx'] ?? '');
    $ngayHen = trim($_POST['ngay_hen'] ?? '');
    $gioHen = trim($_POST['gio_hen'] ?? '');
    $maDichVu = $_POST['dich_vu'] ?? [];

    if (!is_array($maDichVu)) {
        $maDichVu = [];
    }

    // Loai bo ma dich vu trung nhau
    $maDichVu = array_unique(array_map('intval', $maDichVu));

    // Kiem tra thong tin xe
    if (
        $bienSo === '' ||
        $hangXe === '' ||
        $model === '' ||
        $namSx === '' ||
        $ngayHen === '' ||
        $gioHen === ''
    ) {
        $loi = 'Vui lòng nhập đầy đủ thông tin.';
    }
    elseif (strlen($bienSo) > 20) {
        $loi = 'Biển số xe quá dài.';
    }
    elseif (!ctype_digit($namSx) ||
            (int)$namSx < 1980 ||
            (int)$namSx > (int)date('Y') + 1) {
        $loi = 'Năm sản xuất không hợp lệ.';
    }
    elseif (empty($maDichVu)) {
        $loi = 'Vui lòng chọn ít nhất một dịch vụ.';
    }
    else {
        // Kiem tra cac dich vu duoc chon
        foreach ($maDichVu as $maDv) {
            if (!isset($dichVuHopLe[$maDv])) {
                $loi = 'Danh sách dịch vụ không hợp lệ.';
                break;
            }
        }
    }

    // Kiem tra ngay gio hen
    if ($loi === '') {
        $thoiGianHen = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $ngayHen . ' ' . $gioHen
        );

        if (
            !$thoiGianHen ||
            $thoiGianHen->format('Y-m-d H:i') !==
                $ngayHen . ' ' . $gioHen
        ) {
            $loi = 'Ngày hoặc giờ hẹn không hợp lệ.';
        }
        elseif ($thoiGianHen <= new DateTimeImmutable()) {
            $loi = 'Vui lòng chọn thời gian hẹn trong tương lai.';
        }
        elseif (
            $gioHen < '08:00' ||
            $gioHen > '17:00'
        ) {
            $loi = 'Vui lòng chọn giờ từ 08:00 đến 17:00.';
        }
    }

    // Luu du lieu vao MySQL
    if ($loi === '') {
        try {
            $conn->beginTransaction();

            // Kiem tra xe da ton tai cua khach hay chua
            $stmt = $conn->prepare(
                "SELECT ma_xe FROM xe
                 WHERE ma_kh = ? AND bien_so = ?
                 LIMIT 1"
            );
            $stmt->execute([$maKh, $bienSo]);
            $xeCu = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($xeCu) {
                // Neu xe da ton tai thi cap nhat thong tin
                $maXe = (int)$xeCu['ma_xe'];

                $stmt = $conn->prepare(
                    "UPDATE xe
                     SET hang_xe = ?, model = ?, nam_sx = ?
                     WHERE ma_xe = ? AND ma_kh = ?"
                );
                $stmt->execute([
                    $hangXe,
                    $model,
                    (int)$namSx,
                    $maXe,
                    $maKh
                ]);

            } else {
                // Neu xe moi thi them vao bang xe
                $stmt = $conn->prepare(
                    "INSERT INTO xe
                     (ma_kh, bien_so, hang_xe, model, nam_sx)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $maKh,
                    $bienSo,
                    $hangXe,
                    $model,
                    (int)$namSx
                ]);

                $maXe = (int)$conn->lastInsertId();
            }

            // Tao lich hen
            $stmt = $conn->prepare(
                "INSERT INTO dat_lich
                 (ma_kh, ma_xe, ngay_hen, gio_hen, trang_thai)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $maKh,
                $maXe,
                $ngayHen,
                $gioHen,
                'Chờ xác nhận'
            ]);

            $maLichMoi = (int)$conn->lastInsertId();

            // Luu tung dich vu cua lich hen
            $stmt = $conn->prepare(
                "INSERT INTO ct_dat_lich
                 (ma_lich, ma_dv, so_luong, don_gia)
                 VALUES (?, ?, ?, ?)"
            );

            foreach ($maDichVu as $maDv) {
                $dv = $dichVuHopLe[$maDv];

                $stmt->execute([
                    $maLichMoi,
                    $maDv,
                    1,
                    $dv['don_gia']
                ]);
            }

            // Chi luu khi tat ca thao tac thanh cong
            $conn->commit();

            $thanhCong = 'Đặt lịch thành công! Gara sẽ xác nhận lịch hẹn của bạn.';

        } catch (PDOException $ex) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $maLichMoi = null;
            $loi = 'Không thể lưu lịch hẹn. Vui lòng kiểm tra dữ liệu và thử lại.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Đặt lịch sửa chữa - DŨNG BY CAR</title>

    <link rel="stylesheet"
          href="../assets/css/booking.css">
</head>

<body>

<header class="booking-header">
    <div class="header-inner">
        <a href="../index.php" class="logo">
            <img src="../assets/images/dung-by-car-logo.png"
                 alt="DŨNG BY CAR">
        </a>

        <a href="../index.php" class="back-home">
            ← Về trang chủ
        </a>
    </div>
</header>

<main class="booking-container">

    <div class="booking-title">
        <p>DŨNG BY CAR · DỊCH VỤ Ô TÔ</p>
        <h1>Đặt lịch <span>sửa chữa & bảo dưỡng</span></h1>
        <div>
            Xin chào, <strong><?= e($hoTen) ?></strong>.
            Hãy điền thông tin để đặt lịch cho xe của bạn.
        </div>
    </div>

    <?php if ($loi): ?>
        <div class="alert error">
            <?= e($loi) ?>
        </div>
    <?php endif; ?>

    <?php if ($thanhCong): ?>

        <div class="success-panel">
            <div class="success-icon">✓</div>

            <h2>Đặt lịch thành công!</h2>

            <p><?= e($thanhCong) ?></p>

            <p>
                Mã lịch hẹn:
                <strong>#<?= e($maLichMoi) ?></strong>
            </p>

            <a href="../index.php" class="submit-btn">
                Quay về trang chủ
            </a>
        </div>

    <?php else: ?>

    <form method="POST" class="booking-form">

        <!-- THONG TIN XE -->
        <section class="form-section">

            <h2><span>01</span> Thông tin xe</h2>

            <div class="form-grid">

                <div class="field">
                    <label>Biển số xe *</label>
                    <input
                        type="text"
                        name="bien_so"
                        maxlength="20"
                        placeholder="Ví dụ: 30A-12345"
                        value="<?= e($_POST['bien_so'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label>Hãng xe *</label>
                    <input
                        type="text"
                        name="hang_xe"
                        maxlength="100"
                        placeholder="Ví dụ: Toyota"
                        value="<?= e($_POST['hang_xe'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label>Dòng xe *</label>
                    <input
                        type="text"
                        name="model"
                        maxlength="100"
                        placeholder="Ví dụ: Vios"
                        value="<?= e($_POST['model'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label>Năm sản xuất *</label>
                    <input
                        type="number"
                        name="nam_sx"
                        min="1980"
                        max="<?= date('Y') + 1 ?>"
                        placeholder="Ví dụ: 2021"
                        value="<?= e($_POST['nam_sx'] ?? '') ?>"
                        required
                    >
                </div>

            </div>
        </section>

        <!-- CHON DICH VU -->
        <section class="form-section">

            <h2><span>02</span> Chọn dịch vụ</h2>

            <p class="section-description">
                Bạn có thể chọn nhiều dịch vụ trong một lần đặt lịch.
            </p>

            <div class="services-grid">

                <?php foreach ($danhSachDichVu as $dv): ?>

                    <label class="service-option">

                        <input
                            type="checkbox"
                            name="dich_vu[]"
                            value="<?= (int)$dv['ma_dv'] ?>"
                            <?= in_array(
                                (int)$dv['ma_dv'],
                                array_map('intval', $_POST['dich_vu'] ?? []),
                                true
                            ) ? 'checked' : '' ?>
                        >

                        <div class="service-info">
                            <strong>
                                <?= e($dv['ten_dv']) ?>
                            </strong>

                            <small>
                                <?= e($dv['mo_ta'] ?? '') ?>
                            </small>

                            <span>
                                <?= number_format(
                                    (float)$dv['don_gia'],
                                    0, ',', '.'
                                ) ?>đ
                            </span>
                        </div>

                    </label>

                <?php endforeach; ?>

            </div>
        </section>

        <!-- NGAY GIO HEN -->
        <section class="form-section">

            <h2><span>03</span> Thời gian hẹn</h2>

            <div class="form-grid">

                <div class="field">
                    <label>Ngày hẹn *</label>
                    <input
                        type="date"
                        name="ngay_hen"
                        min="<?= date('Y-m-d') ?>"
                        value="<?= e($_POST['ngay_hen'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label>Giờ hẹn *</label>
                    <select name="gio_hen" required>
                        <option value="">-- Chọn giờ --</option>

                        <?php
                        $gioDaChon = $_POST['gio_hen'] ?? '';

                        for ($gio = 8; $gio <= 17; $gio++) {
                            $gioHienThi = sprintf('%02d:00', $gio);
                            $selected = $gioDaChon === $gioHienThi
                                ? 'selected'
                                : '';

                            echo '<option value="' .
                                $gioHienThi . '" ' .
                                $selected . '>' .
                                $gioHienThi .
                                '</option>';
                        }
                        ?>
                    </select>
                </div>

            </div>

            <p class="note">
                Giờ nhận lịch dự kiến: 08:00 – 17:00.
                Lịch hẹn cần được gara xác nhận.
            </p>
        </section>

        <div class="form-footer">
            <a href="../index.php" class="cancel-btn">
                Hủy thao tác
            </a>

            <button type="submit" class="submit-btn">
                Xác nhận đặt lịch →
            </button>
        </div>

    </form>

    <?php endif; ?>

</main>

<footer class="booking-footer">
    © <?= date('Y') ?> DŨNG BY CAR.
    Chăm sóc xế yêu, an tâm trên mọi hành trình.
</footer>

</body>
</html>
