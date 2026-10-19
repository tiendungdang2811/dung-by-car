
<?php
require_once __DIR__ . '/_core.php';
guard();

// ======================================
// DUNG BY CAR - QUAN LY DICH VU
// ======================================

// XỬ LÝ THÊM / SỬA / XÓA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify();

    $act = $_POST['act'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    try {
        // XÓA DỊCH VỤ
        if ($act === 'delete') {
            if ($id <= 0) {
                throw new Exception('Mã dịch vụ không hợp lệ.');
            }

            // Không xóa dịch vụ đã được sử dụng
            $stmt = $conn->prepare("
                SELECT COUNT(*)
                FROM ct_dat_lich
                WHERE ma_dv = ?
            ");
            $stmt->execute([$id]);

            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception(
                    'Dịch vụ đã có trong lịch đặt, không thể xóa để bảo vệ dữ liệu.'
                );
            }

            $stmt = $conn->prepare("
                DELETE FROM dich_vu
                WHERE ma_dv = ?
            ");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Không tìm thấy dịch vụ cần xóa.');
            }

            notice('Đã xóa dịch vụ thành công.');
        }

        // THÊM / SỬA DỊCH VỤ
        elseif ($act === 'add' || $act === 'edit') {
            $name = trim((string)($_POST['name'] ?? ''));
            $desc = trim((string)($_POST['desc'] ?? ''));
            $priceRaw = trim((string)($_POST['price'] ?? ''));

            if (
                $priceRaw === '' ||
                !preg_match('/^\d+(\.\d{1,2})?$/D', $priceRaw)
            ) {
                throw new Exception('Giá dịch vụ không hợp lệ.');
            }

            $price = (float)$priceRaw;

            if (
                $name === '' ||
                !is_finite($price) ||
                $price < 0 ||
                $price > 999999999999
            ) {
                throw new Exception(
                    'Tên hoặc giá dịch vụ không hợp lệ.'
                );
            }

            if (mb_strlen($name, 'UTF-8') > 255) {
                throw new Exception('Tên dịch vụ quá dài.');
            }

            if ($act === 'add') {
                $stmt = $conn->prepare("
                    INSERT INTO dich_vu
                    (ten_dv, mo_ta, don_gia)
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([
                    $name,
                    $desc,
                    $price
                ]);

                notice('Đã thêm dịch vụ mới thành công.');
            } else {
                if ($id <= 0) {
                    throw new Exception('Mã dịch vụ không hợp lệ.');
                }

                $stmt = $conn->prepare("
                    SELECT COUNT(*)
                    FROM dich_vu
                    WHERE ma_dv = ?
                ");
                $stmt->execute([$id]);

                if ((int)$stmt->fetchColumn() === 0) {
                    throw new Exception('Không tìm thấy dịch vụ cần sửa.');
                }

                $stmt = $conn->prepare("
                    UPDATE dich_vu
                    SET ten_dv = ?,
                        mo_ta = ?,
                        don_gia = ?
                    WHERE ma_dv = ?
                ");

                $stmt->execute([
                    $name,
                    $desc,
                    $price,
                    $id
                ]);

                notice('Đã cập nhật dịch vụ thành công.');
            }
        } else {
            throw new Exception('Thao tác không hợp lệ.');
        }
    } catch (Throwable $e) {
        notice('Không thực hiện được: ' . $e->getMessage());
    }

    redirect('quan_ly_dich_vu.php');
}

// ======================================
// LẤY DANH SÁCH DỊCH VỤ
// ======================================

$stmt = $conn->query("
    SELECT
        d.ma_dv,
        d.ten_dv,
        d.mo_ta,
        d.don_gia,
        (
            SELECT COUNT(*)
            FROM ct_dat_lich c
            WHERE c.ma_dv = d.ma_dv
        ) AS so_lan_dat
    FROM dich_vu d
    ORDER BY d.ma_dv DESC
");

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalServices = count($services);

// ======================================
// GIAO DIỆN
// ======================================

layout('Quản lý dịch vụ');
?>

<link
    rel="stylesheet"
    href="../assets/css/quan_ly_dich_vu.css"
>

<div class="sv-wrap">

    <!-- HEADER -->
    <div class="sv-top">
        <div class="sv-top-info">
            <h2>Danh mục dịch vụ</h2>
            <p>
                Quản lý các dịch vụ sửa chữa và
                bảo dưỡng ô tô của gara.
            </p>
        </div>

        <button
            type="button"
            class="sv-add-btn"
            onclick="showAddForm()"
        >
            + Thêm dịch vụ
        </button>
    </div>

    <!-- FORM THÊM / SỬA -->
    <section class="sv-form-box" id="serviceFormBox">

        <h2 id="serviceFormTitle">
            Thêm dịch vụ mới
        </h2>

        <form method="POST" id="serviceForm">

            <input
                type="hidden"
                name="token"
                value="<?= h(token()) ?>"
            >

            <input
                type="hidden"
                name="act"
                id="serviceAct"
                value="add"
            >

            <input
                type="hidden"
                name="id"
                id="serviceId"
                value="0"
            >

            <div class="sv-form-grid">

                <div class="sv-field">
                    <label for="serviceName">
                        Tên dịch vụ
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="serviceName"
                        maxlength="255"
                        placeholder="Nhập tên dịch vụ"
                        required
                    >
                </div>

                <div class="sv-field">
                    <label for="servicePrice">
                        Giá dịch vụ (VNĐ)
                    </label>

                    <input
                        type="number"
                        name="price"
                        id="servicePrice"
                        min="0"
                        max="999999999999"
                        step="1"
                        placeholder="Ví dụ: 500000"
                        required
                    >
                </div>

                <div class="sv-field sv-field-full">
                    <label for="serviceDesc">
                        Mô tả dịch vụ
                    </label>

                    <textarea
                        name="desc"
                        id="serviceDesc"
                        placeholder="Nhập mô tả dịch vụ..."
                    ></textarea>
                </div>

            </div>

            <div class="sv-form-actions">
                <button
                    type="submit"
                    class="sv-save"
                >
                    Lưu dịch vụ
                </button>

                <button
                    type="button"
                    class="sv-cancel"
                    onclick="hideServiceForm()"
                >
                    Hủy
                </button>
            </div>

        </form>
    </section>

    <!-- DANH SÁCH DỊCH VỤ -->
    <section class="sv-panel">

        <div class="sv-toolbar">
            <h2>
                Danh sách dịch vụ
                (<?= (int)$totalServices ?>)
            </h2>

            <input
                type="search"
                class="sv-search"
                id="serviceSearch"
                placeholder="Tìm tên hoặc mã dịch vụ..."
                oninput="filterServices()"
            >
        </div>

        <div class="sv-table-wrap">

            <table class="sv-table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên dịch vụ</th>
                        <th>Giá</th>
                        <th>Đã đặt</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody id="serviceTableBody">

                    <?php if (empty($services)): ?>

                        <tr>
                            <td colspan="5" class="sv-empty">
                                Chưa có dịch vụ nào.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($services as $r): ?>
                            <?php
                                $used = (int)$r['so_lan_dat'] > 0;
                            ?>

                            <tr class="sv-service-row">

                                <td>
                                    <span class="sv-id">
                                        #<?= (int)$r['ma_dv'] ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="sv-name">
                                        <?= h($r['ten_dv']) ?>
                                    </span>

                                    <?php if (
                                        trim((string)$r['mo_ta']) !== ''
                                    ): ?>
                                        <span class="sv-desc">
                                            <?= h($r['mo_ta']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="sv-price">
                                        <?= cash($r['don_gia']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($used): ?>
                                        <span class="sv-badge">
                                            <?= (int)$r['so_lan_dat'] ?> lượt
                                        </span>
                                    <?php else: ?>
                                        <span class="sv-badge sv-badge-unused">
                                            Chưa có
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="sv-actions">

                                        <button
                                            type="button"
                                            class="sv-edit-btn"
                                            data-id="<?= (int)$r['ma_dv'] ?>"
                                            data-name="<?= h($r['ten_dv']) ?>"
                                            data-price="<?= h($r['don_gia']) ?>"
                                            data-desc="<?= h($r['mo_ta']) ?>"
                                            onclick="showEditForm(this)"
                                        >
                                            ✎ Sửa
                                        </button>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Bạn chắc chắn muốn xóa dịch vụ này?')"
                                        >
                                            <input
                                                type="hidden"
                                                name="token"
                                                value="<?= h(token()) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="act"
                                                value="delete"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$r['ma_dv'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="sv-delete-btn"
                                                <?= $used
                                                    ? 'disabled title="Dịch vụ đã có trong lịch đặt"'
                                                    : '' ?>
                                            >
                                                Xóa
                                            </button>
                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>

        <p class="sv-note">
            Lưu ý: Dịch vụ đã xuất hiện trong lịch đặt
            sẽ không được xóa để bảo toàn dữ liệu.
            Việc thay đổi giá dịch vụ không làm thay đổi
            giá đã ghi nhận trong các lịch đặt trước đó.
        </p>

    </section>

</div>

<!-- JAVASCRIPT -->

<script>
function showAddForm() {
    const form = document.getElementById('serviceForm');
    form.reset();

    document.getElementById('serviceAct').value = 'add';
    document.getElementById('serviceId').value = '0';

    document.getElementById('serviceFormTitle').textContent =
        'Thêm dịch vụ mới';

    const box = document.getElementById('serviceFormBox');
    box.classList.add('sv-visible');

    box.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });

    document.getElementById('serviceName').focus();
}

function showEditForm(button) {
    document.getElementById('serviceAct').value = 'edit';
    document.getElementById('serviceId').value =
        button.dataset.id;

    document.getElementById('serviceName').value =
        button.dataset.name;

    document.getElementById('servicePrice').value =
        button.dataset.price;

    document.getElementById('serviceDesc').value =
        button.dataset.desc;

    document.getElementById('serviceFormTitle').textContent =
        'Chỉnh sửa dịch vụ #' + button.dataset.id;

    const box = document.getElementById('serviceFormBox');
    box.classList.add('sv-visible');

    box.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });

    document.getElementById('serviceName').focus();
}

function hideServiceForm() {
    document.getElementById('serviceFormBox')
        .classList.remove('sv-visible');

    document.getElementById('serviceForm').reset();
}

function filterServices() {
    const keyword = document
        .getElementById('serviceSearch')
        .value
        .toLocaleLowerCase('vi-VN')
        .trim();

    const rows = document.querySelectorAll('.sv-service-row');

    rows.forEach(function(row) {
        const content = row.textContent
            .toLocaleLowerCase('vi-VN');

        row.style.display = content.includes(keyword)
            ? ''
            : 'none';
    });
}
</script>

<?php done(); ?>
