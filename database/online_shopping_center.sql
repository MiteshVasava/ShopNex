-- Select your database in phpMyAdmin before importing this file.
CREATE TABLE IF NOT EXISTS users(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,email VARCHAR(190) NOT NULL UNIQUE,password VARCHAR(255) NOT NULL,phone VARCHAR(30),role ENUM('customer','staff','admin') NOT NULL DEFAULT 'customer',status TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS categories(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL UNIQUE,description TEXT,status TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS brands(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL UNIQUE,status TINYINT(1) DEFAULT 1);
CREATE TABLE IF NOT EXISTS products(id INT AUTO_INCREMENT PRIMARY KEY,category_id INT NOT NULL,brand_id INT NULL,name VARCHAR(180) NOT NULL,brand VARCHAR(100) NOT NULL DEFAULT '',sku VARCHAR(80) NOT NULL UNIQUE,description TEXT,price DECIMAL(12,2) NOT NULL,old_price DECIMAL(12,2),stock INT NOT NULL DEFAULT 0,image TEXT,specifications JSON,featured TINYINT(1) DEFAULT 0,status TINYINT(1) DEFAULT 1,rating DECIMAL(2,1) DEFAULT 0,views INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(category_id) REFERENCES categories(id),FOREIGN KEY(brand_id) REFERENCES brands(id) ON DELETE SET NULL);
CREATE TABLE IF NOT EXISTS addresses(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,label VARCHAR(50) DEFAULT 'Home',line1 VARCHAR(255) NOT NULL,city VARCHAR(100) NOT NULL,state VARCHAR(100) NOT NULL,pincode VARCHAR(20) NOT NULL,phone VARCHAR(30),is_default TINYINT(1) DEFAULT 0,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS wishlists(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,product_id INT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_wishlist(user_id,product_id),FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS orders(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,subtotal DECIMAL(12,2) NOT NULL,shipping DECIMAL(12,2) DEFAULT 0,discount DECIMAL(12,2) DEFAULT 0,total DECIMAL(12,2) NOT NULL,address_text TEXT,payment_method VARCHAR(50) NOT NULL,payment_status VARCHAR(40) NOT NULL DEFAULT 'pending',status VARCHAR(50) NOT NULL DEFAULT 'placed',delivery_date DATE,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id));
CREATE TABLE IF NOT EXISTS order_items(id INT AUTO_INCREMENT PRIMARY KEY,order_id INT NOT NULL,product_id INT NOT NULL,quantity INT NOT NULL,unit_price DECIMAL(12,2) NOT NULL,FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id));
CREATE TABLE IF NOT EXISTS reviews(id INT AUTO_INCREMENT PRIMARY KEY,product_id INT NOT NULL,user_id INT NOT NULL,rating TINYINT NOT NULL,comment TEXT,status TINYINT(1) DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_review(product_id,user_id),FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS coupons(id INT AUTO_INCREMENT PRIMARY KEY,code VARCHAR(40) UNIQUE NOT NULL,type ENUM('percent','fixed') NOT NULL,amount DECIMAL(10,2) NOT NULL,min_order DECIMAL(12,2) DEFAULT 0,max_discount DECIMAL(12,2) NULL,expires_at DATE,status TINYINT(1) DEFAULT 1);
CREATE TABLE IF NOT EXISTS coupon_usage(id INT AUTO_INCREMENT PRIMARY KEY,coupon_id INT NOT NULL,user_id INT NOT NULL,order_id INT NOT NULL,used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(coupon_id) REFERENCES coupons(id),FOREIGN KEY(user_id) REFERENCES users(id),FOREIGN KEY(order_id) REFERENCES orders(id));
CREATE TABLE IF NOT EXISTS support_tickets(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,subject VARCHAR(180) NOT NULL,message TEXT NOT NULL,status VARCHAR(40) DEFAULT 'open',staff_reply TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id));
CREATE TABLE IF NOT EXISTS notifications(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,title VARCHAR(180) NOT NULL,message TEXT NOT NULL,is_read TINYINT(1) DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS site_settings(setting_key VARCHAR(100) PRIMARY KEY,setting_value TEXT NOT NULL,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
INSERT IGNORE INTO categories(name,description) VALUES
('Mobiles & Tablets','Smartphones, tablets and mobile accessories'),('Computers & Laptops','Laptops, desktops and computer hardware'),('Electronics','TVs, cameras, audio and electronics'),('Gaming','Consoles, games and gaming accessories'),('Fashion','Clothing, footwear and accessories'),('Home & Appliances','Home, kitchen and electrical appliances'),('Beauty & Personal Care','Beauty, grooming and personal care'),('Sports & Fitness','Fitness gear and sports products'),('Books & Stationery','Books, study and stationery'),('Office & Business','Office supplies and business equipment'),('Toys & Games','Toys, puzzles and family games'),('Automotive','Car and bike accessories'),('Groceries & Essentials','Daily-use groceries and essentials'),('Jewelry & Accessories','Jewelry, watches and fashion accessories');
INSERT IGNORE INTO brands(name) VALUES('Apple'),('Samsung'),('Dell'),('HP'),('Nike'),('HomePro'),('Glow'),('FitTrack'),('ReaderHouse'),('PlayWorld'),('Sony'),('Lenovo');
INSERT IGNORE INTO users(name,email,password,phone,role,status) VALUES
('System Admin','admin@shopnex.local','$2y$12$JJfZHuC7YJrdiKqB7RZQhekPubn7F2.lBD0CKGzHirlpoPx8ycQye','+91 9000000001','admin',1),
('Store Staff','staff@shopnex.local','$2y$12$WvVcpoQFHU82N4jqLtrqMOcI9okuc7Fs3jw0G5u75jsM3/8DS3CTa','+91 9000000002','staff',1),
('Demo Customer','customer@shopnex.local','$2y$12$gpMIBukvf0cBD5FYO23tke8Kea.pqREvV.A3tRsSYvWuU1Ff..Du2','+91 9000000003','customer',1);
INSERT IGNORE INTO products(category_id,brand_id,name,sku,description,price,old_price,stock,image,specifications,featured,status,rating) VALUES
(1,1,'iPhone 15','APL-IP15','A premium smartphone with a bright display and advanced camera system.',79999,89999,20,'https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?auto=format&fit=crop&w=800&q=80','{"Display":"6.1 inch","Storage":"128GB","Camera":"48MP"}',1,1,4.8),
(2,1,'MacBook Air','APL-MBA','Thin and light laptop for study, work and everyday productivity.',99999,109999,12,'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80','{"Display":"13 inch","RAM":"8GB","Storage":"256GB"}',1,1,4.7),
(5,5,'Classic Sneakers','NK-SNK-01','Comfortable everyday sneakers with a modern design.',8999,10999,30,'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80','{"Type":"Sneakers","Material":"Synthetic","Size":"6-11"}',1,1,4.6),
(6,6,'Modern Chair','HM-CHR-01','Minimal modern chair for home and office spaces.',5999,7499,8,'https://images.unsplash.com/photo-1503602642458-232111445657?auto=format&fit=crop&w=800&q=80','{"Material":"Wood","Color":"Natural","Use":"Home/Office"}',1,1,4.5),
(7,7,'Face Care Kit','GLW-FACE-01','Daily face care essentials for a simple routine.',1299,1599,25,'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=800&q=80','{"Type":"Skin Care","Items":"4","Use":"Daily"}',0,1,4.4),
(8,8,'Fitness Watch','FT-WCH-01','Activity tracking smartwatch with workout features.',4999,6999,18,'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80','{"Display":"AMOLED","Battery":"7 days","Water":"Yes"}',1,1,4.5),
(9,9,'The Science Book','BK-SCI-01','A visual introduction to science for curious learners.',799,999,40,'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=800&q=80','{"Pages":"450","Language":"English","Format":"Paperback"}',0,1,4.7),
(11,10,'Building Blocks Set','TW-BLK-01','Creative building blocks for learning and play.',1499,1899,22,'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?auto=format&fit=crop&w=800&q=80','{"Pieces":"500","Age":"6+","Material":"ABS"}',0,1,4.6),
(3,11,'Wireless Headphones','SON-WH01','Immersive wireless audio with active noise reduction.',6999,8999,16,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','{"Type":"Over-ear","Battery":"30 hours","Connectivity":"Bluetooth"}',1,1,4.6),
(4,11,'Gaming Controller','SON-GC01','Comfortable wireless controller for gaming sessions.',5499,6499,14,'https://images.unsplash.com/photo-1603481546238-487240415921?auto=format&fit=crop&w=800&q=80','{"Connectivity":"Wireless","Platform":"Multi-platform"}',1,1,4.5),
(10,4,'Office Backpack','HP-BAG01','Durable laptop backpack for office and college.',2499,2999,35,'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80','{"Laptop":"15.6 inch","Material":"Polyester"}',0,1,4.4),
(13,6,'Smart Kitchen Kettle','HM-KET01','Fast-boil electric kettle for home and office.',1799,2199,27,'https://images.unsplash.com/photo-1594212699903-ec8a3eca50f5?auto=format&fit=crop&w=800&q=80','{"Capacity":"1.7L","Power":"1500W"}',0,1,4.3);
INSERT IGNORE INTO coupons(code,type,amount,min_order,max_discount,expires_at) VALUES('WELCOME10','percent',10,999,1000,'2030-12-31'),('SAVE200','fixed',200,2999,NULL,'2030-12-31');

UPDATE products p JOIN brands b ON b.id=p.brand_id SET p.brand=b.name;


-- Staff permissions: only administrators can assign these grants.
CREATE TABLE IF NOT EXISTS staff_permissions (
 user_id INT NOT NULL, permission_key VARCHAR(60) NOT NULL, allowed TINYINT(1) NOT NULL DEFAULT 0,
 PRIMARY KEY(user_id, permission_key),
 CONSTRAINT fk_staff_permission_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ShopNex feature tables. These are intentionally created without foreign-key
-- constraints for compatibility with shared hosting and existing installations.
CREATE TABLE IF NOT EXISTS staff_permissions (
    user_id INT NOT NULL,
    permission_key VARCHAR(60) NOT NULL,
    allowed TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id, permission_key),
    INDEX idx_staff_permissions_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_images_product_sort (product_id, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
