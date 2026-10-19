-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 19, 2026 lúc 10:16 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `gara_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin`
--

CREATE TABLE `admin` (
  `ma_admin` int(11) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `ten_dang_nhap` varchar(100) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin`
--

INSERT INTO `admin` (`ma_admin`, `ho_ten`, `ten_dang_nhap`, `mat_khau`, `ngay_tao`) VALUES
(1, 'Đặng Tiến Dũng', 'Đặng Tiến Dũng', '$2y$10$QzJGPBEmXUEdxt05jvXTzeZoN1jnZMm9NVVqh8e0c.88H4GrrYCZe', '2026-10-13 16:46:02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ct_dat_lich`
--

CREATE TABLE `ct_dat_lich` (
  `ma_lich` int(11) NOT NULL,
  `ma_dv` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL DEFAULT 1,
  `don_gia` decimal(12,2) NOT NULL,
  `loai_dich_vu` enum('dat_truoc','phat_sinh') NOT NULL DEFAULT 'dat_truoc'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `ct_dat_lich`
--

INSERT INTO `ct_dat_lich` (`ma_lich`, `ma_dv`, `so_luong`, `don_gia`, `loai_dich_vu`) VALUES
(1, 2, 1, 1200000.00, 'dat_truoc'),
(1, 6, 1, 100000.00, 'dat_truoc'),
(1, 10, 1, 200000.00, 'dat_truoc'),
(2, 2, 1, 1200000.00, 'dat_truoc'),
(2, 3, 1, 300000.00, 'dat_truoc'),
(2, 5, 1, 250000.00, 'dat_truoc'),
(2, 6, 1, 100000.00, 'dat_truoc'),
(2, 7, 1, 800000.00, 'dat_truoc'),
(2, 10, 1, 200000.00, 'dat_truoc'),
(3, 2, 1, 1200000.00, 'dat_truoc'),
(3, 3, 1, 300000.00, 'dat_truoc'),
(3, 6, 1, 100000.00, 'dat_truoc'),
(3, 7, 1, 800000.00, 'dat_truoc'),
(3, 10, 1, 200000.00, 'dat_truoc'),
(4, 11, 1, 1000000.00, 'dat_truoc'),
(5, 2, 1, 1200000.00, 'dat_truoc'),
(5, 3, 1, 300000.00, 'phat_sinh'),
(5, 11, 1, 1000000.00, 'dat_truoc'),
(6, 1, 1, 500000.00, 'dat_truoc'),
(6, 6, 1, 200000.00, 'dat_truoc'),
(6, 7, 1, 800000.00, 'dat_truoc'),
(7, 1, 1, 500000.00, 'dat_truoc'),
(7, 2, 1, 1200000.00, 'dat_truoc'),
(7, 3, 1, 300000.00, 'dat_truoc'),
(7, 4, 1, 400000.00, 'dat_truoc'),
(7, 5, 1, 250000.00, 'dat_truoc'),
(7, 7, 1, 800000.00, 'dat_truoc'),
(7, 8, 1, 600000.00, 'dat_truoc'),
(7, 9, 1, 1800000.00, 'dat_truoc'),
(7, 10, 1, 200000.00, 'dat_truoc'),
(7, 11, 1, 1000000.00, 'dat_truoc'),
(8, 2, 1, 1200000.00, 'dat_truoc'),
(8, 5, 1, 250000.00, 'phat_sinh'),
(8, 6, 1, 200000.00, 'phat_sinh'),
(9, 2, 1, 1200000.00, 'dat_truoc'),
(9, 9, 1, 1800000.00, 'phat_sinh'),
(10, 6, 1, 200000.00, 'dat_truoc'),
(11, 4, 1, 400000.00, 'dat_truoc'),
(11, 8, 1, 600000.00, 'dat_truoc'),
(12, 1, 1, 500000.00, 'dat_truoc'),
(12, 6, 1, 200000.00, 'dat_truoc');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dat_lich`
--

CREATE TABLE `dat_lich` (
  `ma_lich` int(11) NOT NULL,
  `ma_kh` int(11) NOT NULL,
  `ma_xe` int(11) NOT NULL,
  `ngay_hen` date NOT NULL,
  `gio_hen` time NOT NULL,
  `trang_thai` varchar(30) NOT NULL DEFAULT 'Cho xac nhan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dat_lich`
--

INSERT INTO `dat_lich` (`ma_lich`, `ma_kh`, `ma_xe`, `ngay_hen`, `gio_hen`, `trang_thai`) VALUES
(1, 1, 1, '2026-10-14', '17:00:00', 'Đã hủy'),
(2, 1, 1, '2026-10-15', '09:00:00', 'Đã hủy'),
(3, 2, 2, '2026-10-15', '08:00:00', 'Hoàn thành'),
(4, 2, 2, '2026-10-15', '13:00:00', 'Hoàn thành'),
(5, 2, 3, '2026-10-15', '10:00:00', 'Hoàn thành'),
(6, 2, 2, '2026-10-17', '14:00:00', 'Hoàn thành'),
(7, 3, 4, '2026-10-19', '12:00:00', 'Hoàn thành'),
(8, 3, 4, '2026-10-18', '16:00:00', 'Hoàn thành'),
(9, 1, 1, '2026-10-18', '17:00:00', 'Hoàn thành'),
(10, 1, 1, '2026-10-19', '08:00:00', 'Hoàn thành'),
(11, 1, 1, '2026-10-20', '11:00:00', 'Đã hủy'),
(12, 4, 5, '2026-10-19', '16:00:00', 'Hoàn thành');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dich_vu`
--

CREATE TABLE `dich_vu` (
  `ma_dv` int(11) NOT NULL,
  `ten_dv` varchar(100) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `don_gia` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dich_vu`
--

INSERT INTO `dich_vu` (`ma_dv`, `ten_dv`, `mo_ta`, `don_gia`) VALUES
(1, 'Thay dầu động cơ', 'Thay dầu động cơ định kỳ', 500000.00),
(2, 'Bảo dưỡng định kỳ', 'Kiểm tra và bảo dưỡng tổng quát ô tô', 1200000.00),
(3, 'Kiểm tra hệ thống phanh', 'Kiểm tra má phanh và hệ thống phanh ô tô', 300000.00),
(4, 'Vệ sinh điều hòa', 'Vệ sinh và kiểm tra hệ thống điều hòa', 400000.00),
(5, 'Thay lọc gió động cơ', 'Thay lọc gió động cơ để đảm bảo hiệu suất hoạt động', 250000.00),
(6, 'Rửa xe ô tô', 'Rửa và làm sạch ngoại thất xe ô tô', 200000.00),
(7, 'Đánh bóng sơn xe', 'Đánh bóng và làm mới bề mặt sơn xe', 800000.00),
(8, 'Nạp gas điều hòa', 'Kiểm tra và nạp gas điều hòa ô tô', 600000.00),
(9, 'Thay ắc quy', 'Kiểm tra và thay ắc quy ô tô', 1800000.00),
(10, 'Kiểm tra lốp xe', 'Kiểm tra áp suất và tình trạng lốp xe', 200000.00),
(11, 'Dọn dẹp chuyên sâu', 'Vệ sinh chuyên sâu từng chi tiết', 1500000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoa_don`
--

CREATE TABLE `hoa_don` (
  `ma_hd` int(11) NOT NULL,
  `ma_lich` int(11) NOT NULL,
  `ma_admin` int(11) DEFAULT NULL,
  `tong_tien` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ngay_lap` datetime DEFAULT current_timestamp(),
  `trang_thai` varchar(30) NOT NULL DEFAULT 'Chua thanh toan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hoa_don`
--

INSERT INTO `hoa_don` (`ma_hd`, `ma_lich`, `ma_admin`, `tong_tien`, `ngay_lap`, `trang_thai`) VALUES
(1, 3, 1, 2600000.00, '2026-10-14 20:26:11', 'Đã thanh toán'),
(2, 4, 1, 1000000.00, '2026-10-14 20:36:23', 'Đã thanh toán'),
(3, 5, 1, 2500000.00, '2026-10-14 21:05:27', 'Đã thanh toán'),
(4, 7, 1, 7050000.00, '2026-10-18 10:32:51', 'Đã thanh toán'),
(5, 6, 1, 2300000.00, '2026-10-18 10:37:35', 'Đã thanh toán'),
(6, 8, 1, 5650000.00, '2026-10-18 15:49:31', 'Đã thanh toán'),
(7, 9, 1, 3000000.00, '2026-10-18 16:06:04', 'Đã thanh toán'),
(8, 10, 1, 200000.00, '2026-10-18 16:11:57', 'Đã thanh toán'),
(9, 12, 1, 1100000.00, '2026-10-19 15:07:47', 'Chưa thanh toán');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khach_hang`
--

CREATE TABLE `khach_hang` (
  `ma_kh` int(11) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `sdt` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mat_khau` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khach_hang`
--

INSERT INTO `khach_hang` (`ma_kh`, `ho_ten`, `sdt`, `email`, `mat_khau`) VALUES
(1, 'Trương Nguyên', '0987654321', 'nguyenbocon11@gmail.com', '$2y$10$txbMrXoGtrlMg8V04tib2.IKvPNpz5VJRjXTHMs8KuUBsSCduyz1m'),
(2, 'Dũng Cris', '0887654321', 'dungcris11@gmail.com', '$2y$10$Awr1i8MGdpzT.9C9csx3i.LLO3dTIfa3CxzMOipyn1lbL9u/JZeHi'),
(3, 'Alex', '0123456789', 'alex11@gmail.com', '$2y$10$6TO.h6dZwmjwXc1Fb1sB8ufZ372fFN58yrC5lyZ1wgKuj1zXtlG7e'),
(4, 'Tiến Dũng', '0921531420', 'tiendung11@gmail.com', '$2y$10$2PjHTHWVOvzJBp0vSFb3g.7qj59VgrCDDaH5Bxkml16WKRMo3vEqq');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phat_sinh_tu_nhap`
--

CREATE TABLE `phat_sinh_tu_nhap` (
  `id` int(11) NOT NULL,
  `ma_lich` int(11) NOT NULL,
  `ten_khoan` varchar(255) NOT NULL,
  `loai` enum('ngoai_danh_muc','chenh_lech') NOT NULL,
  `so_luong` int(11) NOT NULL DEFAULT 1,
  `don_gia` decimal(15,2) NOT NULL DEFAULT 0.00,
  `ghi_chu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phat_sinh_tu_nhap`
--

INSERT INTO `phat_sinh_tu_nhap` (`id`, `ma_lich`, `ten_khoan`, `loai`, `so_luong`, `don_gia`, `ghi_chu`) VALUES
(1, 6, 'dán decal đổi màu', 'ngoai_danh_muc', 1, 800000.00, 'Khách đồng ý tại gara'),
(2, 8, 'thay 4 lốp drif', 'ngoai_danh_muc', 1, 4000000.00, 'Khách đồng ý tại gara'),
(3, 12, 'Nâng cấp dầu turbo', 'chenh_lech', 1, 400000.00, 'Khách yêu cầu nâng cấp');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `xe`
--

CREATE TABLE `xe` (
  `ma_xe` int(11) NOT NULL,
  `ma_kh` int(11) NOT NULL,
  `bien_so` varchar(20) NOT NULL,
  `hang_xe` varchar(50) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `nam_sx` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `xe`
--

INSERT INTO `xe` (`ma_xe`, `ma_kh`, `bien_so`, `hang_xe`, `model`, `nam_sx`) VALUES
(1, 1, '30H-11111', 'Lexus', 'NX350', '2026'),
(2, 2, '30H-55555', 'Lexus', 'RX350 LUXURY', '2026'),
(3, 2, '30H-59555', 'Lexus', 'RX500h', '2026'),
(4, 3, '30A-4953', 'Honda', 'Civic', '2006'),
(5, 4, '30C-56565', 'BMW', 'XM', '2026');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`ma_admin`),
  ADD UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`);

--
-- Chỉ mục cho bảng `ct_dat_lich`
--
ALTER TABLE `ct_dat_lich`
  ADD PRIMARY KEY (`ma_lich`,`ma_dv`),
  ADD KEY `ma_dv` (`ma_dv`);

--
-- Chỉ mục cho bảng `dat_lich`
--
ALTER TABLE `dat_lich`
  ADD PRIMARY KEY (`ma_lich`),
  ADD KEY `ma_kh` (`ma_kh`),
  ADD KEY `ma_xe` (`ma_xe`);

--
-- Chỉ mục cho bảng `dich_vu`
--
ALTER TABLE `dich_vu`
  ADD PRIMARY KEY (`ma_dv`);

--
-- Chỉ mục cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD PRIMARY KEY (`ma_hd`),
  ADD UNIQUE KEY `ma_lich` (`ma_lich`),
  ADD KEY `fk_hoa_don_admin` (`ma_admin`);

--
-- Chỉ mục cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  ADD PRIMARY KEY (`ma_kh`);

--
-- Chỉ mục cho bảng `phat_sinh_tu_nhap`
--
ALTER TABLE `phat_sinh_tu_nhap`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ma_lich` (`ma_lich`);

--
-- Chỉ mục cho bảng `xe`
--
ALTER TABLE `xe`
  ADD PRIMARY KEY (`ma_xe`),
  ADD UNIQUE KEY `bien_so` (`bien_so`),
  ADD KEY `ma_kh` (`ma_kh`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admin`
--
ALTER TABLE `admin`
  MODIFY `ma_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `dat_lich`
--
ALTER TABLE `dat_lich`
  MODIFY `ma_lich` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `dich_vu`
--
ALTER TABLE `dich_vu`
  MODIFY `ma_dv` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  MODIFY `ma_hd` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  MODIFY `ma_kh` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `phat_sinh_tu_nhap`
--
ALTER TABLE `phat_sinh_tu_nhap`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `xe`
--
ALTER TABLE `xe`
  MODIFY `ma_xe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `ct_dat_lich`
--
ALTER TABLE `ct_dat_lich`
  ADD CONSTRAINT `ct_dat_lich_ibfk_1` FOREIGN KEY (`ma_lich`) REFERENCES `dat_lich` (`ma_lich`),
  ADD CONSTRAINT `ct_dat_lich_ibfk_2` FOREIGN KEY (`ma_dv`) REFERENCES `dich_vu` (`ma_dv`);

--
-- Các ràng buộc cho bảng `dat_lich`
--
ALTER TABLE `dat_lich`
  ADD CONSTRAINT `dat_lich_ibfk_1` FOREIGN KEY (`ma_kh`) REFERENCES `khach_hang` (`ma_kh`),
  ADD CONSTRAINT `dat_lich_ibfk_2` FOREIGN KEY (`ma_xe`) REFERENCES `xe` (`ma_xe`);

--
-- Các ràng buộc cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD CONSTRAINT `fk_hoa_don_admin` FOREIGN KEY (`ma_admin`) REFERENCES `admin` (`ma_admin`),
  ADD CONSTRAINT `hoa_don_ibfk_1` FOREIGN KEY (`ma_lich`) REFERENCES `dat_lich` (`ma_lich`);

--
-- Các ràng buộc cho bảng `xe`
--
ALTER TABLE `xe`
  ADD CONSTRAINT `xe_ibfk_1` FOREIGN KEY (`ma_kh`) REFERENCES `khach_hang` (`ma_kh`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
