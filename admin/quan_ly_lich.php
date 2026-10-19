
<?php
require_once __DIR__ . '/_core.php';
guard();

$loi = '';

$trangThaiHopLe = [
    'Chờ xác nhận',
    'Đã xác nhận',
    'Đang xử lý',
    'Hoàn thành',
    'Đã hủy'
];

// ============================================
// CẬP NHẬT TRẠNG THÁI LỊCH HẸN
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify();

    $maLich = filter_input(
        INPUT_POST,
        'ma_lich',
        FILTER_VALIDATE_INT
    );

    $trangThaiMoi = $_POST['trang_thai'] ?? '';

    try {

        if (
            !$maLich ||
            !in_array(
                $trangThaiMoi,
                $trangThaiHopLe,
                true
            )
        ) {
            throw new Exception(
                'Dữ liệu cập nhật không hợp lệ.'
            );
        }

        $conn->beginTransaction();

        // Khóa lịch trong lúc cập nhật
        $stmt = $conn->prepare("
            SELECT trang_thai
            FROM dat_lich
            WHERE ma_lich = ?
            FOR UPDATE
        ");

        $stmt->execute([$maLich]);

        $trangThaiCu = $stmt->fetchColumn();

        if ($trangThaiCu === false) {
            throw new Exception(
                'Không tìm thấy lịch hẹn.'
            );
        }

        if ($trangThaiCu === 'Đã hủy') {
            throw new Exception(
                'Lịch đã hủy không thể cập nhật.'
            );
        }

        // Kiểm tra hóa đơn
        $stmt = $conn->prepare("
            SELECT trang_thai
            FROM hoa_don
            WHERE ma_lich = ?
            FOR UPDATE
        ");

        $stmt->execute([$maLich]);

        $trangThaiHoaDon = $stmt->fetchColumn();

        // Đã thanh toán thì khóa lịch
        if ($trangThaiHoaDon === 'Đã thanh toán') {
            throw new Exception(
                'Hóa đơn đã thanh toán. Lịch hẹn đã được khóa.'
            );
        }

        // Không cho hủy khi đã có hóa đơn
        if (
            $trangThaiMoi === 'Đã hủy' &&
            $trangThaiHoaDon !== false
        ) {
            throw new Exception(
                'Lịch đã lập hóa đơn, không thể hủy.'
            );
        }

        // Không cho chuyển lùi sau khi hoàn thành
        if (
            $trangThaiCu === 'Hoàn thành' &&
            $trangThaiMoi !== 'Hoàn thành'
        ) {
            throw new Exception(
                'Lịch đã hoàn thành, không thể chuyển về trạng thái trước.'
            );
        }

        // Cập nhật trạng thái
        $stmt = $conn->prepare("
            UPDATE dat_lich
            SET trang_thai = ?
            WHERE ma_lich = ?
        ");

        $stmt->execute([
            $trangThaiMoi,
            $maLich
        ]);

        $conn->commit();

        notice(
            'Cập nhật trạng thái lịch hẹn thành công.'
        );

        redirect('quan_ly_lich.php');

    } catch (Throwable $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $loi = $e->getMessage();
    }
}

// ============================================
// LẤY DANH SÁCH LỊCH HẸN
// ============================================

$stmt = $conn->query("
    SELECT
        l.ma_lich,
        l.ngay_hen,
        l.gio_hen,
        l.trang_thai,

        k.ho_ten,
        k.sdt,

        x.bien_so,
        x.hang_xe,
        x.model,

        h.ma_hd,
        h.trang_thai AS trang_thai_hoa_don

    FROM dat_lich l

    LEFT JOIN khach_hang k
        ON k.ma_kh = l.ma_kh

    LEFT JOIN xe x
        ON x.ma_xe = l.ma_xe

    LEFT JOIN hoa_don h
        ON h.ma_lich = l.ma_lich

    ORDER BY l.ma_lich DESC
");

$dsLich = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// GIAO DIỆN
// ============================================

layout('Quản lý lịch hẹn');

if ($loi !== '') {
    echo '<p class="notice" style="color:#ff9999">'
        . h($loi)
        . '</p>';
}
?>

<style>
.lich-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

.lich-table th,
.lich-table td {
    padding: 13px;
    border-bottom: 1px solid #34445a;
    text-align: left;
    vertical-align: middle;
}

.lich-table th {
    color: #ff8a42;
}

.lich-wrap {
    overflow-x: auto;
}

.lich-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.lich-actions select {
    padding: 9px;
    border-radius: 6px;
    background: #0d2033;
    color: #fff;
    border: 1px solid #40546a;
}

.lich-actions button {
    padding: 10px 12px;
    border: none;
    border-radius: 6px;
    background: #ff7a2f;
    color: #111;
    font-weight: bold;
    cursor: pointer;
}

.lich-small {
    font-size: 12px;
    opacity: 0.7;
    margin-top: 5px;
}

.lich-status {
    display: inline-block;
    padding: 7px 10px;
    border-radius: 6px;
    background: #243d57;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
}

.lich-done {
    background: #dcfce7;
    color: #166534;
}

.lich-cancel {
    background: #fee2e2;
    color: #991b1b;
}

.lich-locked {
    color: #86efac;
    font-size: 13px;
    font-weight: 700;
}

.lich-bill {
    color: #ff995b;
    text-decoration: none;
    font-weight: 600;
}

.lich-bill:hover {
    text-decoration: underline;
}

.lich-note {
    color: #a8bbcf;
    font-size: 13px;
    margin-bottom: 16px;
}
</style>

<section class="panel">

    <h2>Danh sách lịch hẹn</h2>

    <p class="lich-note">
        Trang này dùng để xác nhận và quản lý tiến độ
        sửa chữa. Chi phí phát sinh và thanh toán
        được xử lý tại mục Quản lý hóa đơn.
    </p>

    <div class="lich-wrap">

        <table class="lich-table">

            <thead>
                <tr>
                    <th>Mã lịch</th>
                    <th>Khách hàng</th>
                    <th>Xe</th>
                    <th>Ngày hẹn</th>
                    <th>Trạng thái</th>
                    <th>Hóa đơn</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($dsLich as $lich): ?>

                    <?php
                    $daThanhToan =
                        $lich['trang_thai_hoa_don']
                        === 'Đã thanh toán';

                    $daHuy =
                        $lich['trang_thai'] === 'Đã hủy';

                    $daHoanThanh =
                        $lich['trang_thai'] === 'Hoàn thành';

                    $khoaLich =
                        $daThanhToan ||
                        $daHuy ||
                        $daHoanThanh;
                    ?>

                    <tr>

                        <td>
                            #<?= (int)$lich['ma_lich'] ?>
                        </td>

                        <td>
                            <strong>
                                <?= h($lich['ho_ten']) ?>
                            </strong>

                            <div class="lich-small">
                                <?= h($lich['sdt']) ?>
                            </div>
                        </td>

                        <td>
                            <?= h($lich['hang_xe']) ?>
                            <?= h($lich['model']) ?>

                            <div class="lich-small">
                                <?= h($lich['bien_so']) ?>
                            </div>
                        </td>

                        <td>
                            <?= h($lich['ngay_hen']) ?>

                            <div class="lich-small">
                                <?= h($lich['gio_hen']) ?>
                            </div>
                        </td>

                        <td>

                            <?php
                            $statusClass = 'lich-status';

                            if ($daHoanThanh) {
                                $statusClass .= ' lich-done';
                            }

                            if ($daHuy) {
                                $statusClass .= ' lich-cancel';
                            }
                            ?>

                            <span class="<?= $statusClass ?>">
                                <?= h($lich['trang_thai']) ?>
                            </span>

                        </td>

                        <td>

                            <?php if ($daThanhToan): ?>

                                <span class="lich-locked">
                                    ✓ Đã thanh toán 🔒
                                </span>

                            <?php elseif ($lich['ma_hd']): ?>

                                <span class="lich-status">
                                    Chưa thanh toán
                                </span>

                            <?php elseif ($daHuy): ?>

                                <span class="lich-small">
                                    Đã hủy
                                </span>

                            <?php elseif ($daHoanThanh): ?>

                                <a
                                    class="lich-bill"
                                    href="quan_ly_hoa_don.php"
                                >
                                    → Chờ lập hóa đơn
                                </a>

                            <?php else: ?>

                                <span class="lich-small">
                                    Chưa lập hóa đơn
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($khoaLich): ?>

                                <?php if ($daThanhToan): ?>

                                    <span class="lich-locked">
                                        🔒 Đã khóa
                                    </span>

                                <?php elseif ($daHuy): ?>

                                    <span class="lich-small">
                                        Lịch đã hủy
                                    </span>

                                <?php else: ?>

                                    <span class="lich-small">
                                        ✓ Đã hoàn thành
                                    </span>

                                <?php endif; ?>

                            <?php else: ?>

                                <form
                                    method="POST"
                                    class="lich-actions"
                                >

                                    <input
                                        type="hidden"
                                        name="token"
                                        value="<?= h(token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="ma_lich"
                                        value="<?= (int)$lich['ma_lich'] ?>"
                                    >

                                    <select name="trang_thai">

                                        <?php foreach (
                                            $trangThaiHopLe as $tt
                                        ): ?>

                                            <option
                                                value="<?= h($tt) ?>"
                                                <?= $tt === $lich['trang_thai']
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                <?= h($tt) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                    <button type="submit">
                                        Lưu
                                    </button>

                                </form>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($dsLich)): ?>

                    <tr>
                        <td colspan="7">
                            Chưa có lịch hẹn nào.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>

<?php done(); ?>
