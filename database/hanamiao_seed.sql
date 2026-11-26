-- ============================================================================
-- FILE: database/hanamiao_seed.sql
-- Dữ liệu mẫu khởi đầu cho 15 bảng của dự án Hanamiao
-- Tài khoản mẫu (đã băm bằng password_hash):
--   1. Quản trị:  TenDangNhap = admin     | MatKhau = admin123
--   2. Nhân viên: TenDangNhap = nhanvien  | MatKhau = nv123456
-- ============================================================================

USE `hanamiao_db`;
SET NAMES utf8mb4;

-- 1. NguoiDung
INSERT INTO `NguoiDung` (`ID_ND`, `TenND`, `TenDangNhap`, `MatKhau`, `Quyen`, `TrangThai`) VALUES
(1, 'Nguyễn Minh Anh', 'admin', '$2y$10$X4aZQdB.PrJqNgxrDgEKY.lBTnMKI515rPTjwy1C8BDq//ZnODCni', 'admin', 1),
(2, 'Trần Thu Hà', 'nhanvien', '$2y$10$pxLNeeHTC.bJWg9TZ.uxt.RTRI1xRLE8wrkv6bJnF0ILpC8B1zRGW', 'nhanvien', 1)
ON DUPLICATE KEY UPDATE
    `TenND` = VALUES(`TenND`),
    `Quyen` = VALUES(`Quyen`),
    `TrangThai` = VALUES(`TrangThai`);

-- 2. NhaCungCap
INSERT INTO `NhaCungCap` (`ID_NCC`, `TenNCC`, `SDT`, `DiaChi`, `TrangThai`) VALUES
(1, 'Nông trại Hoa Đà Lạt Hasfarm', '02633824947', '450 Nguyên Tử Lực, Phường 8, TP. Đà Lạt', 1),
(2, 'Vườn Hoa Tươi Làng Vạn Thành', '0918456123', '40 Vạn Thành, Phường 5, TP. Đà Lạt', 1),
(3, 'Hoa Nhập Khẩu Phương Nam', '0909321654', '128 Hồ Thị Kỷ, Phường 1, Quận 10, TP.HCM', 1)
ON DUPLICATE KEY UPDATE
    `TenNCC` = VALUES(`TenNCC`),
    `SDT` = VALUES(`SDT`),
    `DiaChi` = VALUES(`DiaChi`);

-- 3. LoaiHoa
INSERT INTO `LoaiHoa` (`ID_LH`, `TenLH`, `TrangThai`) VALUES
(1, 'Hoa Hồng', 1),
(2, 'Hoa Hướng Dương', 1),
(3, 'Hoa Phụ & Lá Trang Trí', 1),
(4, 'Hoa Nhập Khẩu', 1)
ON DUPLICATE KEY UPDATE `TenLH` = VALUES(`TenLH`);

-- 4. Hoa (ảnh hoa thật lưu trong images/hoa/; gồm ca biên tồn thấp = 8, hết tồn = 0)
INSERT INTO `Hoa` (`ID_H`, `TenHoa`, `ID_LH`, `SoLuongTon`, `HinhAnh`, `TrangThai`) VALUES
(1, 'Hoa Hồng Đỏ Đà Lạt', 1, 140, 'images/hoa/hoa-hong-do.jpg', 1),
(2, 'Hoa Hồng Kem Dâu', 1, 85, 'images/hoa/hoa-hong-kem.jpg', 1),
(3, 'Hoa Hướng Dương Lớn', 2, 60, 'images/hoa/hoa-huong-duong.jpg', 1),
(4, 'Hoa Baby Trắng Hà Lan', 3, 95, 'images/hoa/hoa-baby-trang.jpg', 1),
(5, 'Lá Bạc Eucalyptus', 3, 120, 'images/hoa/la-bac-eucalyptus.jpg', 1),
(6, 'Hoa Tulip Hồng Phấn', 4, 8, 'images/hoa/hoa-tulip-hong.jpg', 1),
(7, 'Hoa Mẫu Đơn Phấn', 4, 0, 'images/hoa/hoa-mau-don.jpg', 1),
(8, 'Hoa Cẩm Tú Cầu Xanh', 4, 18, 'images/hoa/hoa-cam-tu-cau.jpg', 1)
ON DUPLICATE KEY UPDATE
    `TenHoa` = VALUES(`TenHoa`),
    `SoLuongTon` = VALUES(`SoLuongTon`),
    `HinhAnh` = VALUES(`HinhAnh`);

-- 5. LoaiSanPham
INSERT INTO `LoaiSanPham` (`ID_LSP`, `TenLoaiSanPham`, `TrangThai`) VALUES
(1, 'Bó Hoa Tươi', 1),
(2, 'Giỏ Hoa Chúc Mừng', 1),
(3, 'Hoa Cưới & Sự Kiện', 1)
ON DUPLICATE KEY UPDATE `TenLoaiSanPham` = VALUES(`TenLoaiSanPham`);

