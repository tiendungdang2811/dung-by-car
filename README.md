# 🚗 DŨNG BY CAR – Website đặt lịch sửa chữa và bảo dưỡng ô tô

## 1. Giới thiệu

**DŨNG BY CAR** là website hỗ trợ khách hàng đặt lịch sửa chữa, bảo dưỡng ô tô trực tuyến và giúp quản trị viên quản lý hoạt động của gara.

Dự án được xây dựng phục vụ đồ án môn học, sử dụng PHP kết hợp cơ sở dữ liệu MySQL.

## 2. Công nghệ sử dụng

- **Frontend:** HTML, CSS
- **Backend:** PHP thuần (không sử dụng framework)
- **Cơ sở dữ liệu:** MySQL
- **Môi trường chạy:** XAMPP
- **Công cụ phát triển:** Visual Studio Code, phpMyAdmin
- **Quản lý mã nguồn:** GitHub

## 3. Chức năng chính

### Khách hàng
- Xem trang chủ, danh sách dịch vụ và giá.
- Đăng ký, đăng nhập tài khoản.
- Đặt lịch sửa chữa, bảo dưỡng ô tô.
- Xem và hủy lịch hẹn.
- Xem thông tin liên hệ, gọi điện và mở Google Maps.

### Quản trị viên (Admin)
- Đăng nhập, đăng xuất và đổi mật khẩu.
- Xem thống kê tổng quan và doanh thu.
- Quản lý lịch hẹn: xác nhận, hoàn thành, hủy.
- Quản lý dịch vụ: thêm, sửa, xóa theo điều kiện.
- Quản lý khách hàng: thêm, sửa, xóa.
- Quản lý hóa đơn và trạng thái thanh toán.
- Quản lý dịch vụ phát sinh và cập nhật tổng tiền.

## 4. Cấu trúc thư mục

```text
gara_oto/
├── admin/                  # Trang quản trị
├── assets/
│   ├── css/                # Giao diện CSS
│   └── images/             # Hình ảnh website
│       └── icons/          # Biểu tượng giao diện
├── auth/                   # Đăng ký, đăng nhập
├── config/
│   └── database.php        # Kết nối MySQL
├── dat_lich/               # Chức năng đặt lịch
├── database/
│   └── gara_db.sql         # Cơ sở dữ liệu
├── index.php               # Trang chủ
└── README.md               # Hướng dẫn dự án
```

## 5. Hướng dẫn cài đặt

**Bước 1:** Cài đặt [XAMPP](https://www.apachefriends.org/).

**Bước 2:** Tải mã nguồn từ GitHub:

https://github.com/tiendungdang2811/dung-by-car

Chọn **Code → Download ZIP**, giải nén và đặt thư mục dự án tại:

`C:\xampp\htdocs\gara_oto`

**Bước 3:** Mở XAMPP, khởi động **Apache** và **MySQL**.

**Bước 4:** Truy cập http://localhost/phpmyadmin, tạo cơ sở dữ liệu tên `gara_db` và import file `database/gara_db.sql`.

**Bước 5:** Kiểm tra thông tin kết nối trong `config/database.php`. Với XAMPP mặc định, MySQL sử dụng tài khoản `root` và mật khẩu để trống.

**Bước 6:** Truy cập website:

http://localhost/gara_oto/

## 6. Tài khoản quản trị viên

Thông tin đăng nhập Admin để giảng viên kiểm tra đồ án:

- **Trang đăng nhập:** http://localhost/gara_oto/admin/login.php
- **Tên đăng nhập:** Đặng Tiến Dũng
- **Mật khẩu:** Gara12345@

Tài khoản sử dụng cho mục đích kiểm tra đồ án trên môi trường localhost.

## 7. Thông tin tác giả

- **Họ và tên:** Đặng Tiến Dũng
- **Tên đề tài:** Xây dựng website đặt lịch sửa chữa và bảo dưỡng ô tô
- **Tên website:** DŨNG BY CAR
- **Mục đích:** Đồ án môn học

---

**© 2026 DŨNG BY CAR – Website đặt lịch sửa chữa và bảo dưỡng ô tô.**
