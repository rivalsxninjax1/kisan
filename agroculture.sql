-- ============================================================================
-- KISAN database  (import this file in phpMyAdmin > Import, at the SERVER level)
--
-- It creates the `kisan` database that db.php expects, so you do not need to
-- create or select a database first.
--
-- WARNING: this file drops and re-creates every KISAN table, so importing it
-- again resets the data. Fine for local development.
--
-- Demo logins (password for both:  Demo@1234)
--     farmer  ->  demo_farmer
--     buyer   ->  demo_buyer
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `kisan`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kisan`;

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `transaction`, `review`, `mycart`, `likedata`,
                     `blogfeedback`, `blogdata`, `fproduct`, `buyer`, `farmer`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- Users
-- ----------------------------------------------------------------------------

CREATE TABLE `farmer` (
  `fid`       INT          NOT NULL AUTO_INCREMENT,
  `fname`     VARCHAR(255) NOT NULL,
  `fusername` VARCHAR(100) NOT NULL,
  `fpassword` VARCHAR(255) NOT NULL,
  `fhash`     VARCHAR(64)  NOT NULL DEFAULT '',
  `femail`    VARCHAR(190) NOT NULL,
  `fmobile`   VARCHAR(20)  NOT NULL,
  `faddress`  TEXT         NOT NULL,
  `factive`   TINYINT      NOT NULL DEFAULT 1,
  `frating`   INT          NOT NULL DEFAULT 0,
  `picExt`    VARCHAR(10)  NOT NULL DEFAULT 'png',
  `picStatus` TINYINT      NOT NULL DEFAULT 0,
  `created`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`fid`),
  UNIQUE KEY `uq_farmer_username` (`fusername`),
  UNIQUE KEY `uq_farmer_email` (`femail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `buyer` (
  `bid`       INT          NOT NULL AUTO_INCREMENT,
  `bname`     VARCHAR(255) NOT NULL,
  `busername` VARCHAR(100) NOT NULL,
  `bpassword` VARCHAR(255) NOT NULL,
  `bhash`     VARCHAR(64)  NOT NULL DEFAULT '',
  `bemail`    VARCHAR(190) NOT NULL,
  `bmobile`   VARCHAR(20)  NOT NULL,
  `baddress`  TEXT         NOT NULL,
  `bactive`   TINYINT      NOT NULL DEFAULT 1,
  `created`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bid`),
  UNIQUE KEY `uq_buyer_username` (`busername`),
  UNIQUE KEY `uq_buyer_email` (`bemail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Marketplace
-- ----------------------------------------------------------------------------

CREATE TABLE `fproduct` (
  `pid`       INT           NOT NULL AUTO_INCREMENT,
  `fid`       INT           NOT NULL,
  `product`   VARCHAR(255)  NOT NULL,
  `pcat`      VARCHAR(50)   NOT NULL,
  `pinfo`     TEXT          NOT NULL,
  `price`     DECIMAL(10,2) NOT NULL,
  `unit`      VARCHAR(20)   NOT NULL DEFAULT 'kg',
  `pimage`    VARCHAR(255)  NOT NULL DEFAULT '',
  `picStatus` TINYINT       NOT NULL DEFAULT 0,
  `created`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pid`),
  KEY `idx_fproduct_fid` (`fid`),
  KEY `idx_fproduct_cat` (`pcat`),
  CONSTRAINT `fk_fproduct_farmer` FOREIGN KEY (`fid`) REFERENCES `farmer` (`fid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mycart` (
  `bid` INT NOT NULL,
  `pid` INT NOT NULL,
  PRIMARY KEY (`bid`, `pid`),
  KEY `idx_mycart_pid` (`pid`),
  CONSTRAINT `fk_mycart_buyer`   FOREIGN KEY (`bid`) REFERENCES `buyer` (`bid`)    ON DELETE CASCADE,
  CONSTRAINT `fk_mycart_product` FOREIGN KEY (`pid`) REFERENCES `fproduct` (`pid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `review` (
  `reviewId` INT          NOT NULL AUTO_INCREMENT,
  `pid`      INT          NOT NULL,
  `name`     VARCHAR(255) NOT NULL,
  `rating`   TINYINT      NOT NULL,
  `comment`  TEXT         NOT NULL,
  `created`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reviewId`),
  KEY `idx_review_pid` (`pid`),
  CONSTRAINT `fk_review_product` FOREIGN KEY (`pid`) REFERENCES `fproduct` (`pid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transaction` (
  `tid`       INT          NOT NULL AUTO_INCREMENT,
  `bid`       INT          NOT NULL,
  `pid`       INT          NOT NULL,
  `name`      VARCHAR(255) NOT NULL,
  `city`      VARCHAR(255) NOT NULL,
  `mobile`    VARCHAR(20)  NOT NULL,
  `email`     VARCHAR(190) NOT NULL,
  `pincode`   VARCHAR(20)  NOT NULL,
  `addr`      VARCHAR(255) NOT NULL,
  `orderTime` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tid`),
  KEY `idx_transaction_bid` (`bid`),
  KEY `idx_transaction_pid` (`pid`),
  CONSTRAINT `fk_transaction_buyer`   FOREIGN KEY (`bid`) REFERENCES `buyer` (`bid`)    ON DELETE CASCADE,
  CONSTRAINT `fk_transaction_product` FOREIGN KEY (`pid`) REFERENCES `fproduct` (`pid`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Blog
-- ----------------------------------------------------------------------------

CREATE TABLE `blogdata` (
  `blogId`      INT          NOT NULL AUTO_INCREMENT,
  `blogUser`    VARCHAR(100) NOT NULL,
  `blogTitle`   VARCHAR(255) NOT NULL,
  `blogContent` LONGTEXT     NOT NULL,
  `blogTime`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `likes`       INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`blogId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blogfeedback` (
  `commentId`   INT          NOT NULL AUTO_INCREMENT,
  `blogId`      INT          NOT NULL,
  `comment`     VARCHAR(500) NOT NULL,
  `commentUser` VARCHAR(100) NOT NULL,
  `commentPic`  VARCHAR(255) NOT NULL DEFAULT 'profile0.png',
  `commentTime` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`commentId`),
  KEY `idx_blogfeedback_blog` (`blogId`),
  CONSTRAINT `fk_blogfeedback_blog` FOREIGN KEY (`blogId`) REFERENCES `blogdata` (`blogId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Farmers and buyers have separate id ranges, so a like is identified by
-- (blog, role, user id). The original table could mix them up.
CREATE TABLE `likedata` (
  `blogId`     INT                    NOT NULL,
  `userRole`   ENUM('farmer','buyer') NOT NULL,
  `blogUserId` INT                    NOT NULL,
  PRIMARY KEY (`blogId`, `userRole`, `blogUserId`),
  CONSTRAINT `fk_likedata_blog` FOREIGN KEY (`blogId`) REFERENCES `blogdata` (`blogId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Demo data   (password for both demo users is  Demo@1234)
-- ----------------------------------------------------------------------------

INSERT INTO `farmer` (`fid`, `fname`, `fusername`, `fpassword`, `femail`, `fmobile`, `faddress`) VALUES
(1, 'Demo Farmer', 'demo_farmer', '$2y$10$J.0zOZjjpPEt6oKjgwagcu4YLU1sRFBvP83rXf9IfX5EMLGfATALy', 'demo.farmer@example.com', '9800000001', 'Ward 5, Biratnagar, Morang');

INSERT INTO `buyer` (`bid`, `bname`, `busername`, `bpassword`, `bemail`, `bmobile`, `baddress`) VALUES
(1, 'Demo Buyer', 'demo_buyer', '$2y$10$J.0zOZjjpPEt6oKjgwagcu4YLU1sRFBvP83rXf9IfX5EMLGfATALy', 'demo.buyer@example.com', '9800000002', 'Ward 3, Biratnagar, Morang');

INSERT INTO `fproduct` (`pid`, `fid`, `product`, `pcat`, `pinfo`, `price`, `unit`, `pimage`, `picStatus`) VALUES
(1,  1, 'Mango',       'Fruit',     'Sweet, juicy mangoes picked at the peak of ripeness. Grown without waxing or artificial ripening.', 180.00, 'kg',    'mango13.JPG',      1),
(2,  1, 'Banana',      'Fruit',     'Naturally ripened bananas, straight from our grove. Perfect for the whole family.',               120.00, 'dozen', 'banana12.JPG',     1),
(3,  1, 'Green Apple', 'Fruit',     'Crisp, tangy green apples with a clean finish. Great for snacking and baking.',                    220.00, 'kg',    'greenApple.JPG',   1),
(4,  1, 'Sapota',      'Fruit',     'Soft, honey-sweet sapota (chikoo), harvested ripe and ready to eat.',                              130.00, 'kg',    'Sapota5.jpg',      1),
(5,  1, 'Ladyfinger',  'Vegetable', 'Tender, fresh-picked okra. Harvested in the morning and delivered the same day.',                   90.00,  'kg',    'Ladyfinger12.jpg', 1),
(6,  1, 'Brinjal',     'Vegetable', 'Small, glossy brinjal with thin skin. Ideal for curries and roasting.',                              70.00,  'kg',    'brinjal.JPG',      1),
(7,  1, 'Potato',      'Vegetable', 'Firm, earthy potatoes that store well. Sorted by size and washed before packing.',                   45.00,  'kg',    'potato12.JPG',     1),
(8,  1, 'Mushroom',    'Vegetable', 'Fresh button mushrooms grown in a clean, controlled shed. Best used within three days.',             320.00, 'kg',    'mushroom12.JPG',   1),
(9,  1, 'Bajra',       'Grains',    'Whole pearl millet, cleaned and sun-dried. A hardy, nutritious staple grain.',                       60.00,  'kg',    'Bajra4.jpg',       1),
(10, 1, 'Wheat',       'Grains',    'Clean, sun-dried wheat grain from this season''s harvest.',                                          55.00,  'kg',    'wheat2.JPG',       1),
(11, 1, 'Rice',        'Grains',    'Long-grain rice, cleaned and sorted. Light, fluffy and aromatic once cooked.',                       85.00,  'kg',    'rice.JPG',         1),
(12, 1, 'Masoor Dal',  'Grains',    'Whole masoor lentils, sorted by hand. Cooks quickly and is rich in protein.',                       150.00, 'kg',    'masoor.JPG',       1);

INSERT INTO `blogdata` (`blogId`, `blogUser`, `blogTitle`, `blogContent`, `likes`) VALUES
(1, 'demo_farmer', 'Welcome to the KISAN blog',
 'This is the place for farmers and buyers to share tips, seasonal advice and stories from the field.\r\n\r\nWhat is growing well on your farm this month? Write a post and tell us.', 1);

INSERT INTO `blogfeedback` (`blogId`, `comment`, `commentUser`, `commentPic`) VALUES
(1, 'Great idea! Looking forward to reading more from local farmers.', 'demo_buyer', 'profile0.png');

INSERT INTO `likedata` (`blogId`, `userRole`, `blogUserId`) VALUES
(1, 'buyer', 1);

INSERT INTO `review` (`pid`, `name`, `rating`, `comment`) VALUES
(1, 'Demo Buyer', 5, 'Very sweet and fresh. Packed well and arrived on time.'),
(1, 'Demo Buyer', 4, 'Great flavour. A couple were slightly overripe, but overall very happy.');