-- 6. SanPham (ảnh bó hoa thật lưu trong images/sanpham/)
INSERT INTO `SanPham` (`ID_SP`, `TenSanPham`, `ID_LSP`, `HinhAnh`, `MoTa`, `TrangThai`) VALUES
(1, 'Bó Hoa Hồng Đỏ Phối Baby Trắng Cao Cấp Kỷ Niệm Ngày Cưới', 1, 'images/sanpham/bo-hoa-hong-do.jpg', 'Kết hợp 15 bông hồng đỏ Đà Lạt tuyển chọn cùng hoa baby trắng và lá bạc thơm dịu, gói giấy lụa Hàn Quốc sang trọng.', 1),
(2, 'Giỏ Hoa Nắng Mai Hướng Dương', 2, 'images/sanpham/gio-hoa-huong-duong.jpg', 'Giỏ hoa hướng dương rực rỡ phối cùng baby trắng và lá phụ, thích hợp tặng khai trương, sinh nhật và tốt nghiệp.', 1),
(3, 'Bó Hoa Hồng Kem Dâu Ngọt Ngào', 1, 'images/sanpham/bo-hoa-hong-kem.jpg', 'Phong cách nhẹ nhàng với hoa hồng kem dâu thanh lịch, tặng người thương hoặc dịp 8/3, 20/10.', 1),
(4, 'Bó Hoa Tulip Hồng Tinh Khôi', 1, 'images/sanpham/bo-hoa-tulip-hong.jpg', 'Bó hoa tulip nhập khẩu màu hồng phấn thanh tao, số lượng có hạn theo đợt nhập mỗi sáng.', 1),
(5, 'Giỏ Hoa Cẩm Tú Cầu & Hồng Kem', 2, 'images/sanpham/gio-hoa-cam-tu-cau.jpg', 'Sắc xanh dịu nhẹ của cẩm tú cầu hòa quyện cùng hồng kem dâu tạo cảm giác thư thái.', 1),
(6, 'Bó Hoa Mẫu Đơn Phấn Cổ Điển', 3, 'images/sanpham/bo-hoa-mau-don.jpg', 'Thiết kế hoa cầm tay phong cách cổ điển bằng mẫu đơn phấn nhập khẩu.', 1)
ON DUPLICATE KEY UPDATE
    `TenSanPham` = VALUES(`TenSanPham`),
    `HinhAnh` = VALUES(`HinhAnh`),
    `MoTa` = VALUES(`MoTa`);

-- 7. ThanhPhanBo (Định mức số bông cho từng sản phẩm)
INSERT INTO `ThanhPhanBo` (`ID_TPB`, `ID_SP`, `ID_H`, `SoBong`) VALUES
(1, 1, 1, 15),
(2, 1, 4, 3),
(3, 1, 5, 4),
(4, 2, 3, 8),
(5, 2, 4, 4),
(6, 2, 5, 5),
(7, 3, 2, 12),
(8, 3, 4, 2),
(9, 4, 6, 5),
(10, 4, 5, 3),
(11, 5, 8, 3),
(12, 5, 2, 10),
(13, 6, 7, 5)
ON DUPLICATE KEY UPDATE `SoBong` = VALUES(`SoBong`);

-- 8. LichSuGia (có giá cũ đã đóng + giá hiện hành NgayKetThuc IS NULL)
INSERT INTO `LichSuGia` (`ID_GB`, `ID_SP`, `GiaBan`, `NgayBatDau`, `NgayKetThuc`) VALUES
(1, 1, 420000, '2026-01-01 00:00:00', '2026-09-30 23:59:59'),
(2, 1, 450000, '2026-10-01 00:00:00', NULL),
(3, 2, 520000, '2026-10-01 00:00:00', NULL),
(4, 3, 380000, '2026-10-01 00:00:00', NULL),
(5, 4, 650000, '2026-10-15 00:00:00', NULL),
(6, 5, 590000, '2026-10-15 00:00:00', NULL),
(7, 6, 890000, '2026-11-01 00:00:00', NULL)
ON DUPLICATE KEY UPDATE
    `GiaBan` = VALUES(`GiaBan`),
    `NgayKetThuc` = VALUES(`NgayKetThuc`);

-- 9 & 10. PhieuNhap & ChiTietPhieuNhap
INSERT INTO `PhieuNhap` (`ID_PN`, `ID_ND`, `ID_NCC`, `NgayLapPhieu`) VALUES
(1, 1, 1, '2026-11-24 06:30:00'),
(2, 2, 3, '2026-11-25 07:00:00')
ON DUPLICATE KEY UPDATE `NgayLapPhieu` = VALUES(`NgayLapPhieu`);

