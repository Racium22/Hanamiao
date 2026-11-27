# Xây dựng phần mềm quản lý tiệm hoa - Hanamiao

Hệ thống web quản lý tiệm hoa **Hanamiao** được xây dựng bằng **PHP thuần + MySQL (PDO)** trên nền tảng XAMPP, bao gồm **Cửa hàng trực tuyến (Storefront)** cho khách đặt hoa và **Hệ thống quản trị (Admin)** độc lập cho chủ tiệm / nhân viên vận hành kho hoa tươi, công thức bó hoa, lịch sử giá và đơn hàng.

> **Dành cho người mới tham gia dự án:** Hãy đọc lần lượt:
> 1. Tài liệu này (`README.md`) để nắm tổng quan & dựng lại môi trường chạy trong 5 phút.
> 2. [QUY_TAC_DU_AN.md](QUY_TAC_DU_AN.md) để nắm cây thư mục, quy tắc code, bảo mật PDO và chuẩn UI/UX.
> 3. [KE_HOACH_DU_AN.md](KE_HOACH_DU_AN.md) để xem tiến độ, các giai đoạn (Phase/Wave) và nhận đầu việc trong checklist.
4. Aihihi

---

## 1. Tính năng & Đối tượng sử dụng

### 1.1. Khách hàng (Storefront — `http://localhost/Hanamiao/`)
- Duyệt danh sách bó hoa, giỏ hoa theo danh mục (`LoaiSanPham`) và thanh menu đa cấp động (`Menu`).
- Xem chi tiết từng sản phẩm: hình ảnh, mô tả, **giá bán hiệu lực theo thời gian** (`LichSuGia`), **thành phần số bông hoa** (`ThanhPhanBo`) và khả năng đáp ứng từ kho.
- Quản lý giỏ hàng & đặt đơn hàng trực tuyến với đầy đủ thông tin **Người đặt**, **Người nhận**, **Địa chỉ & Ngày giao**, **Ghi chú lời chúc**.
- Tra cứu trạng thái đơn hàng theo mã đơn và số điện thoại.

### 1.2. Nhân viên & Chủ tiệm (Admin — `http://localhost/Hanamiao/admin/`)
- **Quản lý kho nguyên liệu hoa tươi:** `LoaiHoa`, `Hoa` (theo dõi `SoLuongTon`), `NhaCungCap`.
- **Nhập kho & Hao hụt:** Lập `PhieuNhap` (cộng tồn kho) và ghi nhận `HaoHut` hoa héo/hỏng (trừ tồn kho an toàn bằng Transaction).
- **Quản lý thành phẩm & Định giá:** `LoaiSanPham`, `SanPham`, thiết lập công thức `ThanhPhanBo` (1 bó gồm những hoa gì, bao nhiêu bông) và `LichSuGia` theo khoảng ngày.
- **Xử lý đơn hàng:** Duyệt và cập nhật `TrangThaiDon`; tự động kiểm tra và trừ `Hoa.SoLuongTon` theo `ThanhPhanBo` khi xác nhận chuẩn bị đơn (và hoàn kho nếu hủy đơn đã trừ).
- **Quản trị hệ thống:** Quản lý tài khoản/phân quyền (`NguoiDung`), cấu trúc `Menu` đa cấp và Dashboard cảnh báo tồn kho thấp.

---

## 2. Công nghệ & Yêu cầu Môi trường

- **Web Server & Runtime:** XAMPP (Apache + PHP 8.0 trở lên, bật extension `pdo_mysql`).
- **Cơ sở dữ liệu:** MySQL / MariaDB (quản trị qua phpMyAdmin), bảng mã `utf8mb4_unicode_ci`.
- **Frontend:** HTML5, CSS3 thuần (CSS Variables, Flexbox, CSS Grid, Media Query), JavaScript thuần.
- **Kiến trúc:**
  - Front-Controller (`index.php` và `admin/index.php`) định tuyến qua `?page=...` dùng whitelist an toàn.
  - Mô hình xử lý form **POST – Redirect – GET (PRG)** qua thư mục `actions/`.
  - Giao diện Storefront và Admin **tách biệt CSS độc lập**.

---

## 3. Cấu trúc Cơ sở dữ liệu (`hanamiao_db` — 15 bảng)

