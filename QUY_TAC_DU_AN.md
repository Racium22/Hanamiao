# Quy tắc Kỹ thuật, Kiến trúc & Chuẩn UI/UX — Dự án Hanamiao

**Tên dự án:** Xây dựng phần mềm quản lý tiệm hoa - Hanamiao  
**Thư mục gốc:** `C:\xampp\htdocs\Hanamiao`  
**URL chạy cục bộ:** `http://localhost/Hanamiao/` (Storefront) | `http://localhost/Hanamiao/admin/` (Quản trị)

> Tài liệu này tổng hợp các quy chuẩn bắt buộc được rút ra từ hệ thống bài thực hành nền tảng (`Lab01`, `Lab02`, `Lab08`, `TX01`, `TX04`, `TX1_02`), thiết kế cơ sở dữ liệu [`CSDLHanamiao.docx`](file:///e:/NAM4_HK1/Cong_nghe_web/tailieu/CSDLHanamiao.docx), kết hợp quy trình **Spec-Driven Development**, **GSD Workflow**, **Code Reviewer Quality Gate** và **UI/UX Design System**. Mọi dòng mã viết trong dự án **bắt buộc** tuân thủ tài liệu này.

---

## 1. Công nghệ & Nguyên tắc nền tảng

| Thành phần | Quy chuẩn bắt buộc | Ghi chú |
| :--- | :--- | :--- |
| **Ngôn ngữ Backend** | PHP thuần (PHP 8.x, khuyến nghị `declare(strict_types=1);`) | Tuyệt đối không có khoảng trắng/BOM trước thẻ mở `<?php`. |
| **Cơ sở dữ liệu** | MySQL / MariaDB (XAMPP), engine `InnoDB` | Bảng mã bắt buộc `utf8mb4` / `utf8mb4_unicode_ci`. |
| **Truy cập dữ liệu** | **PDO** (`PDO::ERRMODE_EXCEPTION`, `PDO::FETCH_ASSOC`, `PDO::ATTR_EMULATE_PREPARES => false`) | Chỉ định nghĩa kết nối tại **01 nơi duy nhất**: `config/database.php` qua hàm `getConnection(): PDO`. |
| **Giao diện (Frontend)** | HTML5 ngữ nghĩa + CSS thuần (Variables, Flexbox, CSS Grid, Media Query) + JS thuần nhẹ | **Không** dùng Bootstrap hay framework CSS nặng; tuân thủ token tại `css/tokens.css`. |
| **Môi trường chạy** | Apache qua `http://localhost/Hanamiao/` | Cấm kiểm thử bằng đường dẫn `file://`. |

---

## 2. Cây thư mục chuẩn bắt buộc

Mọi thành viên và agent khi tạo/chỉnh sửa mã nguồn phải giữ đúng cấu trúc cây thư mục dưới đây. **Không** tạo file xử lý nghiệp vụ rời rạc ở thư mục gốc ngoài `index.php`.

```text
Hanamiao/
├── index.php                       # Bộ định tuyến (Front-Controller) khu vực Storefront (?page=...)
├── README.md                       # Tài liệu tổng quan & hướng dẫn dựng lại dự án
├── QUY_TAC_DU_AN.md                # File quy tắc kiến trúc, CSDL, bảo mật & UI/UX (tài liệu này)
├── KE_HOACH_DU_AN.md               # Kế hoạch chia Phase/Wave & Checklist triển khai
│
├── config/                         # Cấu hình hệ thống & xác thực dùng chung
│   ├── database.php                # Hàm getConnection(): PDO kết nối hanamiao_db
│   └── auth.php                    # Khởi tạo session, kiểm tra đăng nhập, phân quyền, CSRF token
│
├── functions/                      # Hàm xử lý dùng lại nhiều nơi (không xuất HTML trực tiếp trừ helper render)
│   ├── helpers.php                 # h() thoát XSS, formatMoney(), formatDate(), redirectWithFlash()
│   ├── menu_functions.php          # layMenuCon(), inMenu(), inTuyChonCha() (đệ quy đa cấp từ bảng Menu)
│   └── business_functions.php      # Hàm dùng chung: lấy giá hiện hành từ LichSuGia, tính sức chứa từ ThanhPhanBo
│
├── components/                     # Thành phần giao diện dùng chung của Storefront
│   ├── header.php                  # Mở HTML, nạp tokens.css + style.css, thanh trên, navbar từ Menu
│   ├── left.php                    # Sidebar trái: danh mục LoaiSanPham, menu đa cấp, bộ lọc giá
│   ├── right.php                   # Sidebar phải (tùy trang): tóm tắt giỏ hàng, thông báo/hỗ trợ
│   └── footer.php                  # Chân trang, thông tin tiệm hoa Hanamiao, đóng thẻ HTML
│
├── pages/                          # Các màn hình nội dung Storefront (được require bởi index.php)
│   ├── home.php                    # Trang chủ: banner, sản phẩm nổi bật, danh mục chính
│   ├── products.php                # Danh sách sản phẩm theo loại / từ khóa / khoảng giá
│   ├── product_detail.php          # Chi tiết sản phẩm, thành phần bó hoa (ThanhPhanBo), tình trạng đáp ứng
│   ├── cart.php                    # Giỏ hàng (lưu Session) & kiểm tra tồn kho khả dụng
│   ├── checkout.php                # Form đặt hàng (người đặt + người nhận + ngày giao + ghi chú)
│   └── order_lookup.php            # Tra cứu trạng thái đơn hàng theo Mã đơn & SĐT khách
│
├── actions/                        # Xử lý form POST phía Storefront (theo mô hình POST -> Redirect -> GET)
│   ├── cart_action.php             # Thêm/cập nhật/xóa sản phẩm trong giỏ hàng
│   └── order_save.php              # Lưu DonHang + ChiTietDonHang (Transaction, lấy giá từ LichSuGia)
│
├── css/                            # CSS riêng cho Storefront
│   ├── tokens.css                  # Design Tokens (màu, font Inter, bo góc, khoảng cách, trạng thái)
│   └── style.css                   # Bố cục Storefront (Flexbox/Grid, responsive)
│
├── js/                             # JavaScript thuần cho Storefront
│   └── app.js                      # Tương tác giỏ hàng, validation nhẹ, UI drawer/toast
│
├── images/                         # Tài nguyên hình ảnh (dùng đường dẫn tương đối images/...)
│   ├── placeholder.svg             # Ảnh mặc định khi sản phẩm/hoa chưa có ảnh hoặc ảnh lỗi
│   ├── banner/                     # Ảnh banner cửa hàng
│   ├── hoa/                        # Ảnh nguyên liệu hoa (bảng Hoa)
│   └── sanpham/                    # Ảnh bó hoa/giỏ hoa thành phẩm (bảng SanPham)
│
├── admin/                          # Khu vực Quản trị ĐỘC LẬP về giao diện & CSS
│   ├── index.php                   # Bộ định tuyến Admin (?page=...), bắt buộc kiểm tra session trước
│   ├── login.php                   # Trang đăng nhập Nhân viên / Quản trị
│   ├── logout.php                  # Hủy session đăng xuất an toàn
│   ├── css/
│   │   └── style.css               # CSS ĐỘC LẬP của Admin (không nạp css/style.css của Storefront)
│   ├── js/
│   │   └── admin.js                # JS xử lý động dòng nhập kho/hao hụt/thành phần bó, modal xác nhận
│   ├── components/
│   │   ├── header.php              # Đầu trang Admin, tên người dùng đang đăng nhập, nút đăng xuất
│   │   ├── sidebar.php             # Menu quản trị cố định/theo quyền (cuộn độc lập)
│   │   └── footer.php              # Chân trang Admin
│   ├── pages/                      # Các màn hình nghiệp vụ Admin
│   │   ├── dashboard.php           # Tổng quan: đơn mới, hoa sắp hết tồn, doanh thu, cảnh báo
│   │   ├── users.php               # Quản lý NguoiDung (thêm/sửa/khóa, phân quyền)
│   │   ├── suppliers.php           # Quản lý NhaCungCap
│   │   ├── flower_types.php        # Quản lý LoaiHoa
│   │   ├── flowers.php             # Quản lý Hoa & tồn kho hiện tại
│   │   ├── product_types.php       # Quản lý LoaiSanPham
│   │   ├── products.php            # Quản lý SanPham
│   │   ├── product_bom.php         # Thiết lập ThanhPhanBo (số bông mỗi hoa cho từng sản phẩm)
│   │   ├── product_prices.php      # Quản lý LichSuGia theo ngày bắt đầu/kết thúc
│   │   ├── imports.php             # Danh sách & lập PhieuNhap + ChiTietPhieuNhap
│   │   ├── wastage.php             # Danh sách & ghi nhận HaoHut + ChiTietHaoHut
│   │   ├── orders.php              # Quản lý DonHang, xem chi tiết, đổi TrangThaiDon
│   │   └── menus.php               # Quản lý Menu đa cấp (kế thừa Lab08)
│   └── actions/                    # Xử lý POST cho toàn bộ nghiệp vụ Admin (PRG pattern)
│       ├── user_save.php
│       ├── supplier_save.php
│       ├── flower_type_save.php
│       ├── flower_save.php
│       ├── product_type_save.php
│       ├── product_save.php
│       ├── bom_save.php
│       ├── price_save.php
│       ├── import_save.php
│       ├── wastage_save.php
│       ├── order_status_save.php
│       └── menu_save.php
│
└── database/                       # Tài nguyên CSDL MySQL
    ├── hanamiao_schema.sql         # Tạo CSDL hanamiao_db & 15 bảng + FK + Index
    └── hanamiao_seed.sql           # Dữ liệu mẫu đầy đủ cho 15 bảng để test & demo ngay
```

### Quy tắc tách biệt Admin và Storefront (rút ra từ Lab01, Lab02 & TX1_02)
1. `admin/` sử dụng `admin/index.php` và `admin/css/style.css` **độc lập hoàn toàn** với `css/style.css` và `components/` của Storefront (có thể dùng chung `css/tokens.css` cho biến màu/font cơ bản).
2. `admin/` **không** tạo file kết nối CSDL riêng; gọi lại `require_once __DIR__ . '/../config/database.php';` và `config/auth.php`.
3. Giao diện Admin dùng prefix class riêng (`.admin-app`, `.admin-header`, `.admin-workspace`, `.admin-sidebar`, `.admin-main`, `.admin-footer`) để tránh đụng độ selector.

---

## 3. Quy tắc Cơ sở dữ liệu & Nghiệp vụ (`CSDLHanamiao.docx`)

Cơ sở dữ liệu mang tên `hanamiao_db` gồm **đúng 15 bảng** theo `CSDLHanamiao.docx`. Giữ nguyên tên bảng và tên cột để đảm bảo khớp thiết kế gốc:

1. `NguoiDung (ID_ND, TenND, TenDangNhap, MatKhau, Quyen, TrangThai)`
2. `NhaCungCap (ID_NCC, TenNCC, SDT, DiaChi, TrangThai)`
3. `LoaiHoa (ID_LH, TenLH, TrangThai)`
4. `Hoa (ID_H, TenHoa, ID_LH, SoLuongTon, HinhAnh, TrangThai)`
5. `LoaiSanPham (ID_LSP, TenLoaiSanPham, TrangThai)`
6. `SanPham (ID_SP, TenSanPham, ID_LSP, HinhAnh, MoTa, TrangThai)`
7. `ThanhPhanBo (ID_TPB, ID_SP, ID_H, SoBong)`
8. `LichSuGia (ID_GB, ID_SP, GiaBan, NgayBatDau, NgayKetThuc)`
9. `HaoHut (ID_HH, ID_ND, NgayGhiNhan, LyDo)`
10. `ChiTietHaoHut (ID_CTHH, ID_HH, ID_H, SoLuong)`
11. `PhieuNhap (ID_PN, ID_ND, ID_NCC, NgayLapPhieu)`
12. `ChiTietPhieuNhap (ID_CTPN, ID_PN, ID_H, SoLuong, GiaNhap, ThanhTien)`
13. `DonHang (ID_DH, ID_ND, NgayLapDon, TenKhach, SDTKhach, DiaChiGiao, NgayGiao, TrangThaiDon, TenNguoiNhan, SDTNguoiNhan, GhiChu, TongTien)`
14. `ChiTietDonHang (ID_CTDH, ID_DH, ID_SP, SoLuong, DonGia, ThanhTien)`
15. `Menu (ID, ID_Cha, TenMenu, MoTa, url)`

### Quy tắc toàn vẹn nghiệp vụ bắt buộc (Critical Business Rules)
- **Mật khẩu (`NguoiDung.MatKhau`):** Bắt buộc mã hóa bằng `password_hash($password, PASSWORD_DEFAULT)` và kiểm tra bằng `password_verify()`. Không lưu mật khẩu thô.
- **Xóa mềm (`TrangThai`):** Các danh mục có `TrangThai` (`NguoiDung`, `NhaCungCap`, `LoaiHoa`, `Hoa`, `LoaiSanPham`, `SanPham`) ưu tiên khóa/ngừng kinh doanh (`TrangThai = 0`) thay vì `DELETE` cứng khi đã phát sinh dữ liệu liên quan.
- **Công thức bó (`ThanhPhanBo`):**
  - Ràng buộc `UNIQUE (ID_SP, ID_H)` và `SoBong > 0`.
  - Số sản phẩm tối đa làm được từ kho hiện tại: `MIN(FLOOR(Hoa.SoLuongTon / ThanhPhanBo.SoBong))`. Sản phẩm chưa có thành phần hoặc có thành phần thiếu tồn kho phải hiển thị trạng thái "Hết hàng / Liên hệ".
- **Giá bán (`LichSuGia`):**
  - Giá hiện hành của sản phẩm là dòng có `NgayBatDau <= NOW()` và (`NgayKetThuc IS NULL OR NgayKetThuc >= NOW()`), ưu tiên `ID_GB DESC LIMIT 1`.
  - Khi thêm mức giá mới trong Admin: tự động cập nhật `NgayKetThuc` cho mức giá đang mở trước đó trong cùng Transaction.
- **Đồng bộ tồn kho `Hoa.SoLuongTon` (BẮT BUỘC dùng Transaction `beginTransaction()` / `commit()` / `rollBack()`):**
  - **Nhập kho (`PhieuNhap`):** Cộng `ChiTietPhieuNhap.SoLuong` vào `Hoa.SoLuongTon`. Tính `ThanhTien = SoLuong * GiaNhap` ở server.
  - **Hao hụt (`HaoHut`):** Trừ `ChiTietHaoHut.SoLuong` khỏi `Hoa.SoLuongTon`. Phải kiểm tra `SoLuongTon >= SoLuong` trước khi trừ (không cho âm kho).
  - **Đơn hàng (`DonHang`):**
    - `ID_ND` có thể `NULL` khi khách tự đặt online; khi nhân viên xác nhận/lập đơn sẽ gắn `ID_ND`.
    - `DonGia` trong `ChiTietDonHang` phải truy vấn từ `LichSuGia` tại thời điểm đặt, **không** tin giá từ form client.
    - Khi đơn chuyển sang trạng thái xác nhận xuất kho (`DangChuanBi` / `HoanThanh` tùy cấu hình trừ kho lúc xác nhận): kiểm tra đủ nguyên liệu theo `ThanhPhanBo` và trừ `SoLuong * SoBong` vào `Hoa.SoLuongTon` (có cờ hoặc logic chuyển trạng thái chặt chẽ để tránh trừ 2 lần, và hoàn trả tồn kho nếu hủy đơn đã trừ kho).
- **Menu đa cấp (`Menu`):**
  - `ID_Cha = 0` là menu gốc.
  - Khi thêm/sửa menu phải kiểm tra `ID_Cha` hợp lệ (không chọn chính nó làm cha).
  - Validate `url` chỉ cho phép định dạng nội bộ an toàn (ví dụ `index.php?page=...`) hoặc để `NULL` (hiển thị dạng nhãn không link).

---

## 4. Quy chuẩn Code & Bảo mật (Code Quality Gate)

1. **Điều hướng Whitelist (Chống LFI):**
   - `index.php` và `admin/index.php` bắt buộc định nghĩa mảng `$pages = ['key' => 'pages/file.php', ...]`.
   - Kiểm tra `!is_string($page) || !isset($pages[$page])` -> trả về `http_response_code(404)` và trang thông báo 404.
2. **Chống SQL Injection 100%:**
   - Cấm tuyệt đối nối chuỗi SQL chứa biến đầu vào (`"SELECT ... WHERE id = $id"`).
   - Mọi truy vấn có tham số phải dùng `$stmt = $pdo->prepare($sql); $stmt->execute([...]);`.
3. **Chống XSS 100%:**
   - Mọi chuỗi xuất ra HTML phải bọc qua hàm `h($str)` (`htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8')`).
4. **Mô hình POST – Redirect – GET (PRG) & CSRF:**
   - Toàn bộ file trong `actions/` và `admin/actions/` chỉ chấp nhận `$_SERVER['REQUEST_METHOD'] === 'POST'`.
   - Kiểm tra CSRF token trên mọi form thêm/sửa/xóa/đổi trạng thái.
   - Sau khi xử lý xong luôn dùng `header('Location: ...'); exit;` kèm flash message trong session thay vì render HTML trực tiếp từ file action.
5. **Xử lý ngoại lệ & Tài nguyên:**
   - Mọi thao tác PDO đặt trong `try ... catch (PDOException $e)`.
   - Ghi chi tiết lỗi kỹ thuật bằng `error_log($e->getMessage())`, chỉ hiển thị thông báo tiếng Việt thân thiện cho người dùng.
6. **Quy ước đặt tên:**
   - **File PHP/CSS/JS:** `snake_case` chữ thường (ví dụ: `product_detail.php`, `flower_save.php`).
   - **Hàm PHP:** `camelCase` tiếng Anh hoặc tiếng Việt không dấu thống nhất theo ngữ cảnh (ví dụ: `getConnection()`, `formatMoney()`, `layMenuCon()`, `inMenu()`).
   - **Biến PHP:** `camelCase` (`$productList`, `$currentPrice`, `$idCha`).
   - **Class CSS:** `kebab-case` (`.main-layout`, `.product-card`, `.admin-sidebar`).

---

## 5. Quy chuẩn UI/UX & Thiết kế Giao diện

Kết hợp chuẩn bố cục từ `Lab01`/`Lab02`/`TX01` với bộ nguyên tắc từ skill `ui-ux`:

1. **Hệ thống Token (`css/tokens.css`) & Tài nguyên 100% Local (Không CDN):**
   - Dùng **01 font duy nhất**: `Inter` tải về chạy **local** tại `css/fonts/` + `css/fonts.css` (12 tệp `.woff2` đủ bảng mã tiếng Việt `ữ`, `ợ`, `ằ`), tuyệt đối **không** gọi Google Fonts CDN hay thư viện CDN ngoài vì dự án chạy offline/local trên XAMPP.
   - **Icon chuyên nghiệp (KHÔNG dùng emoji bàn phím):**
     - Sidebar & nút thao tác Admin dùng gói **Lucide Icons** tải local tại `js/vendor/lucide.min.js` (`<i data-lucide="...">`).
     - Nút giỏ hàng ở header Storefront dùng biểu tượng SVG giỏ hàng chuẩn (`cart-svg-icon`).
   - **Hình ảnh hoa thật:** Ảnh các loại hoa trong `images/hoa/`, bó hoa trong `images/sanpham/` và banner `images/banner/` phải dùng **ảnh chụp thật** (`.jpg`, `.png`, `.webp`); chỉ giữ `images/placeholder.svg` làm ảnh fallback khi sản phẩm/hoa chưa tải ảnh lên.
   - Màu nhấn thương hiệu Hanamiao định nghĩa tập trung tại `:root` (`--primary: #7a2e4d`, nền sáng `--background: #f4f4f6`, `--surface: #ffffff`, chữ `--foreground: #2c2c2c`, chữ phụ `--muted: #707070`, trạng thái `--success`, `--warning`, `--danger`).
   - Không rải mã màu hex tùy tiện vào từng file `.css` hay inline `style="..."`.
   - Đường viền mảnh `--border` / `--border-strong` theo phong cách **Flat (đường tóc phẳng)**; chỉ dùng bóng (`--elevation-popover`, `--elevation-modal`) cho lớp nổi (dropdown, modal, toast).
2. **Bố cục chuẩn 2 khu vực:**
   - **Storefront (khách hàng):** Header (logo, tìm kiếm, giỏ hàng) + Navbar (render từ bảng `Menu`) + Banner + Main Layout (`left` danh mục/lọc + `content` lưới card sản phẩm CSS Grid + `right` hỗ trợ/giỏ nhanh ở trang chủ/danh sách) + Footer. Trang chi tiết & checkout dùng bố cục rộng thoáng hơn (ẩn cột phải nếu cần).
   - **Admin (quản trị):** Áp dụng chuẩn **2 vùng cuộn độc lập** từ `Lab02`:
     - `.admin-app` cao `100dvh`, `display: flex; flex-direction: column;`
     - `.admin-header` & `.admin-footer` `flex-shrink: 0;` (không bị cuộn theo nội dung).
     - `.admin-workspace` `display: flex; flex: 1; min-height: 0;`
     - `.admin-sidebar` và `.admin-main` đều có `min-height: 0; overflow-y: auto; overflow-x: hidden; scrollbar-gutter: stable;`.
3. **Trạng thái & Ca biên (Edge Cases) bắt buộc xử lý ở mọi màn hình:**
   - Luôn có giao diện riêng cho: **Danh sách rỗng (Empty State)**, **Thông báo lỗi**, **Thông báo thành công**, **Hết hàng / Không đủ hoa tồn kho**, **Đang chọn (active)**.
   - Kiểm tra **ca biên**: tên sản phẩm/loại hoa dài 2 dòng, số tiền `999.999.000 đ` (bật `font-variant-numeric: tabular-nums`), tồn kho = `0`, ảnh thiếu/lỗi tự fallback về `images/placeholder.svg`.
   - **Responsive 375px:** Tuyệt đối không phát sinh thanh cuộn ngang toàn trang ở màn hình điện thoại (`375px`) và tablet (`768px`).
4. **Ngôn ngữ & Trải nghiệm:**
   - 100% tiếng Việt có dấu, rõ ràng, tự nhiên (dấu `*` đỏ cho trường bắt buộc, nút `Hủy` bên trái nút hành động chính, xác nhận trước khi khóa/hủy đơn/xóa).
   - Không tự đẻ thêm tính năng/khối thừa ngoài phạm vi từng màn hình đã đặc tả.

---

## 6. Quy trình làm việc bắt buộc khi code (Spec -> Wave -> Verify)

Trước khi viết code cho bất kỳ màn hình hoặc chức năng mới nào, **bắt buộc** thực hiện 4 bước:
1. **Kiểm tra ngữ cảnh & Spec:** Đối chiếu yêu cầu trong `KE_HOACH_DU_AN.md`, xác định In-Scope / Out-of-Scope và bảng dữ liệu liên quan.
2. **Lập File & DB Impact Map:** Liệt kê rõ các file tạo mới/sửa đổi và câu SQL/Transaction cần dùng.
3. **Thực thi tuần tự theo Wave:**
   - Wave 1: Schema / Helper / Query đọc dữ liệu.
   - Wave 2: Xử lý POST (`actions/`), validate server-side, Transaction.
   - Wave 3: Giao diện (`pages/`, `components/`), CSS theo `tokens.css`, xử lý trạng thái rỗng/lỗi/ca biên.
4. **Kiểm thử & Review Gate:**
   - Kiểm tra `php -l` toàn bộ file vừa tạo/sửa không có lỗi cú pháp.
   - Chạy thực tế trên `http://localhost/Hanamiao/` (kiểm thử luồng happy path + dữ liệu rỗng/sai + responsive 375px).
   - Soát bảo mật (SQLi, XSS, Session/Auth, CSRF, Transaction) và đánh dấu `[x]` vào `KE_HOACH_DU_AN.md`.

