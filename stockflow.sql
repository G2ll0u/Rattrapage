-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : dim. 04 mai 2025 à 09:58
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `stockflow`
--

-- --------------------------------------------------------

--
-- Structure de la table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE IF NOT EXISTS `audit_log` (
  `ID_Log` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `ID_User` int UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `row_id` bigint UNSIGNED DEFAULT NULL,
  `log_timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_Log`),
  KEY `ID_User` (`ID_User`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `audit_log`
--

INSERT INTO `audit_log` (`ID_Log`, `ID_User`, `action`, `table_name`, `row_id`, `log_timestamp`) VALUES
(1, 1, 'INSERT', 'Product', 1, '0000-00-00 00:00:00'),
(2, 1, 'INSERT', 'Product', 2, '0000-00-00 00:00:00'),
(3, 2, 'UPDATE', 'Product', 1, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `category`
--

DROP TABLE IF EXISTS `category`;
CREATE TABLE IF NOT EXISTS `category` (
  `ID_Category` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`ID_Category`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `category`
--

INSERT INTO `category` (`ID_Category`, `name`) VALUES
(1, 'Electronics'),
(2, 'Office Supplies'),
(3, 'Furniture');

-- --------------------------------------------------------

--
-- Structure de la table `delivery`
--

DROP TABLE IF EXISTS `delivery`;
CREATE TABLE IF NOT EXISTS `delivery` (
  `ID_Delivery` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `delivery_date` date NOT NULL,
  `qty` int NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `ID_Product` int UNSIGNED NOT NULL,
  `ID_Provider` int UNSIGNED NOT NULL,
  PRIMARY KEY (`ID_Delivery`),
  KEY `ID_Product` (`ID_Product`),
  KEY `idx_delivery_provider` (`ID_Provider`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `delivery`
--

INSERT INTO `delivery` (`ID_Delivery`, `delivery_date`, `qty`, `unit_cost`, `ID_Product`, `ID_Provider`) VALUES
(1, '0000-00-00', 10, 750.00, 1, 1),
(2, '0000-00-00', 100, 8.00, 2, 2);

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `ID_Orders` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `status` enum('draft','pending','received','cancelled') DEFAULT 'draft',
  `ID_Provider` int UNSIGNED NOT NULL,
  PRIMARY KEY (`ID_Orders`),
  KEY `ID_Provider` (`ID_Provider`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `orders`
--

INSERT INTO `orders` (`ID_Orders`, `order_date`, `expected_date`, `status`, `ID_Provider`) VALUES
(1, '0000-00-00', '0000-00-00', 'pending', 1);

-- --------------------------------------------------------

--
-- Structure de la table `order_line`
--

DROP TABLE IF EXISTS `order_line`;
CREATE TABLE IF NOT EXISTS `order_line` (
  `ID_Orders` int UNSIGNED NOT NULL,
  `ID_Product` int UNSIGNED NOT NULL,
  `qty` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`ID_Orders`,`ID_Product`),
  KEY `ID_Product` (`ID_Product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `order_line`
--

INSERT INTO `order_line` (`ID_Orders`, `ID_Product`, `qty`, `unit_price`) VALUES
(1, 1, 5, 760.00),
(1, 3, 2, 140.00);

-- --------------------------------------------------------

--
-- Structure de la table `product`
--

DROP TABLE IF EXISTS `product`;
CREATE TABLE IF NOT EXISTS `product` (
  `ID_Product` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `amount` int DEFAULT '0',
  `price` decimal(10,2) DEFAULT '0.00',
  `last_delivery` date DEFAULT NULL,
  `alert_threshold` int DEFAULT '0',
  `ID_Category` int UNSIGNED NOT NULL,
  PRIMARY KEY (`ID_Product`),
  UNIQUE KEY `reference` (`reference`),
  KEY `ID_Category` (`ID_Category`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `product`
--

INSERT INTO `product` (`ID_Product`, `reference`, `name`, `amount`, `price`, `last_delivery`, `alert_threshold`, `ID_Category`) VALUES
(1, 'LPT‑001', 'Laptop', 10, 800.00, '0000-00-00', 5, 1),
(2, 'MOU‑010', 'Mouse', 50, 12.50, '0000-00-00', 20, 2),
(3, 'DSK‑200', 'Desk', 5, 150.00, '0000-00-00', 2, 3),
(4, 'CHR‑110', 'Chair', 15, 90.00, '0000-00-00', 5, 3);

-- --------------------------------------------------------

--
-- Structure de la table `provider`
--

DROP TABLE IF EXISTS `provider`;
CREATE TABLE IF NOT EXISTS `provider` (
  `ID_Provider` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(12) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `contact_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ID_Provider`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `provider`
--

INSERT INTO `provider` (`ID_Provider`, `name`, `email`, `phone`, `address`, `contact_name`) VALUES
(1, 'TechSupply', 'contact@techsupply.com', '0123456789', '123 Tech St.', 'Alice Green'),
(2, 'OfficeWorld', 'sales@officeworld.com', '0987654321', '456 Office Ave.', 'Bob White');

-- --------------------------------------------------------

--
-- Structure de la table `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE IF NOT EXISTS `role` (
  `ID_Role` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`ID_Role`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `role`
--

INSERT INTO `role` (`ID_Role`, `name`) VALUES
(1, 'Admin'),
(2, 'Manager'),
(3, 'Employee');

-- --------------------------------------------------------

--
-- Structure de la table `stock_movement`
--

DROP TABLE IF EXISTS `stock_movement`;
CREATE TABLE IF NOT EXISTS `stock_movement` (
  `ID_Move` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `move_date` date NOT NULL,
  `qty` int NOT NULL,
  `direction` enum('IN','OUT') NOT NULL,
  `ID_Product` int UNSIGNED NOT NULL,
  `ID_User` int UNSIGNED NOT NULL,
  `ID_Provider` int UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`ID_Move`),
  KEY `ID_User` (`ID_User`),
  KEY `ID_Provider` (`ID_Provider`),
  KEY `idx_stock_move_product` (`ID_Product`),
  KEY `idx_stock_move_date` (`move_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `stock_movement`
--

INSERT INTO `stock_movement` (`ID_Move`, `move_date`, `qty`, `direction`, `ID_Product`, `ID_User`, `ID_Provider`) VALUES
(1, '0000-00-00', 10, 'IN', 1, 1, 1),
(2, '0000-00-00', 100, 'IN', 2, 1, 2),
(3, '0000-00-00', 2, 'OUT', 1, 2, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user_`
--

DROP TABLE IF EXISTS `user_`;
CREATE TABLE IF NOT EXISTS `user_` (
  `ID_User` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `ID_Role` int UNSIGNED NOT NULL,
  PRIMARY KEY (`ID_User`),
  UNIQUE KEY `email` (`email`),
  KEY `ID_Role` (`ID_Role`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `user_`
--

INSERT INTO `user_` (`ID_User`, `name`, `first_name`, `email`, `password`, `ID_Role`) VALUES
(1, 'Smith', 'John', 'john.smith@admin.com', '$2y$10$rV51rxP2w7RvOA85N.Sjcu4gVccfCPEAAOYP5wLVk8Dqj4sTvNhWu', 1),
(2, 'Brown', 'Ella', 'ella.brown@manager.com', '$2y$10$uyb8YmjArpzVoqIFhWNCyOxZBdmkSQaZ69R8dIMVtsZah5vgOQqi6', 2),
(3, 'Doe', 'Mark', 'mark.doe@employee.com', '$2y$10$Xy50wSzfJkUPQLduPIuO9OdsybM7NycEDH/kj6iJOIpssQzU2oMSy', 3),
(4, 'Taylor', 'Alice', 'alice.taylor@admin.com', '$2y$10$3nvaPbUpSyvIxZAA/Ooecuk8OVY67BRxM9c73xNqQ70CTJC0Ahdd6', 1),
(5, 'Wilson', 'David', 'david.wilson@admin.com', '$2y$10$whtSMibl22JAX9BcpoN6CODnd6cqRvjElCWcyze2Jr8uyU8OMQDI6', 1),
(6, 'Garcia', 'Maria', 'maria.garcia@manager.com', '$2y$10$akTiAAZxxVpDtgYZBpY/uOjYMIpKXoPPxdcj0ohoHCYRA.0Pm1BYu', 2),
(7, 'Martinez', 'Carlos', 'carlos.martinez@manager.com', '$2y$10$wZFOeSkpn5a11WujZ/ciHedMOU3bm7tUPQPb8J2ir.Og0KAG/ecyO', 2),
(8, 'Lee', 'Sarah', 'sarah.lee@manager.com', '$2y$10$3OoDSsNSe7vXKos2jgQMm.POKSwBmZaimA9IXTU6rswpARX8hfZeq', 2),
(9, 'Clark', 'Daniel', 'daniel.clark@manager.com', '$2y$10$tpQ1TzPLmgKfnngB5MepxOCFNEL4QuYTvQyH9cHhI.R7feUE80MVi', 2),
(10, 'Patel', 'Priya', 'priya.patel@manager.com', '$2y$10$4Jz/JWZ1GRMvufQFK.MpU.ncHKVi0OcYeAbyYv/PtNkwSP283eP0m', 2),
(11, 'Kim', 'Hannah', 'hannah.kim@employee.com', '$2y$10$pzkYu60L9RwqqlMA.My6GOh2GtWZkmBASj6u.AKfdRuGm9C5kUJMi', 3),
(12, 'Nguyen', 'Liam', 'liam.nguyen@employee.com', '$2y$10$uLWXbtluMQ2ENJsTykNZXOUVVmR4TIonQN3pt9wRpsEmLeq5txeWK', 3),
(13, 'Hernandez', 'Sofia', 'sofia.hernandez@employee.com', '$2y$10$o9MZ7d3s7jQ54tdZTzeK.uVciJNs4AcTCgN9ilSRi56NzHmO2ms4W', 3),
(14, 'Lopez', 'Miguel', 'miguel.lopez@employee.com', '$2y$10$DBOnGtnju/2fWfgyXx2DL.o6vOKJOlVUFpXdR3RQ1d7nOsEDN4yfK', 3),
(15, 'Gonzalez', 'Isabella', 'isabella.gonzalez@employee.com', '$2y$10$bEr5UYiJkMTNg60YyAzDLuL/FYW6Y.GSJwAYJ5ArqE7C101MKhO42', 3),
(16, 'Perez', 'Ethan', 'ethan.perez@employee.com', '$2y$10$v5TiDK2l/ZLRvYcau1lT/ekYBcKSgXKGgvycYz8ZGWzvHCk02UDYq', 3),
(17, 'Roberts', 'Chloe', 'chloe.roberts@employee.com', '$2y$10$cfGNQh1hsD0Pxi6IhCJAcuaPsYpZGqnZjLXT0DIbls/ijIpt51F6q', 3),
(18, 'Murphy', 'Lucas', 'lucas.murphy@employee.com', '$2y$10$eOLjlTK86cqcc.IJNQbPmO4tHVR56uCOa6u0fcWnAGv9ul.MDc8E2', 3),
(19, 'Hughes', 'Ava', 'ava.hughes@employee.com', '$2y$10$hbiW5Nac/Wz8VHd.R8G9w.r6/IGE1taDso4/XHbVwizrAn4.lR/AO', 3),
(20, 'Foster', 'Noah', 'noah.foster@employee.com', '$2y$10$AjBrZUm0CC3tiwZfJGVhxO1fyeRkYe4xUlETzFTurNBcp7PHkCVg2', 3),
(21, 'Evans', 'Lily', 'lily.evans@employee.com', '$2y$10$JwbgLBnU0b62kECdDGW6.eOzPbpD86AwyTb2QPMgyWLBjBGgz7ita', 3),
(22, 'Stewart', 'Mason', 'mason.stewart@employee.com', '$2y$10$sw1Zdx7XqnQ6.tLL0LU2we9Sgw8lT9DS/ykrhP4vcBLhiX5.H8lUW', 3),
(23, 'Morgan', 'Zoe', 'zoe.morgan@employee.com', '$2y$10$xovYLNgzPLrtNHacx0nwVez36i9trWeotB/w.mBU8k/mDJJXGRsgC', 3),
(24, 'Doe', 'John', 'john.doe@example.com', '$2y$10$h472q0retUfGtBojC3qTT.KNNmD5ixyS7oQZ/lS8g7Wz566i5ilXW', 2);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`ID_User`) REFERENCES `user_` (`ID_User`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `delivery`
--
ALTER TABLE `delivery`
  ADD CONSTRAINT `delivery_ibfk_1` FOREIGN KEY (`ID_Product`) REFERENCES `product` (`ID_Product`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `delivery_ibfk_2` FOREIGN KEY (`ID_Provider`) REFERENCES `provider` (`ID_Provider`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`ID_Provider`) REFERENCES `provider` (`ID_Provider`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `order_line`
--
ALTER TABLE `order_line`
  ADD CONSTRAINT `order_line_ibfk_1` FOREIGN KEY (`ID_Orders`) REFERENCES `orders` (`ID_Orders`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_line_ibfk_2` FOREIGN KEY (`ID_Product`) REFERENCES `product` (`ID_Product`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`ID_Category`) REFERENCES `category` (`ID_Category`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `stock_movement`
--
ALTER TABLE `stock_movement`
  ADD CONSTRAINT `stock_movement_ibfk_1` FOREIGN KEY (`ID_Product`) REFERENCES `product` (`ID_Product`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_movement_ibfk_2` FOREIGN KEY (`ID_User`) REFERENCES `user_` (`ID_User`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_movement_ibfk_3` FOREIGN KEY (`ID_Provider`) REFERENCES `provider` (`ID_Provider`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `user_`
--
ALTER TABLE `user_`
  ADD CONSTRAINT `user__ibfk_1` FOREIGN KEY (`ID_Role`) REFERENCES `role` (`ID_Role`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
