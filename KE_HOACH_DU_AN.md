# Kế hoạch Triển khai & Checklist Dự án — Hanamiao

**Tên dự án:** Xây dựng phần mềm quản lý tiệm hoa - Hanamiao  
**Phương pháp quản lý:** GSD (Phase & Wave Execution) + Spec-Driven Development  
**Tài liệu quy chuẩn đi kèm:** [QUY_TAC_DU_AN.md](QUY_TAC_DU_AN.md) | [README.md](README.md)

---

## 1. Phạm vi & Bảng Yêu cầu Nghiệp vụ (Requirements)

### Trong phạm vi (In-Scope)
- **CSDL:** Đầy đủ 15 bảng theo [`CSDLHanamiao.docx`](file:///e:/NAM4_HK1/Cong_nghe_web/tailieu/CSDLHanamiao.docx) trên MySQL (`hanamiao_db`).
- **Storefront (Khách hàng):** Xem trang chủ, duyệt/lọc sản phẩm theo loại & menu đa cấp, xem chi tiết thành phần bó hoa, giỏ hàng, đặt đơn hàng online (người đặt + người nhận + ngày giao), tra cứu đơn hàng.
- **Admin (Quản trị & Bán hàng độc lập):** Đăng nhập/phân quyền (`Quyen`), quản lý nhân sự, nhà cung cấp, loại hoa & kho hoa nguyên liệu, phiếu nhập kho, ghi nhận hao hụt, loại sản phẩm, sản phẩm & thành phần bó (`ThanhPhanBo`), lịch sử giá (`LichSuGia`), xử lý đơn hàng (`DonHang`), quản lý menu đa cấp (`Menu`), Dashboard tổng quan.

### Ngoài phạm vi giai đoạn hiện tại (Out-of-Scope)
- Thanh toán trực tuyến qua cổng thanh toán bên thứ ba (VNPay/MoMo API).
- Gửi email/SMS tự động hoặc ứng dụng di động riêng.

### Mã Yêu cầu Cốt lõi (`REQ-...`)
| Mã REQ | Mô tả | Phase |
| :--- | :--- | :--- |
| `REQ-DB-01` | Khởi tạo CSDL `hanamiao_db` với đủ 15 bảng, khóa ngoại, index và dữ liệu mẫu | Phase 1 |
| `REQ-CORE-01` | Kết nối PDO duy nhất, Router whitelist 404, Helper chống XSS, Auth & CSRF | Phase 1 |
| `REQ-MENU-01` | Hiển thị & quản lý `Menu` đa cấp đệ quy (`ID_Cha = 0`) | Phase 1 & 4 |
| `REQ-SHOP-01` | Giao diện Storefront: Trang chủ, danh sách/lọc sản phẩm theo giá hiện hành | Phase 2 |
| `REQ-SHOP-02` | Chi tiết sản phẩm kèm định mức `ThanhPhanBo` & kiểm tra khả năng đáp ứng kho | Phase 2 |
| `REQ-SHOP-03` | Giỏ hàng, đặt đơn hàng (`DonHang`, `ChiTietDonHang`) & tra cứu đơn | Phase 2 |
| `REQ-ADM-01` | Khung giao diện Admin độc lập (2 vùng cuộn độc lập) & Đăng nhập phân quyền | Phase 1 & 3 |
| `REQ-INV-01` | Quản lý `NhaCungCap`, `LoaiHoa`, `Hoa`, `PhieuNhap` (cộng tồn kho Transaction) | Phase 3 |
| `REQ-INV-02` | Ghi nhận `HaoHut` & `ChiTietHaoHut` (trừ tồn kho Transaction, chống âm kho) | Phase 3 |
| `REQ-PRD-01` | Quản lý `LoaiSanPham`, `SanPham`, `ThanhPhanBo`, `LichSuGia` | Phase 3 |
| `REQ-ORD-01` | Quản lý & xử lý trạng thái `DonHang` (trừ/hoàn tồn kho `Hoa` theo `ThanhPhanBo`) | Phase 4 |
| `REQ-NOTI-01` | Hệ thống thông báo & xử lý thông báo phía Admin (đơn mới, sắp/trễ giao, hoa hết/sắp hết, đánh dấu đã đọc, điều hướng xử lý) | Phase 4 |
| `REQ-DASH-01` | Dashboard thống kê & cảnh báo hoa sắp hết hàng | Phase 4 |

---

## 2. Bảng Theo dõi Tiến độ Tổng thể (State Tracking)

- **Giai đoạn hiện tại:** Phase 3 đã hoàn thành (`100%`) -> Sẵn sàng vào **Phase 4 — Wave 4.1** (Admin Đơn hàng trừ/hoàn kho Transaction, Menu đa cấp, Thông báo Admin, Dashboard & UAT).
- **Tổng số Phase:** 5 giai đoạn (Phase 0 -> Phase 4).

| Giai đoạn | Tên giai đoạn | Trạng thái | Tiến độ |
| :--- | :--- | :--- | :--- |
| **Phase 0** | Khởi tạo dự án, quy tắc, kế hoạch & cấu trúc thư mục | Hoàn thành | `100%` |
| **Phase 1** | CSDL 15 bảng (`hanamiao_db`), Core PDO, Auth & Layout khung | Hoàn thành | `100%` |
| **Phase 2** | Cửa hàng trực tuyến Storefront & Luồng Đặt hoa | Hoàn thành | `100%` |
| **Phase 3** | Admin Kho hoa, Nhập hàng, Hao hụt, Sản phẩm, BOM, Giá & Tài nguyên Local | Hoàn thành | `100%` |
| **Phase 4** | Admin Đơn hàng, Menu động, Thông báo Admin, Dashboard & Kiểm thử UAT | Chưa bắt đầu | `0%` |

---

## 3. Kế hoạch Chi tiết & Checklist Theo Phase / Wave

### PHASE 0: Khởi tạo Khung Dự án & Design System
**Mục tiêu:** Dựng sẵn cây thư mục chuẩn tại `C:\xampp\htdocs\Hanamiao`, bộ Design Tokens (`css/tokens.css`) và ảnh mặc định để mọi Wave sau bám đúng quy ước.

- [x] **Bước 0.1:** Khởi tạo thư mục `C:\xampp\htdocs\Hanamiao` và 3 file chuẩn hóa (`QUY_TAC_DU_AN.md`, `KE_HOACH_DU_AN.md`, `README.md`).
- [x] **Bước 0.2:** Tạo đầy đủ các thư mục theo cây chuẩn (`config/`, `functions/`, `components/`, `pages/`, `actions/`, `css/`, `js/`, `images/{banner,hoa,sanpham}`, `admin/{css,js,components,pages,actions}`, `database/`).
- [x] **Bước 0.3:** Xây dựng `css/tokens.css` (bộ màu thương hiệu Hanamiao, font `Inter`, thang spacing/radius, màu trạng thái semantic, không dark mode).
- [x] **Bước 0.4:** Bổ sung `images/placeholder.svg` (ảnh fallback chuẩn) và ảnh mẫu cho danh mục hoa/bó hoa.

**Tiêu chí nghiệm thu Phase 0:** Cây thư mục khớp 100% mục 2 của `QUY_TAC_DU_AN.md`; `tokens.css` định nghĩa đủ biến màu, chữ, viền, elevation.

---

### PHASE 1: Nền tảng CSDL (`hanamiao_db`), Core PHP & Khung Giao diện
**Mục tiêu:** CSDL chạy được trên MySQL, kết nối PDO ổn định, có hệ thống xác thực Admin và khung 2 giao diện (Storefront + Admin 2 thanh cuộn độc lập) hoạt động trên `localhost`.

#### Wave 1.1: Database Schema & Seed Data (`REQ-DB-01`)
- [x] Tạo `database/hanamiao_schema.sql`: `CREATE DATABASE hanamiao_db` (`utf8mb4_unicode_ci`) và đủ **15 bảng** (`NguoiDung`, `NhaCungCap`, `LoaiHoa`, `Hoa`, `LoaiSanPham`, `SanPham`, `ThanhPhanBo`, `LichSuGia`, `HaoHut`, `ChiTietHaoHut`, `PhieuNhap`, `ChiTietPhieuNhap`, `DonHang`, `ChiTietDonHang`, `Menu`).
- [x] Khai báo đầy đủ `PRIMARY KEY`, `FOREIGN KEY`, `UNIQUE` (`TenDangNhap`, `(ID_SP, ID_H)` trong `ThanhPhanBo`), `INDEX` (`ID_Cha` trong `Menu`, ngày trong `LichSuGia`, `TrangThaiDon` trong `DonHang`).
- [x] Tạo `database/hanamiao_seed.sql`: dữ liệu mẫu có ý nghĩa (tài khoản `admin` & `nhanvien` đã `password_hash()`, 2-3 nhà cung cấp, 4 loại hoa, 8+ loại hoa tươi có tồn kho, 3 loại sản phẩm, 6+ sản phẩm bó hoa kèm `ThanhPhanBo` & `LichSuGia`, 1 phiếu nhập, 1 phiếu hao hụt, 2 đơn hàng mẫu, cây `Menu` 2-3 cấp).
- [x] Import thử vào MySQL (`hanamiao_db`) và kiểm tra không có lỗi SQL/FK.

#### Wave 1.2: Core Config, Helpers & Auth (`REQ-CORE-01`)
- [x] Tạo `config/database.php` với hàm duy nhất `getConnection(): PDO` (bắt `PDOException`, `error_log`).
- [x] Tạo `config/auth.php`: khởi tạo session an toàn, `requireAdminLogin()`, `requireRole()`, `generateCsrfToken()`, `verifyCsrfToken()`.
- [x] Tạo `functions/helpers.php`: `h()`, `formatMoney()`, `formatDate()`, `setFlash()`, `getFlash()`, `getImageUrl()` (tự fallback về `images/placeholder.svg`).
- [x] Tạo `functions/menu_functions.php`: `layMenuCon()`, `inMenu()`, `inTuyChonCha()`.
- [x] Tạo `functions/business_functions.php`: `getCurrentPrice($pdo, $idSP)`, `getMaxAvailableQty($pdo, $idSP)`.

#### Wave 1.3: Khung Giao diện Storefront & Admin Độc lập (`REQ-ADM-01`, `REQ-MENU-01`)
- [x] Tạo `index.php` (router whitelist `$pages`, xử lý 404) + `components/{header,left,right,footer}.php` + `pages/home.php`.
- [x] Tạo `css/style.css` cho Storefront (Header, Navbar từ bảng `Menu`, layout 3 cột / responsive 375px).
- [x] Tạo `admin/login.php`, `admin/logout.php`, `admin/index.php` (router whitelist có `requireAdminLogin()` & phân quyền 403).
- [x] Tạo `admin/components/{header,sidebar,footer}.php`, `admin/pages/dashboard.php` và `admin/css/style.css` **độc lập**, áp dụng bố cục **2 vùng cuộn độc lập** (`100dvh`, `min-height: 0`, `overflow-y: auto`).

#### Wave 1.4: Kiểm thử Phase 1
- [x] Kiểm tra `php -l` toàn bộ 18 tệp PHP (0 lỗi).
- [x] Truy cập Storefront hiển thị sản phẩm, giá hiện hành, ảnh fallback & menu đa cấp từ MySQL; `?page=khong_ton_tai` trả HTTP 404.
- [x] Truy cập Admin chưa đăng nhập chuyển về `admin/login.php`; đăng nhập `admin` xem Dashboard thực từ MySQL; kiểm tra phân quyền `nhanvien` truy cập `users` trả HTTP 403; đăng xuất POST CSRF hợp lệ.

---

### PHASE 2: Cửa hàng Trực tuyến (Storefront) & Luồng Đặt hoa
**Mục tiêu:** Khách hàng có thể xem sản phẩm với giá đúng theo `LichSuGia`, xem thành phần hoa bên trong bó, thêm vào giỏ hàng, đặt hàng và tra cứu đơn hàng.

#### Wave 2.1: Thiết kế UI Brief & Dữ liệu hiển thị (`REQ-SHOP-01`, `REQ-SHOP-02`)
- [x] Chốt bố cục các màn Storefront (`home`, `products`, `product_detail`, `cart`, `checkout`, `order_lookup`).
- [x] Xây dựng `pages/home.php`: banner tiệm hoa Hanamiao, khối sản phẩm nổi bật (lấy `GiaBan` hiện hành từ `LichSuGia`), khối danh mục từ `LoaiSanPham`.
- [x] Xây dựng `pages/products.php`: lọc theo `ID_LSP`, tìm kiếm theo `TenSanPham` (prepared statement `LIKE`), lọc theo khoảng giá, xử lý **Empty State** khi không có sản phẩm khớp.
- [x] Xây dựng `pages/product_detail.php`: ảnh lớn (fallback nếu lỗi), giá hiện hành, mô tả, bảng **Thành phần bó hoa** (`ThanhPhanBo` JOIN `Hoa` -> tên hoa + số bông), hiển thị số lượng có thể làm từ kho (`getMaxAvailableQty()`), nút thêm vào giỏ.

#### Wave 2.2: Giỏ hàng, Đặt đơn & Tra cứu (`REQ-SHOP-03`)
- [x] Xây dựng `actions/cart_action.php` & `pages/cart.php`: thêm/tăng/giảm/xóa sản phẩm trong `$_SESSION['cart']`, cảnh báo nếu số lượng vượt mức kho đáp ứng.
- [x] Xây dựng `pages/checkout.php`: form tách rõ **Thông tin người đặt** (`TenKhach`, `SDTKhach`) và **Thông tin người nhận / giao hàng** (`TenNguoiNhan`, `SDTNguoiNhan`, `DiaChiGiao`, `NgayGiao`, `GhiChu`), tóm tắt đơn hàng.
- [x] Xây dựng `actions/order_save.php`: validate server-side (SĐT, ngày giao >= ngày hiện tại, giỏ hàng không rỗng), kiểm tra CSRF, **Transaction** chèn `DonHang` (`TrangThaiDon = 'ChoXacNhan'`, `ID_ND = NULL`) + `ChiTietDonHang` (tính `DonGia` từ `LichSuGia` tại server, tính lại `TongTien`), xóa giỏ hàng và chuyển hướng trang xác nhận thành công.
- [x] Xây dựng `pages/order_lookup.php`: tra cứu đơn theo `ID_DH` + `SDTKhach` (khớp cả hai mới hiển thị thông tin giao nhận & trạng thái).

#### Wave 2.3: Kiểm thử Phase 2
- [x] Test đặt đơn với giỏ hàng nhiều sản phẩm, kiểm tra `TongTien` và `DonGia` lưu trong DB khớp `LichSuGia`.
- [x] Test ca biên: sản phẩm chưa có giá trong `LichSuGia`, sản phẩm hết hoa nguyên liệu, tên sản phẩm dài 2 dòng, mobile 375px không cuộn ngang.

---

### PHASE 3: Admin — Quản lý Kho Hoa, Nhập hàng, Hao hụt, Sản phẩm & Nâng cấp Tài nguyên Local
**Mục tiêu:** Nhân viên và Quản trị viên vận hành trọn vẹn chuỗi cung ứng nội bộ (Nhà cung cấp -> Nhập hoa -> Tồn kho hoa -> Hao hụt -> Công thức bó hoa -> Lịch sử giá tự đóng giá cũ), đồng thời chuẩn hóa 100% tài nguyên chạy local không CDN, ảnh hoa thật và icon SVG/Lucide.

#### Wave 3.0: Chuẩn hóa Tài nguyên Local, Ảnh Hoa Thật & Icon UI/UX
- [x] Tải font `Inter` (`.woff2` đầy đủ tiếng Việt) về `css/fonts/` + `css/fonts.css` và gỡ toàn bộ liên kết CDN khỏi `<head>`.
- [x] Tải gói **Lucide Icons** (`lucide.min.js`) về `js/vendor/lucide.min.js` và gắn icon SVG chuyên nghiệp cạnh từng mục menu trái Admin (tuyệt đối không dùng emoji bàn phím).
- [x] Thay chữ "Giỏ hàng" trên header Storefront bằng biểu tượng SVG giỏ hàng kèm bộ đếm số lượng.
- [x] Thay toàn bộ ảnh hoa/bó hoa/banner vẽ SVG bằng **15 ảnh chụp hoa thật (`.jpg`)** lưu tại `images/hoa/`, `images/sanpham/`, `images/banner/` và đồng bộ đường dẫn vào MySQL + `hanamiao_seed.sql`.

#### Wave 3.1: Quản trị Hệ thống & Danh mục Cơ sở (`REQ-ADM-01`, `REQ-INV-01`)
- [x] `admin/pages/users.php` + `admin/actions/user_save.php`: CRUD `NguoiDung`, hash mật khẩu, khóa/mở khóa (`TrangThai`), chặn không cho tự khóa chính mình (chỉ `Quyen = 'admin'`).
- [x] `admin/pages/suppliers.php` + `admin/actions/supplier_save.php`: CRUD `NhaCungCap`, validate SĐT.
- [x] `admin/pages/flower_types.php` + `admin/actions/flower_type_save.php`: CRUD `LoaiHoa`.
- [x] `admin/pages/flowers.php` + `admin/actions/flower_save.php`: CRUD `Hoa` (upload ảnh thật an toàn vào `images/hoa/`, hiển thị `SoLuongTon` dạng `tabular-nums`, badge cảnh báo khi tồn thấp).

#### Wave 3.2: Nghiệp vụ Nhập kho & Hao hụt (`REQ-INV-01`, `REQ-INV-02`)
- [x] `admin/pages/imports.php` + `admin/actions/import_save.php`:
  - Form lập `PhieuNhap` chọn `NhaCungCap`, hỗ trợ thêm nhiều dòng `Hoa` (Số lượng + Giá nhập) bằng `admin/js/admin.js`.
  - Xử lý Transaction: Insert `PhieuNhap` (gắn `ID_ND` từ session) -> Insert `ChiTietPhieuNhap` (`ThanhTien = SoLuong * GiaNhap`) -> `UPDATE Hoa SET SoLuongTon = SoLuongTon + ?`.
  - Xem lại chi tiết từng phiếu nhập đã lập.
- [x] `admin/pages/wastage.php` + `admin/actions/wastage_save.php`:
  - Form lập `HaoHut` nhập `LyDo`, chọn nhiều dòng `Hoa` + `SoLuong` hỏng/héo.
  - Xử lý Transaction: Kiểm tra `SoLuongTon >= SoLuong` cho từng loại hoa (báo lỗi và `rollBack()` nếu vượt tồn) -> Insert `HaoHut` & `ChiTietHaoHut` -> `UPDATE Hoa SET SoLuongTon = SoLuongTon - ?`.

#### Wave 3.3: Quản lý Sản phẩm, Thành phần Bó & Lịch sử Giá (`REQ-PRD-01`)
- [x] `admin/pages/product_types.php` + `admin/actions/product_type_save.php`: CRUD `LoaiSanPham`.
- [x] `admin/pages/products.php` + `admin/actions/product_save.php`: CRUD `SanPham` (upload ảnh thật vào `images/sanpham/`, mô tả, trạng thái).
- [x] `admin/pages/product_bom.php` + `admin/actions/bom_save.php`: Gắn/sửa/xóa `ThanhPhanBo` (`ID_H`, `SoBong`) cho từng `SanPham`, chặn trùng loại hoa trong cùng sản phẩm.
- [x] `admin/pages/product_prices.php` + `admin/actions/price_save.php`: Nhập `NgayBatDau` cho đợt đầu (mặc định `NgayKetThuc = NULL`); khi tạo đợt giá mới tự lấy thời điểm hiện tại `NOW()` để đóng đợt giá cũ (`NgayKetThuc = NOW()`) và mở đợt giá mới (`NgayBatDau = NOW()`, `NgayKetThuc = NULL`) trong Transaction.

#### Wave 3.4: Kiểm thử Phase 3
- [x] Test kịch bản chuỗi tồn kho: Nhập +20 bông hồng (140 -> 160) -> Ghi hao hụt -5 (-> 155) -> Ghi hao hụt -9999 bị chặn & rollback.
- [x] Test đổi giá bán `SanPham`: giá cũ tự gán `NgayKetThuc = NOW()`, giá mới có hiệu lực ngay lập tức trên Storefront; kiểm tra 0 liên kết CDN & 45/45 file PHP `php -l` pass.

---

### PHASE 4: Admin Đơn hàng, Menu động, Thông báo Admin, Dashboard & Nghiệm thu UAT
**Mục tiêu:** Hoàn thiện vòng đời đơn hàng (trừ/hoàn kho theo `ThanhPhanBo`), quản trị `Menu` động, xây dựng trung tâm & luồng xử lý thông báo phía Admin, hoàn thiện Dashboard và kiểm tra chất lượng toàn dự án.

#### Wave 4.1: Quản lý Đơn hàng & Tồn kho Tự động (`REQ-ORD-01`)
- [ ] `admin/pages/orders.php`: Danh sách đơn hàng lọc theo `TrangThaiDon` (`ChoXacNhan`, `DangChuanBi`, `DangGiao`, `HoanThanh`, `DaHuy`), tìm theo tên/SĐT khách, trang chi tiết đơn hiển thị đầy đủ thông tin người đặt/người nhận và danh sách sản phẩm.
- [ ] `admin/actions/order_status_save.php`:
  - Luồng chuyển trạng thái hợp lệ (State Machine rõ ràng, không cho nhảy cóc trái quy tắc).
  - **Transaction trừ kho:** Khi chuyển từ `ChoXacNhan` -> `DangChuanBi`: khóa dòng `Hoa` (`SELECT ... FOR UPDATE`), tính tổng số bông cần dùng từ `ChiTietDonHang` x `ThanhPhanBo`; nếu thiếu hoa thì `rollBack()` và báo rõ tên loại hoa thiếu; nếu đủ thì trừ `Hoa.SoLuongTon` và cập nhật `ID_ND` người duyệt.
  - **Transaction hoàn kho:** Nếu hủy đơn từ trạng thái đã trừ kho (`DangChuanBi` -> `DaHuy`) thì cộng trả lại `SoLuongTon`.

#### Wave 4.2: Quản trị Menu Đa cấp & Dashboard (`REQ-MENU-01`, `REQ-DASH-01`)
- [ ] `admin/pages/menus.php` + `admin/actions/menu_save.php`: Thêm/sửa/xóa `Menu` (chọn `ID_Cha` bằng `inTuyChonCha()`, kiểm tra không cho xóa menu khi còn menu con, validate `url` nội bộ).
- [ ] `admin/pages/dashboard.php`:
  - 4 thẻ số liệu thực từ MySQL: Đơn chờ xác nhận, Doanh thu tháng/ngày, Sản phẩm đang bán, Hoa tồn thấp (<= ngưỡng cảnh báo, vd 20 bông).
  - Bảng "Đơn hàng mới nhất" & bảng "Cảnh báo tồn kho hoa thấp".

#### Wave 4.3: Hệ thống Thông báo & Xử lý Thông báo Phía Admin (`REQ-NOTI-01`)
- [ ] **Tổng hợp thông báo nghiệp vụ từ dữ liệu thực (`functions/business_functions.php`):**
  - Đơn hàng mới đang chờ xác nhận (`DonHang.TrangThaiDon = 'ChoXacNhan'`).
  - Đơn hàng sắp đến giờ giao trong ngày hoặc đã quá giờ giao nhưng chưa hoàn thành.
  - Cảnh báo kho hoa hết hàng (`Hoa.SoLuongTon = 0`) và sắp hết hàng (`SoLuongTon <= 20`).
  - Cảnh báo sản phẩm đang mở bán nhưng chưa có giá hiệu lực (`LichSuGia`) hoặc chưa có định mức bó (`ThanhPhanBo`).
- [ ] **Biểu tượng chuông & Panel thông báo trên Header Admin (`admin/components/header.php` + `admin/js/admin.js`):**
  - Nút chuông dùng icon Lucide `bell` (không dùng emoji bàn phím) kèm badge số lượng thông báo chưa đọc (`tabular-nums`).
  - Panel thông báo thả xuống theo chuẩn `overlay.md` (`--surface-overlay`, `--elevation-popover`): hiển thị danh sách thông báo phân loại theo mức độ, trạng thái đã đọc/chưa đọc, nút **"Đánh dấu tất cả đã đọc"** và liên kết **"Xem tất cả"**.
- [ ] **Trung tâm & Xử lý thông báo (`admin/pages/notifications.php` + `admin/actions/notification_action.php`):**
  - Màn hình quản lý thông báo có bộ lọc (`Tất cả`, `Chưa đọc`, `Đơn hàng`, `Kho hoa`, `Sản phẩm`).
  - Xử lý thông báo (POST + CSRF + PRG): đánh dấu đã đọc từng thông báo, đánh dấu tất cả đã đọc, hoặc chuyển thẳng tới màn hình nghiệp vụ để xử lý triệt để (duyệt đơn tại `orders`, nhập thêm hoa tại `imports`, cập nhật giá tại `product_prices`). Khi nghiệp vụ gốc đã xử lý xong (đơn đã duyệt / hoa đã nhập đủ), cảnh báo tương ứng tự động biến mất.
- [ ] **Chuẩn hóa thông báo phản hồi (Flash / Toast Admin):** nâng cấp thông báo thao tác thành công/lỗi/cảnh báo có nút đóng gọn và tự ẩn mượt với thông báo thành công.

#### Wave 4.4: Quality Gate, Code Review & UI Verification (Cổng cuối)
- [ ] **Syntax & Security Review (`code-reviewer`):**
  - Rà soát 100% file truy vấn dùng PDO `prepare()`/`execute()`, không nối chuỗi SQL.
  - Rà soát 100% điểm `echo` biến dữ liệu dùng `h()`.
  - Rà soát 100% file `admin/pages/` và `admin/actions/` kiểm tra session + quyền + CSRF.
  - Rà soát 100% thao tác tồn kho/đơn hàng/giá dùng `beginTransaction()` + `rollBack()`.
- [ ] **UI/UX Verification (`ui-ux`):**
  - Kiểm tra mọi màn hình ở kích thước Desktop (`1280px`) và Mobile (`375px`) không vỡ/tràn ngang.
  - Kiểm tra trạng thái rỗng (Empty State), lỗi form, ảnh fallback `placeholder.svg`, panel thông báo Admin & kiểm tra 0 CDN ngoài.
  - Kiểm tra 2 vùng cuộn độc lập trong Admin.
- [ ] **UAT End-to-End:** Chạy trọn luồng từ Nhập hoa -> Tạo sản phẩm + Định mức bó + Giá -> Khách đặt hàng -> Thông báo Admin xuất hiện -> Admin duyệt đơn (trừ kho, thông báo tự cập nhật) -> Ghi hao hụt -> Kiểm tra số liệu Dashboard.
- [ ] Cập nhật trạng thái hoàn thành toàn bộ Phase trong file này và xuất lại bản `.sql` chuẩn mới nhất vào `database/`.