INSERT INTO `ChiTietPhieuNhap` (`ID_CTPN`, `ID_PN`, `ID_H`, `SoLuong`, `GiaNhap`, `ThanhTien`) VALUES
(1, 1, 1, 150, 9000, 1350000),
(2, 1, 3, 65, 14000, 910000),
(3, 1, 4, 100, 18000, 1800000),
(4, 2, 6, 10, 45000, 450000),
(5, 2, 8, 20, 32000, 640000)
ON DUPLICATE KEY UPDATE `ThanhTien` = VALUES(`ThanhTien`);

-- 11 & 12. HaoHut & ChiTietHaoHut
INSERT INTO `HaoHut` (`ID_HH`, `ID_ND`, `NgayGhiNhan`, `LyDo`) VALUES
(1, 2, '2026-11-25 18:00:00', 'Hoa bị dập cánh trong quá trình vận chuyển sáng')
ON DUPLICATE KEY UPDATE `LyDo` = VALUES(`LyDo`);

INSERT INTO `ChiTietHaoHut` (`ID_CTHH`, `ID_HH`, `ID_H`, `SoLuong`) VALUES
(1, 1, 1, 10),
(2, 1, 6, 2)
ON DUPLICATE KEY UPDATE `SoLuong` = VALUES(`SoLuong`);

-- 13 & 14. DonHang & ChiTietDonHang
INSERT INTO `DonHang` (`ID_DH`, `ID_ND`, `NgayLapDon`, `TenKhach`, `SDTKhach`, `DiaChiGiao`, `NgayGiao`, `TrangThaiDon`, `TenNguoiNhan`, `SDTNguoiNhan`, `GhiChu`, `TongTien`) VALUES
(1, 2, '2026-11-25 09:15:00', 'Lê Quốc Bảo', '0912345678', '25 Nguyễn Huệ, Quận 1, TP.HCM', '2026-11-26 10:00:00', 'HoanThanh', 'Phạm Ngọc Lan', '0987654321', 'Ghi thiệp: Chúc mừng kỷ niệm 2 năm!', 450000),
(2, NULL, '2026-11-26 14:20:00', 'Võ Hoàng Yến', '0938112233', '88 Pasteur, Quận 3, TP.HCM', '2026-11-27 09:00:00', 'ChoXacNhan', 'Công ty TNHH Ánh Dương', '02838223344', 'Giao trước 9h sáng giúp mình', 1040000)
ON DUPLICATE KEY UPDATE
    `TrangThaiDon` = VALUES(`TrangThaiDon`),
    `TongTien` = VALUES(`TongTien`);

INSERT INTO `ChiTietDonHang` (`ID_CTDH`, `ID_DH`, `ID_SP`, `SoLuong`, `DonGia`, `ThanhTien`) VALUES
(1, 1, 1, 1, 450000, 450000),
(2, 2, 2, 2, 520000, 1040000)
ON DUPLICATE KEY UPDATE `ThanhTien` = VALUES(`ThanhTien`);

-- 15. Menu đa cấp (3 cấp, kế thừa Lab 08)
INSERT INTO `Menu` (`ID`, `ID_Cha`, `TenMenu`, `MoTa`, `url`) VALUES
(1, 0, 'Trang chủ', 'Trang chủ tiệm hoa Hanamiao', 'index.php?page=home'),
(2, 0, 'Sản phẩm hoa', 'Danh mục tất cả sản phẩm hoa', 'index.php?page=products'),
(3, 2, 'Bó hoa tươi', 'Các mẫu bó hoa tặng sinh nhật, kỷ niệm', 'index.php?page=products&lsp=1'),
(4, 2, 'Giỏ hoa chúc mừng', 'Giỏ hoa khai trương, sự kiện', 'index.php?page=products&lsp=2'),
(5, 2, 'Hoa cưới & sự kiện', 'Hoa cầm tay cô dâu và trang trí', 'index.php?page=products&lsp=3'),
(6, 3, 'Hoa hồng tình yêu', 'Bó hoa chủ đạo từ hoa hồng', 'index.php?page=products&lsp=1&q=Hồng'),
(7, 0, 'Giỏ hàng', 'Xem giỏ hàng đang chọn', 'index.php?page=cart'),
(8, 0, 'Tra cứu đơn hàng', 'Kiểm tra trạng thái giao hoa', 'index.php?page=order_lookup')
ON DUPLICATE KEY UPDATE
    `ID_Cha` = VALUES(`ID_Cha`),
    `TenMenu` = VALUES(`TenMenu`),
    `MoTa` = VALUES(`MoTa`),
    `url` = VALUES(`url`);

