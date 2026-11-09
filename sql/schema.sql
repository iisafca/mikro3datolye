-- Mikro 3D Atölye veritabanı şeması.
-- Not: admin.php yeni tabloları ve sütunları ilk girişte kendisi oluşturur; bu dosya belge ve sıfırdan kurulum içindir.

CREATE TABLE IF NOT EXISTS products(
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, category VARCHAR(80) NOT NULL DEFAULT 'Genel',
 price DECIMAL(10,2) NOT NULL DEFAULT 0, description TEXT, image VARCHAR(80), shopier_url VARCHAR(300),
 active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) CHARSET=utf8mb4;

-- Stok: filament ve diğer malzemeler. Birim maliyet = pack_price / pack_size (örn. 650 ₺ / 1000 g).
CREATE TABLE IF NOT EXISTS materials(
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, kind VARCHAR(60) NOT NULL DEFAULT 'Filament',
 color VARCHAR(60), hex VARCHAR(7) NULL, unit VARCHAR(20) NOT NULL DEFAULT 'g', qty DECIMAL(12,2) NOT NULL DEFAULT 0,
 min_qty DECIMAL(12,2) NOT NULL DEFAULT 0, pack_price DECIMAL(10,2) NOT NULL DEFAULT 0, pack_size DECIMAL(12,2) NOT NULL DEFAULT 1000,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) CHARSET=utf8mb4;

-- Panel ayarları (elektrik birim fiyatı, yazıcı gücü).
CREATE TABLE IF NOT EXISTS settings(k VARCHAR(40) PRIMARY KEY, v VARCHAR(255) NOT NULL) CHARSET=utf8mb4;

-- Baskılar. Maliyetler kayıt anındaki fiyatlarla saklanır; sonradan değişen filament fiyatı eski kayıtları etkilemez.
CREATE TABLE IF NOT EXISTS prints(
 id INT AUTO_INCREMENT PRIMARY KEY, printed_on DATE NOT NULL, title VARCHAR(150) NOT NULL, qty INT NOT NULL DEFAULT 1,
 hours DECIMAL(6,2) NOT NULL DEFAULT 0, material_cost DECIMAL(10,2) NOT NULL DEFAULT 0, power_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
 extra_cost DECIMAL(10,2) NOT NULL DEFAULT 0, sale_price DECIMAL(10,2) NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'ok',
 deducted TINYINT(1) NOT NULL DEFAULT 0, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(printed_on)) CHARSET=utf8mb4;

-- Bir baskıda kullanılan malzemeler (renkler). Ad ve renk, malzeme silinse de kalsın diye kopyalanır.
CREATE TABLE IF NOT EXISTS print_materials(
 id INT AUTO_INCREMENT PRIMARY KEY, print_id INT NOT NULL, material_id INT NULL, name VARCHAR(150) NOT NULL, color VARCHAR(60),
 hex VARCHAR(7), unit VARCHAR(20) NOT NULL DEFAULT 'g', amount DECIMAL(12,2) NOT NULL DEFAULT 0, cost DECIMAL(10,2) NOT NULL DEFAULT 0,
 INDEX(print_id)) CHARSET=utf8mb4;

-- Satışlar. Kâr = adet × birim fiyat − komisyon − kargo − adet × birim maliyet.
CREATE TABLE IF NOT EXISTS sales(
 id INT AUTO_INCREMENT PRIMARY KEY, sold_on DATE NOT NULL, print_id INT NULL, title VARCHAR(150) NOT NULL, qty INT NOT NULL DEFAULT 1,
 unit_price DECIMAL(10,2) NOT NULL DEFAULT 0, unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0, fee DECIMAL(10,2) NOT NULL DEFAULT 0,
 shipping DECIMAL(10,2) NOT NULL DEFAULT 0, channel VARCHAR(30) NOT NULL DEFAULT 'Diğer', customer VARCHAR(120), notes TEXT,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(sold_on), INDEX(print_id)) CHARSET=utf8mb4;
