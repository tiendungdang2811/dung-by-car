
<?php
session_start();
require_once __DIR__ . '/config/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function hienThiTien($value) {
    return number_format((float)$value, 0, ',', '.') . 'đ';
}

// ==========================================
// LẤY DANH SÁCH DỊCH VỤ TỪ DATABASE
// ==========================================

$danhSachDichVu = [];
$loiDichVu = false;

try {
    $stmt = $conn->query("
        SELECT
            ma_dv,
            ten_dv,
            mo_ta,
            don_gia
        FROM dich_vu
        ORDER BY ma_dv ASC
    ");

    $danhSachDichVu = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {
    $loiDichVu = true;
}

// ==========================================
// ICON DỊCH VỤ
// ==========================================

function iconDichVu($ten) {
    $ten = mb_strtolower(trim((string)$ten), 'UTF-8');

    if (str_contains($ten, 'thay dầu')) {
        return 'thay-dau.png';
    }

    if (str_contains($ten, 'bảo dưỡng')) {
        return 'bao-duong.png';
    }

    if (str_contains($ten, 'phanh')) {
        return 'kiem-tra-phanh.png';
    }

    if (
        str_contains($ten, 'vệ sinh điều hòa') ||
        str_contains($ten, 'vệ sinh điều hoà')
    ) {
        return 've-sinh-dieu-hoa.png';
    }

    if (str_contains($ten, 'lọc gió')) {
        return 'thay-loc-gio.png';
    }

    if (str_contains($ten, 'rửa xe')) {
        return 'rua-xe.png';
    }

    if (str_contains($ten, 'đánh bóng')) {
        return 'danh-bong-son.png';
    }

    if (str_contains($ten, 'nạp gas')) {
        return 'nap-gas-dieu-hoa.png';
    }

    if (str_contains($ten, 'ắc quy')) {
        return 'thay-ac-quy.png';
    }

    if (str_contains($ten, 'lốp')) {
        return 'kiem-tra-lop.png';
    }

    return 'bao-duong.png';
}

// ==========================================
// TRẠNG THÁI ĐĂNG NHẬP
// ==========================================

$daDangNhap = isset($_SESSION['ma_kh']);
$hoTen = $_SESSION['ho_ten'] ?? 'Khách hàng';

$linkDatLich = $daDangNhap
    ? 'dat_lich/dat_lich.php'
    : 'auth/login.php';

$linkLichCuaToi = $daDangNhap
    ? 'dat_lich/lich_cua_toi.php'
    : 'auth/login.php';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>DŨNG BY CAR - Gara Ô Tô Chuyên Nghiệp</title>

    <meta
        name="description"
        content="DŨNG BY CAR - Đặt lịch sửa chữa và bảo dưỡng ô tô trực tuyến."
    >

    <link
        rel="stylesheet"
        href="assets/css/style.css?v=33"
    >

    <!-- Chỉ bổ sung căn vị trí khi chuyển mục -->
    <style>
        #trang-chu,
        #dich-vu,
        #gioi-thieu,
        #lien-he {
            scroll-margin-top: 120px;
        }
    </style>
</head>

<body>

<!-- ==========================================
     HEADER
========================================== -->

<header class="site-header">

    <div class="container header-inner">

        <!-- LOGO -->

        <a
            href="index.php"
            class="brand"
            aria-label="DŨNG BY CAR - Trang chủ"
        >
            <img
                src="assets/images/dung-by-car-logo.png"
                alt="DŨNG BY CAR"
                class="brand-logo"
            >
        </a>

        <!-- MENU -->

        <nav class="main-nav" aria-label="Menu chính">

            <a href="#trang-chu">
                Trang chủ
            </a>

            <a href="#dich-vu">
                Dịch vụ
            </a>

            <a href="#gioi-thieu">
                Giới thiệu
            </a>

            <a href="#lien-he">
                Liên hệ
            </a>

        </nav>

        <!-- NÚT BÊN PHẢI -->

        <div class="header-actions">

            <?php if ($daDangNhap): ?>

                <span
                    class="welcome-text"
                    title="<?= e($hoTen) ?>"
                >
                    Xin chào, <?= e($hoTen) ?>
                </span>

                <a
                    href="<?= e($linkLichCuaToi) ?>"
                    class="header-outline"
                >
                    <img
                        src="assets/images/icons/chi-tiet.png"
                        alt=""
                        class="btn-icon"
                    >

                    <span>Lịch của tôi</span>
                </a>

                <a
                    href="auth/logout.php"
                    class="header-outline orange-outline"
                >
                    <span>Đăng xuất</span>
                </a>

            <?php else: ?>

                <a
                    href="auth/login.php"
                    class="header-outline"
                >
                    <img
                        src="assets/images/icons/dang-nhap.png"
                        alt=""
                        class="btn-icon"
                    >

                    <span>Đăng nhập</span>
                </a>

                <a
                    href="auth/dangki.php"
                    class="header-outline orange-outline"
                >
                    <img
                        src="assets/images/icons/dang-ky.png"
                        alt=""
                        class="btn-icon"
                    >

                    <span>Đăng ký</span>
                </a>

            <?php endif; ?>

            <a
                href="<?= e($linkDatLich) ?>"
                class="header-book"
            >
                <img
                    src="assets/images/icons/dat-lich.png"
                    alt=""
                    class="btn-icon"
                >

                <span>Đặt lịch ngay</span>
            </a>

        </div>

    </div>

</header>

<main>

    <!-- ======================================
         TRANG CHỦ / BANNER
    ====================================== -->

    <section class="hero" id="trang-chu">

        <div class="container hero-inner">

            <div class="hero-content">

                <p class="hero-label">

                    <span class="hero-label-icon">
                        ⚒
                    </span>

                    DŨNG BY CAR · GARA Ô TÔ CHUYÊN NGHIỆP

                </p>

                <h1>

                    Chăm xe tận tâm,<br>

                    <span>
                        vững bước mọi hành trình.
                    </span>

                </h1>

                <p class="hero-description">

                    Dịch vụ sửa chữa và bảo dưỡng ô tô chuyên nghiệp,
                    tận tâm trong từng chi tiết, đồng hành cùng bạn
                    trên mọi cung đường.

                </p>

                <div class="hero-buttons">

                    <a
                        href="<?= e($linkDatLich) ?>"
                        class="hero-primary"
                    >
                        <img
                            src="assets/images/icons/dat-lich.png"
                            alt=""
                            class="btn-icon"
                        >

                        <span>Đặt lịch ngay</span>

                        <span class="arrow">→</span>
                    </a>

                    <a
                        href="#dich-vu"
                        class="hero-secondary"
                    >
                        <span>Khám phá dịch vụ</span>

                        <span class="arrow">→</span>
                    </a>

                </div>

            </div>

        </div>

    </section>

    <!-- ======================================
         BA KHỐI NỔI BẬT
    ====================================== -->

    <section class="features">

        <div class="container features-grid">

            <div class="feature-item">

                <div class="feature-symbol">
                    ✓
                </div>

                <div>
                    <h3>Chăm sóc tận tâm</h3>
                    <p>Quan tâm từng chi tiết của xe</p>
                </div>

            </div>

            <div class="feature-item">

                <div class="feature-symbol">
                    ✓
                </div>

                <div>
                    <h3>Dịch vụ đa dạng</h3>
                    <p>Đáp ứng nhiều nhu cầu bảo dưỡng</p>
                </div>

            </div>

            <div class="feature-item">

                <div class="feature-symbol">
                    ✓
                </div>

                <div>
                    <h3>Đặt lịch trực tuyến</h3>
                    <p>Thao tác nhanh chóng, thuận tiện</p>
                </div>

            </div>

        </div>

    </section>

    <!-- ======================================
         DỊCH VỤ
    ====================================== -->

    <section class="services" id="dich-vu">

        <div class="container">

            <div class="section-heading">

                <p class="section-tag">
                    DỊCH VỤ CỦA CHÚNG TÔI
                </p>

                <h2>
                    Chăm sóc xe
                    <span>toàn diện</span>
                </h2>

                <p>
                    Lựa chọn dịch vụ phù hợp với chiếc xe của bạn.
                    Đặt lịch trực tuyến chỉ với vài thao tác.
                </p>

            </div>

            <div class="service-list">

                <?php if ($loiDichVu): ?>

                    <p class="empty-message">
                        Không thể tải danh sách dịch vụ.
                        Vui lòng kiểm tra kết nối MySQL.
                    </p>

                <?php elseif (empty($danhSachDichVu)): ?>

                    <p class="empty-message">
                        Hiện chưa có dịch vụ để hiển thị.
                    </p>

                <?php else: ?>

                    <?php foreach ($danhSachDichVu as $dv): ?>

                        <article class="service-card">

                            <div class="service-card-top">

                                <div class="service-icon">

                                    <img
                                        src="assets/images/icons/<?= e(iconDichVu($dv['ten_dv'])) ?>"
                                        alt="<?= e($dv['ten_dv']) ?>"
                                        class="service-icon-img"
                                    >

                                </div>

                                <span class="service-badge">
                                    DỊCH VỤ
                                </span>

                            </div>

                            <h3>
                                <?= e($dv['ten_dv']) ?>
                            </h3>

                            <p class="service-description">
                                <?= e($dv['mo_ta'] ?? '') ?>
                            </p>

                            <div class="service-bottom">

                                <div>

                                    <small>
                                        Giá tham khảo
                                    </small>

                                    <div class="price">
                                        <?= hienThiTien($dv['don_gia']) ?>
                                    </div>

                                </div>

                                <a
                                    href="<?= e($linkDatLich) ?>"
                                    class="service-arrow"
                                    aria-label="Đặt lịch <?= e($dv['ten_dv']) ?>"
                                >
                                    <img
                                        src="assets/images/icons/chi-tiet.png"
                                        alt=""
                                        class="service-arrow-icon"
                                    >
                                </a>

                            </div>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>

    </section>

    <!-- ======================================
         GIỚI THIỆU
    ====================================== -->

    <section class="about" id="gioi-thieu">

        <div class="container about-content">

            <p class="section-tag">
                VỀ DŨNG BY CAR
            </p>

            <h2>
                Đồng hành cùng
                <span>mọi hành trình</span>
            </h2>

            <p class="about-description">

                DŨNG BY CAR là website hỗ trợ khách hàng đặt lịch
                sửa chữa, kiểm tra và bảo dưỡng ô tô trực tuyến.

                <br>

                Khách hàng có thể lựa chọn dịch vụ, đăng ký lịch hẹn
                và theo dõi trạng thái xử lý một cách thuận tiện.

            </p>

            <div class="about-features">

                <div>✓ Dịch vụ đa dạng</div>

                <div>✓ Giá tham khảo rõ ràng</div>

                <div>✓ Đặt lịch dễ dàng</div>

                <div>✓ Theo dõi lịch hẹn</div>

            </div>

            <a
                href="#dich-vu"
                class="about-button"
            >
                Khám phá dịch vụ →
            </a>

        </div>

    </section>

    <!-- ======================================
         LIÊN HỆ
    ====================================== -->

    <section class="contact" id="lien-he">

        <div class="container contact-content">

            <div class="contact-text">

                <p class="section-tag">
                    LIÊN HỆ VỚI CHÚNG TÔI
                </p>

                <h2>
                    Sẵn sàng chăm sóc chiếc xe của bạn?
                </h2>

                <p>
                    Đặt lịch trực tuyến để lựa chọn thời gian
                    và dịch vụ phù hợp.
                </p>

                <div class="contact-details gara-contact-details">

                    <a
                        class="contact-detail gara-contact-link"
                        href="tel:190066776996"
                        aria-label="Gọi hotline 1900 6677 6996"
                    >
                        <img
                            src="assets/images/icons/lien-he.png"
                            alt=""
                            class="contact-icon"
                        >

                        <span>

                            <strong>
                                Hotline: 1900 6677 6996
                            </strong>

                            <small>
                                Nhấn để gọi điện
                            </small>

                        </span>
                    </a>

                    <a
                        class="contact-detail gara-contact-link"
                        href="https://www.google.com/maps/search/?api=1&amp;query=S%E1%BB%91%207-9%20Nguy%E1%BB%85n%20V%C4%83n%20Linh%2C%20Long%20Bi%C3%AAn%2C%20H%C3%A0%20N%E1%BB%99i"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Mở địa chỉ gara trên Google Maps"
                    >
                        <img
                            src="assets/images/icons/dia-chi.png"
                            alt=""
                            class="contact-icon"
                        >

                        <span>

                            <strong>
                                Số 7–9 Nguyễn Văn Linh, Long Biên, Hà Nội
                            </strong>

                            <small>
                                Nhấn để xem trên Google Maps ↗
                            </small>

                        </span>
                    </a>

                </div>

            </div>

            <a
                href="<?= e($linkDatLich) ?>"
                class="contact-button"
            >
                <img
                    src="assets/images/icons/dat-lich.png"
                    alt=""
                    class="btn-icon"
                >

                <span>
                    Đặt lịch ngay
                </span>

                <span>→</span>
            </a>

        </div>

    </section>

</main>

<!-- ==========================================
     FOOTER
========================================== -->

<footer class="footer">

    <div class="container footer-grid">

        <!-- THƯƠNG HIỆU -->

        <div class="footer-brand">

            <img
                src="assets/images/dung-by-car-logo.png"
                alt="DŨNG BY CAR"
                class="footer-logo"
            >

            <p>
                Chăm sóc xế yêu,
                an tâm trên mọi hành trình.
            </p>

        </div>

        <!-- LIÊN KẾT -->

        <div class="footer-links">

            <h3>Liên kết</h3>

            <a href="#trang-chu">
                Trang chủ
            </a>

            <a href="#dich-vu">
                Dịch vụ
            </a>

            <a href="#gioi-thieu">
                Giới thiệu
            </a>

            <a href="#lien-he">
                Liên hệ
            </a>

            <a href="<?= e($linkLichCuaToi) ?>">
                Lịch của tôi
            </a>

        </div>

        <!-- THÔNG TIN GARA -->

        <div class="footer-info gara-footer-info">

            <h3>
                Thông tin gara
            </h3>

            <div class="gara-info-row">

                <span
                    class="gara-info-icon"
                    aria-hidden="true"
                >
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="7" r="4"/>
                        <path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
                    </svg>
                </span>

                <span>
                    <strong>Quản lý:</strong>
                    Đặng Tiến Dũng
                </span>

            </div>

            <div class="gara-info-row">

                <span
                    class="gara-info-icon"
                    aria-hidden="true"
                >
                    <svg viewBox="0 0 24 24">
                        <path d="M22 16.9v3a2 2 0 0 1-2.2 2A19.8 19.8 0 0 1 3.1 5.2 2 2 0 0 1 5.1 3h3a2 2 0 0 1 2 1.7l.5 3a2 2 0 0 1-.6 1.7L8.1 11a16 16 0 0 0 4.9 4.9l1.6-1.9a2 2 0 0 1 1.7-.6l3 .5a2 2 0 0 1 1.7 1.9z"/>
                    </svg>
                </span>

                <span>
                    <strong>Hotline:</strong>

                    <a href="tel:190066776996">
                        1900 6677 6996
                    </a>
                </span>

            </div>

            <div class="gara-info-row">

                <span
                    class="gara-info-icon"
                    aria-hidden="true"
                >
                    <svg viewBox="0 0 24 24">
                        <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0z"/>
                        <circle cx="12" cy="10" r="2.5"/>
                    </svg>
                </span>

                <span>
                    <strong>Địa chỉ:</strong>

                    <a
                        href="https://www.google.com/maps/search/?api=1&amp;query=S%E1%BB%91%207-9%20Nguy%E1%BB%85n%20V%C4%83n%20Linh%2C%20Long%20Bi%C3%AAn%2C%20H%C3%A0%20N%E1%BB%99i"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Số 7–9 Nguyễn Văn Linh, Long Biên, Hà Nội
                    </a>

                </span>

            </div>

            <div class="gara-info-row">

                <span
                    class="gara-info-icon"
                    aria-hidden="true"
                >
                    <svg viewBox="0 0 24 24">
                        <path d="M14.7 6.3a4 4 0 0 0-5 5L3 18l3 3 6.7-6.7a4 4 0 0 0 5-5l-3 3-3-3z"/>
                    </svg>
                </span>

                <span>
                    <strong>Dịch vụ:</strong>
                    Bảo dưỡng định kỳ, Sửa chữa chung, Chăm sóc xe
                </span>

            </div>

            <div class="gara-info-row">

                <span
                    class="gara-info-icon"
                    aria-hidden="true"
                >
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 7v5l3 2"/>
                    </svg>
                </span>

                <span>
                    <strong>Thời gian làm việc:</strong>
                    08:00 - 18:00 (Thứ 2 - Chủ Nhật)
                </span>

            </div>

            <p class="gara-footer-description">
                Hệ thống đặt lịch sửa chữa và bảo dưỡng ô tô.
            </p>

            <a
                href="<?= e($linkDatLich) ?>"
                class="gara-footer-book"
            >
                Đặt lịch sửa chữa →
            </a>

        </div>

    </div>

    <div class="container copyright">

        © <?= date('Y') ?>
        DŨNG BY CAR. All rights reserved.

    </div>

</footer>

<!-- ==========================================
     JAVASCRIPT: MENU ACTIVE CHÍNH XÁC
========================================== -->

<script>
document.addEventListener("DOMContentLoaded", function () {

    const menuLinks = document.querySelectorAll(
        ".main-nav a[href^='#']"
    );

    const sectionIds = [
        "trang-chu",
        "dich-vu",
        "gioi-thieu",
        "lien-he"
    ];

    const sections = sectionIds
        .map(function (id) {
            return document.getElementById(id);
        })
        .filter(Boolean);

    if (sections.length === 0) {
        return;
    }

    const header = document.querySelector(".site-header");

    let dangCuonBangMenu = false;
    let timerCuon = null;
    let idDaBam = "";

    // ======================================
    // ĐỔI GẠCH CAM
    // ======================================

    function doiMenuActive(id) {

        menuLinks.forEach(function (link) {

            const active =
                link.getAttribute("href") === "#" + id;

            link.classList.toggle("active", active);

            if (active) {
                link.setAttribute("aria-current", "location");
            } else {
                link.removeAttribute("aria-current");
            }

        });
    }

    // ======================================
    // CHIỀU CAO HEADER
    // ======================================

    function layChieuCaoHeader() {
        return header ? header.getBoundingClientRect().height : 0;
    }

    // ======================================
    // TÌM MỤC ĐANG XEM
    // ======================================

    function mucTheoViTriCuon() {

        const viTriKiemTra =
            window.scrollY + layChieuCaoHeader() + 60;

        let idHienTai = sections[0].id;

        sections.forEach(function (section) {

            const sectionTop =
                section.getBoundingClientRect().top +
                window.scrollY;

            if (viTriKiemTra >= sectionTop) {
                idHienTai = section.id;
            }

        });

        const daDenCuoiTrang =
            window.innerHeight + window.scrollY >=
            document.documentElement.scrollHeight - 8;

        if (daDenCuoiTrang) {
            idHienTai = sections[sections.length - 1].id;
        }

        return idHienTai;
    }

    // ======================================
    // CẬP NHẬT KHI CUỘN THỦ CÔNG
    // ======================================

    function capNhatKhiCuon() {

        if (dangCuonBangMenu) {
            return;
        }

        doiMenuActive(mucTheoViTriCuon());
    }

    // ======================================
    // CHUYỂN ĐẾN MỤC ĐƯỢC BẤM
    // ======================================

    function chuyenDenMuc(id, doiURL = true) {

        const section = document.getElementById(id);

        if (!section) {
            return;
        }

        clearTimeout(timerCuon);

        dangCuonBangMenu = true;
        idDaBam = id;

        doiMenuActive(id);

        if (doiURL) {
            history.pushState(null, "", "#" + id);
        }

        const viTriDich =
            section.getBoundingClientRect().top +
            window.scrollY -
            layChieuCaoHeader();

        const maxScroll =
            document.documentElement.scrollHeight -
            window.innerHeight;

        const viTriCuon =
            Math.min(
                Math.max(0, viTriDich),
                Math.max(0, maxScroll)
            );

        window.scrollTo({
            top: viTriCuon,
            behavior: "smooth"
        });

        // Giữ mục được bấm trong lúc trình duyệt cuộn
        timerCuon = setTimeout(function () {

            dangCuonBangMenu = false;

            // Giữ đúng mục vừa bấm.
            // Chỉ khi người dùng tự cuộn tiếp mới đổi.
            doiMenuActive(idDaBam);

        }, 1200);
    }

    // ======================================
    // BẤM CÁC NÚT MENU
    // ======================================

    menuLinks.forEach(function (link) {

        link.addEventListener("click", function (event) {

            const id =
                this.getAttribute("href").substring(1);

            if (!document.getElementById(id)) {
                return;
            }

            event.preventDefault();

            chuyenDenMuc(id);

        });

    });

    // ======================================
    // BẤM LIÊN KẾT KHÁC TRONG TRANG
    // VD: KHÁM PHÁ DỊCH VỤ
    // ======================================

    document.querySelectorAll(
        'a[href="#trang-chu"],' +
        'a[href="#dich-vu"],' +
        'a[href="#gioi-thieu"],' +
        'a[href="#lien-he"]'
    ).forEach(function (link) {

        if (link.closest(".main-nav")) {
            return;
        }

        link.addEventListener("click", function (event) {

            const id =
                this.getAttribute("href").substring(1);

            if (!document.getElementById(id)) {
                return;
            }

            event.preventDefault();

            chuyenDenMuc(id);

        });

    });

    // ======================================
    // NGƯỜI DÙNG TỰ CUỘN CHUỘT
    // ======================================

    let cuonThuCong = false;

    function batDauCuonThuCong() {

        if (dangCuonBangMenu) {

            clearTimeout(timerCuon);

            dangCuonBangMenu = false;
        }

        cuonThuCong = true;
    }

    window.addEventListener(
        "wheel",
        batDauCuonThuCong,
        { passive: true }
    );

    window.addEventListener(
        "touchstart",
        batDauCuonThuCong,
        { passive: true }
    );

    window.addEventListener(
        "keydown",
        function (event) {

            const phimCuon = [
                "ArrowDown",
                "ArrowUp",
                "PageDown",
                "PageUp",
                "Home",
                "End",
                " "
            ];

            if (phimCuon.includes(event.key)) {
                batDauCuonThuCong();
            }

        }
    );

    window.addEventListener(
        "scroll",
        function () {

            if (dangCuonBangMenu) {
                return;
            }

            capNhatKhiCuon();

        },
        { passive: true }
    );

    // ======================================
    // THAY ĐỔI KÍCH THƯỚC MÀN HÌNH
    // ======================================

    window.addEventListener(
        "resize",
        function () {

            if (!dangCuonBangMenu) {
                capNhatKhiCuon();
            }

        }
    );

    // ======================================
    // KHI URL THAY ĐỔI
    // ======================================

    window.addEventListener(
        "hashchange",
        function () {

            const id =
                window.location.hash.substring(1);

            if (sectionIds.includes(id)) {
                chuyenDenMuc(id, false);
            }

        }
    );

    // ======================================
    // KHI VỪA MỞ WEBSITE
    // ======================================

    const idBanDau =
        window.location.hash.substring(1);

    if (sectionIds.includes(idBanDau)) {

        doiMenuActive(idBanDau);

        // Đợi trình duyệt hoàn thành bố cục
        window.addEventListener("load", function () {
            chuyenDenMuc(idBanDau, false);
        });

    } else {

        capNhatKhiCuon();

    }

});
</script>

</body>
</html>