Thiết kế chuẩn theo [`CSDLHanamiao.docx`](file:///e:/NAM4_HK1/Cong_nghe_web/tailieu/CSDLHanamiao.docx), chia thành 4 nhóm bảng liên kết chặt chẽ:

1. **Hệ thống & Điều hướng:** `NguoiDung`, `Menu` (cấu trúc cây cha – con qua `ID_Cha`).
2. **Kho hoa nguyên liệu:** `NhaCungCap`, `LoaiHoa`, `Hoa`, `PhieuNhap`, `ChiTietPhieuNhap`, `HaoHut`, `ChiTietHaoHut`.
3. **Sản phẩm & Định giá:** `LoaiSanPham`, `SanPham`, `ThanhPhanBo` (liên kết `SanPham` – `Hoa`), `LichSuGia` (giá theo `NgayBatDau` – `NgayKetThuc`).
4. **Bán hàng:** `DonHang`, `ChiTietDonHang`.

```text
NhaCungCap ──▶ PhieuNhap ──▶ ChiTietPhieuNhap ──(+)──▶ Hoa.SoLuongTon
NguoiDung  ──▶ HaoHut    ──▶ ChiTietHaoHut    ──(-)──▶ Hoa.SoLuongTon
                                                          ▲
SanPham ◀── LichSuGia                                     │ (-) theo SoLuong * SoBong
   │                                                      │
   ├──▶ ThanhPhanBo ──────────────────────────────────────┘
   └──▶ ChiTietDonHang ◀── DonHang
```

---

## 4. Hướng dẫn Dựng lại Dự án (Setup Guide)

Thực hiện đúng 5 bước sau để chạy dự án trên máy mới:

### Bước 1: Đặt mã nguồn đúng thư mục XAMPP
Sao chép toàn bộ thư mục `Hanamiao` vào đường dẫn:
```text
C:\xampp\htdocs\Hanamiao
```
*(Nếu cài XAMPP ở ổ đĩa khác như `D:\xampp`, đặt vào `D:\xampp\htdocs\Hanamiao`).*

### Bước 2: Khởi động Apache và MySQL
1. Mở **XAMPP Control Panel**.
2. Nhấn **Start** cho cả **Apache** và **MySQL**.
3. Đảm bảo phần mở rộng `pdo_mysql` đã được bật trong `php.ini` (mặc định XAMPP đã bật sẵn).

### Bước 3: Khởi tạo & Import Cơ sở dữ liệu
1. Mở trình duyệt và truy cập: `http://localhost/phpmyadmin`
2. Tạo hoặc chọn tab **Import**, chọn tệp `database/hanamiao_schema.sql` -> nhấn **Go** để tạo CSDL `hanamiao_db` cùng 15 bảng.
3. Tiếp tục **Import** tệp `database/hanamiao_seed.sql` để nạp dữ liệu mẫu (hoa, sản phẩm, giá bán, menu, tài khoản quản trị).
4. Kiểm tra trong `hanamiao_db` thấy đủ 15 bảng và dữ liệu mẫu ở bảng `Hoa`, `SanPham`, `NguoiDung`, `Menu`.

### Bước 4: Kiểm tra cấu hình kết nối
Mở tệp `config/database.php` và đối chiếu thông số máy cục bộ:
```php
$host     = '127.0.0.1';
$port     = '3306';
$database = 'hanamiao_db';
$username = 'root';
$password = ''; // Điền mật khẩu nếu MySQL máy bạn có đặt mật khẩu
```

### Bước 5: Chạy thử trên trình duyệt
- **Trang khách hàng (Storefront):** `http://localhost/Hanamiao/` *(nếu XAMPP đổi cổng Apache như cổng `8001`, truy cập `http://localhost:8001/Hanamiao/`)*
- **Trang quản trị (Admin):** `http://localhost/Hanamiao/admin/` *(hoặc `http://localhost:8001/Hanamiao/admin/`)*
  - Tài khoản mẫu trong `database/hanamiao_seed.sql`:
    - Quản trị viên: `admin` / `admin123`
    - Nhân viên: `nhanvien` / `nv123456`

---

## 5. Quy trình Dành cho Người mới Tham gia Phát triển

1. **Không sửa code bừa bãi:** Trước khi làm tính năng mới, kiểm tra mục tương ứng trong [KE_HOACH_DU_AN.md](KE_HOACH_DU_AN.md) và tuân thủ cấu trúc thư mục tại [QUY_TAC_DU_AN.md](QUY_TAC_DU_AN.md).
2. **Đảm bảo an toàn mã nguồn:**
   - Mọi câu SQL có tham số phải dùng `prepare()` + `execute()` (PDO).
   - Mọi dữ liệu in ra HTML phải đi qua `h(...)` (`htmlspecialchars`).
   - Mọi thao tác làm thay đổi `Hoa.SoLuongTon` hoặc `LichSuGia` phải bọc trong Transaction (`beginTransaction()` / `commit()` / `rollBack()`).
   - Giữ CSS Storefront (`css/style.css`) và Admin (`admin/css/style.css`) tách biệt.
3. **Kiểm tra trước khi đánh dấu hoàn thành:**
   - Chạy kiểm tra cú pháp: `php -l <tên_file.php>`.
   - Kiểm tra trực tiếp trên `http://localhost/Hanamiao/` ở cả màn hình desktop và mobile (`375px`).

---

## 6. Xử lý Lỗi Thường gặp (Troubleshooting)

| Hiện tượng | Nguyên nhân phổ biến | Cách khắc phục |
| :--- | :--- | :--- |
| `could not find driver` | Chưa bật PDO MySQL trong PHP | Mở `php.ini` trong XAMPP, bỏ dấu `;` trước `extension=pdo_mysql`, khởi động lại Apache. |
| `Access denied for user` | Sai username/password MySQL | Kiểm tra lại thông số trong `config/database.php`. |
| `Unknown database 'hanamiao_db'` | Chưa tạo hoặc import nhầm tên CSDL | Mở phpMyAdmin, import lại `database/hanamiao_schema.sql`. |
| `strict_types must be the very first statement` | Có khoảng trắng hoặc UTF-8 BOM trước `<?php` | Lưu file dạng **UTF-8 without BOM**, xóa mọi ký tự trước `<?php`. |
| Không thấy giao diện / mất CSS | Sai đường dẫn tương đối hoặc cache trình duyệt | Kiểm tra đường dẫn `css/style.css` / `admin/css/style.css` và nhấn `Ctrl + F5`. |
| Mở trang chỉ thấy mã PHP thô | Mở file trực tiếp bằng `file://` | Phải truy cập qua `http://localhost/Hanamiao/` khi Apache đang chạy. |
| Hai thanh cuộn Admin không hoạt động | Nội dung chưa tràn hoặc thiếu `min-height: 0` | Kiểm tra `.admin-workspace` và `.admin-sidebar, .admin-main` trong `admin/css/style.css`. |

