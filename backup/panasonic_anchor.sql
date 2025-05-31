/*
SQLyog Community v13.1.7 (64 bit)
MySQL - 8.0.42-0ubuntu0.22.04.1 : Database - panasonic_anchor
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`panasonic_anchor` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `panasonic_anchor`;

/*Table structure for table `anchor_smart_saver` */

DROP TABLE IF EXISTS `anchor_smart_saver`;

CREATE TABLE `anchor_smart_saver` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `mobile` bigint DEFAULT NULL,
  `flow_level` int DEFAULT '0',
  `flow_sub_level` varchar(15) DEFAULT '0',
  `language` varchar(10) DEFAULT NULL,
  `callback_datetime` datetime DEFAULT NULL,
  `callback_inserted_datetime` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `flow_level` (`flow_level`),
  KEY `flow_sub_level` (`flow_sub_level`),
  KEY `chat_id` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

/*Data for the table `anchor_smart_saver` */

insert  into `anchor_smart_saver`(`id`,`mobile`,`flow_level`,`flow_sub_level`,`language`,`callback_datetime`,`callback_inserted_datetime`,`created_at`) values 
(1,919029634011,2,'2.6.2.2','Hey.',NULL,NULL,'2025-05-22 16:45:21'),
(2,917738691223,1,'0','Hi',NULL,NULL,'2025-05-24 17:56:19'),
(3,917506493373,1,'0','Hi',NULL,NULL,'2025-05-24 18:00:23'),
(4,917718943161,2,'2.6.2.2','Hii',NULL,NULL,'2025-05-28 10:55:55');

/*Table structure for table `electician` */

DROP TABLE IF EXISTS `electician`;

CREATE TABLE `electician` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pincode` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '1 => Active, Inactive',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `electician` */

/*Table structure for table `non_registered_user` */

DROP TABLE IF EXISTS `non_registered_user`;

CREATE TABLE `non_registered_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mobile` bigint DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `pincode` int DEFAULT NULL,
  `otp` varchar(10) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0=> incomplete, 1=> pending, 2=>completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

/*Data for the table `non_registered_user` */

insert  into `non_registered_user`(`id`,`mobile`,`contact_no`,`name`,`pincode`,`otp`,`status`,`created_at`,`updated_at`,`updated_by`) values 
(1,919029634011,'9039634011',NULL,400603,'1POYay',2,'2025-05-23 12:47:02','2025-05-23 12:47:02',NULL);

/*Table structure for table `product` */

DROP TABLE IF EXISTS `product`;

CREATE TABLE `product` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category` int DEFAULT '0',
  `name` varchar(100) DEFAULT NULL,
  `desc` varchar(250) DEFAULT NULL,
  `url` varchar(250) DEFAULT NULL,
  `file_type` enum('DOCUMENT','VIDEO','IMAGE','TEXT','VOICE','AUDIO','APPLICATION') DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '1 => Active, Inactive',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=latin1;

/*Data for the table `product` */

insert  into `product`(`id`,`category`,`name`,`desc`,`url`,`file_type`,`status`,`created_at`,`updated_at`) values 
(1,2,'Pipes Catalogue',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/CMS/Pipes_Catalogue.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(2,3,'IAQ 23 090223 N',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Fan/IAQ_Catalogue23_090223_N.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:49:41'),
(3,4,'THEA IQ Catalogue',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Home_Automation/PANASONIC_THEA_IQ_Catalogue.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(4,4,'Thea Catalogue',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Home_Automation/Thea_Catalogue.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(5,5,'CDS Brochure Final web',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Housing Brochure/CDS_Brochure_Final_web.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(6,5,'ETS brochure web',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Housing Brochure/ETS_brochure_web.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(7,5,'i-class brochure Web',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Housing Brochure/i-class_brochure_Web.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(8,5,'L-Class Kitchen',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Housing Brochure/L-Class_Kitchen_Brochure_Web.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(9,5,'Seated Shower Brochure',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Housing Brochure/Seated_Shower_Brochure_Final_Web.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(10,5,'Shoe Cabinet Brochure',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Housing Brochure/Shoe_Cabinet_Brochure_Web.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(11,5,'Sink Brochure',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Housing Brochure/Sink_Brochure.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(12,5,'Wooden Flooring',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Housing Brochure/Wooden_Flooring_catalogue_July_2022.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(13,6,'Anchor LED Pricelist',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/LED/Anchor_LED_Pricelist.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(14,6,'Trade Lighting',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/LED/Panasonic_Trade_Lighting_Pricelist.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(15,16,'Anchor Conduit Pipes','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Product_Pricelist_&_Catalogue/Anchor_Conduit_Pipes/Anchor_Conduit_Pipes.pdf','DOCUMENT',1,'2024-01-16 13:31:59','2024-01-23 16:22:24'),
(16,13,'Air e {Air Purifier}','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Product_Pricelist_&_Catalogue/Panasonic_Housing/Panasonic_Air_e_{Air Purifier}.pdf','DOCUMENT',1,'2024-01-16 13:31:59','2024-01-23 16:21:45'),
(17,13,'Cloth Drying System','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Product_Pricelist_&_Catalogue/Panasonic_Housing/Panasonic_Cloth_Drying_System.pdf','DOCUMENT',1,'2024-01-16 13:31:59','2024-01-23 16:21:42'),
(18,13,'Electronic Toilet Seat','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Product_Pricelist_&_Catalogue/Panasonic_Housing/Panasonic_Electronic_Toilet_Seat.pdf','DOCUMENT',1,'2024-01-16 13:31:59','2024-01-23 16:21:57'),
(19,13,'Panasonic i Class','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Product_Pricelist_&_Catalogue/Panasonic_Housing/Panasonic_i_Class.pdf','DOCUMENT',1,'2024-01-16 13:31:59','2024-01-23 16:21:55'),
(20,13,'Panasonic L Class','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Product_Pricelist_&_Catalogue/Panasonic_Housing/Panasonic_L_Class.pdf','DOCUMENT',1,'2024-01-16 13:31:59','2024-01-23 16:21:53'),
(21,13,'Seated Shower','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Product_Pricelist_&_Catalogue/Panasonic_Housing/Panasonic_Seated_Shower.pdf','DOCUMENT',1,'2024-01-16 13:31:59','2024-01-23 16:21:51'),
(22,13,'Shoe Rack','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Product_Pricelist_&_Catalogue/Panasonic_Housing/Panasonic_Shoe_Rack.pdf','DOCUMENT',1,'2024-01-16 13:31:59','2024-01-23 16:21:50'),
(23,13,'Panasonic Sink','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Product_Pricelist_&_Catalogue/Panasonic_Housing/Panasonic_Sink.pdf','DOCUMENT',1,'2024-01-16 13:31:59','2024-01-23 16:21:48'),
(24,13,'Panasonic Sink','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Product_Pricelist_&_Catalogue/Panasonic_Housing/Panasonic_Wooden_Flooring.pdf','DOCUMENT',1,'2024-01-16 13:31:59','2024-01-23 16:21:46'),
(25,9,'Panasonic Catalogue',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/SWG/Catalogue/Panasonic_Catalogue.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(26,9,'UNO Catalogue',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/SWG/Catalogue/UNO_Catalogue.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(27,9,'Uno Plus Catalogue',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/SWG/Catalogue/Uno_Plus_Catalogue.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(28,9,'Panasonic MCB Pricelist',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/SWG/Catalogue/Panasonic_MCB_Pricelist.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(29,9,'Uno E Series Pricelist',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/SWG/Catalogue/Uno_E_Series_Pricelist.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(30,9,'Uno Plus Price List',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/SWG/Catalogue/Uno_Plus_Price_List.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(31,9,'Uno Pricelist',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/SWG/Catalogue/Uno_Pricelist.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(32,10,'Water Heater Catalogue','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Water Heater/Anchor_Water_Heater_Catalogue.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:57:47'),
(33,10,'WH BROCHURE','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Water Heater/Panasonic_WH_BROCHURE.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:57:45'),
(34,11,'Smart Controller',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/Anchor_Smart_Controller_Leaflet_SP.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(35,11,'Modular Boxes',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/Modular_Boxes.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(36,11,'Penta Modular',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/Penta_Modular.pdf','DOCUMENT',2,'2024-01-16 13:31:59','2024-01-18 15:08:05'),
(37,11,'REGENT',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/REGENT.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(38,11,'Rider',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/Rider.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(39,11,'Roma Classic',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/Roma_Classic.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(40,11,'Roma HA Pricelist',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/Roma_HA_Pricelist.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(41,11,'Roma Plus',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/Roma_Plus.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(42,11,'Roma Urban',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/Roma_Urban.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(43,11,'Smart Anchor Catalogue',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/Smart_Anchor_Catalogue_cum_Pricelist.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(44,11,'Tiona',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/Tiona.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(45,11,'Woods',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/Woods.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(46,11,'Ziva Pricelist',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Anchor/Ziva_Pricelist.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(47,12,'Akina',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Panasonic/Akina.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(48,12,'Europa',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Panasonic/Europa.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(49,12,'Vision',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/WD/Panasonic/Vision.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(50,13,'COPAPRO',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Wire/COPAPRO.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(51,13,'Wire & Cable',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/catalogue/Wire/Wire_&_Cable.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(52,14,'Anchor Conduit Pipes',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Anchor_Conduit_Pipes/Anchor_Conduit_Pipes.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(53,7,'Anchor Fan','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Fan/Anchor_Fan.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:19:46'),
(54,6,'Anchor LED Lighting','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/LED_Lighting/Anchor_LED_Lighting.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:19:16'),
(55,6,'Consumer Lighting','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/LED_Lighting/Panasonic_Consumer_Lighting.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:19:13'),
(56,16,'Air e {Air Purifier}',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Panasonic_Housing/Panasonic_Air_e_{Air_Purifier}.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(57,16,'Cloth Drying System',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Panasonic_Housing/Panasonic_Cloth_Drying_System.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(58,16,'Electronic Toilet Seat',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Panasonic_Housing/Panasonic_Electronic_Toilet_Seat.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(59,16,'Panasonic i Class',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Panasonic_Housing/Panasonic_i_Class.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(60,16,'Panasonic L Class',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Panasonic_Housing/Panasonic_L_Class.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(61,16,'Seated Shower',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Panasonic_Housing/Panasonic_Seated_Shower.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(62,16,'Panasonic Shoe Rack',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Panasonic_Housing/Panasonic_Shoe_Rack.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(63,16,'Panasonic Sink',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Panasonic_Housing/Panasonic_Sink.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(64,16,'Wooden Flooring',NULL,'https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Panasonic_Housing/Panasonic_Wooden_Flooring.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 15:08:05'),
(65,15,'Panasonic Power Tool','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Panasonic_Power_Tool/Panasonic_Power_Tool.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:22:11'),
(66,4,'Anchor UNO','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Switchgear/Catalogue/Anchor_UNO.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:18:29'),
(67,4,'Anchor UNO Plus','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Switchgear/Catalogue/Anchor_UNO_Plus.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:18:33'),
(68,4,'Panasonic-Switch Gear','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Switchgear/Catalogue/Panasonic_Switchgear.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:18:31'),
(69,3,'Anchor UNO','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Switchgear/Pricelist/Anchor_UNO.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:18:05'),
(70,3,'Anchor UNO E Series','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Switchgear/Pricelist/Anchor_UNO_E_Series.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:18:01'),
(71,3,'Anchor UNO Plus','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Switchgear/Pricelist/Anchor_UNO_Plus.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:17:59'),
(72,3,'Panasonic-Switch Gear','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Switchgear/Pricelist/Panasonic_Switchgear.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:17:58'),
(73,11,'Roma Smart Digital','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/System_Components/Panasonic_Home_Automation/Roma_Smart_Digital.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:21:12'),
(74,20,'Home Automation','Smart Controller MirAie','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/System_Components/Panasonic_Home_Automation/Smart_Controller_MirAie.pdf','DOCUMENT',2,'2024-01-16 13:32:00','2024-01-18 16:06:52'),
(75,10,'Thea','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/System_Components/Panasonic_Home_Automation/Thea.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:20:58'),
(76,10,'Thea IQ KNX','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/System_Components/Panasonic_Home_Automation/Thea_IQ_KNX.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:20:56'),
(77,11,'Vetaar','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/System_Components/Panasonic_Home_Automation/Vetaar.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:21:14'),
(78,12,'Panasonic Fire alarm','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/System_Components/Smart_Building/Panasonic_Fire_alarm_system.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:21:27'),
(79,8,'Anchor Water Heater','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Water_Heater/Anchor_Water_Heater.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 17:31:03'),
(80,8,'Panasonic Water Heater','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Water_Heater/Panasonic_Water_Heater.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 17:31:02'),
(81,5,'Anchor COPAPRO FR Wire','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wires_and_Cable/Anchor_COPAPRO.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:18:53'),
(82,5,'Anchor Wire Cable','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wires_and_Cable/Anchor_Wire.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:18:55'),
(83,1,'Modular Boxes','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Modular_Boxes.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:03:26'),
(84,1,'Obell','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Obel_Switch.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:03:26'),
(85,1,'Penta','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Penta.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:03:26'),
(86,1,'Penta Modular','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Penta_Modular.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:03:26'),
(87,1,'Regent','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Regent.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:03:26'),
(88,1,'Rider','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Rider.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:03:26'),
(89,1,'Roma Classic','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Roma_Classic.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:03:26'),
(90,1,'Roma Plus','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Roma_Plus.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:03:26'),
(91,1,'Roma Urban','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Roma_Urban.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:03:26'),
(92,1,'Smart Anchor Accessory','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Smart_Anchor_Accessories.pdf','DOCUMENT',1,'2024-01-16 13:32:00','2024-01-23 16:03:26'),
(93,1,'Tiona','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Tiona.pdf','DOCUMENT',1,'2024-01-16 13:32:01','2024-01-23 16:03:26'),
(94,1,'Woods','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Woods.pdf','DOCUMENT',1,'2024-01-16 13:32:01','2024-01-23 16:03:26'),
(95,1,'Ziva','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Anchor_Wiring_Device/Ziva.pdf','DOCUMENT',1,'2024-01-16 13:32:01','2024-01-23 16:03:26'),
(96,2,'Akina','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Panasonic_Wiring_Device/Akina.pdf','DOCUMENT',1,'2024-01-16 13:32:01','2024-01-23 16:04:54'),
(97,2,'Europa','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Panasonic_Wiring_Device/Europa.pdf','DOCUMENT',1,'2024-01-16 13:32:01','2024-01-23 16:04:56'),
(98,2,'Vision','','https://edas-webapi.edas.tech/vaaniSM/catalogue_brochure/product_pricelist_catalogue/Wiring_Device/Panasonic_Wiring_Device/Vision.pdf','DOCUMENT',1,'2024-01-16 13:32:01','2024-01-23 16:04:58'),
(99,27,'Anchor Roma Plus',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Roma_Plus_202407011603396641633019986.pdf','DOCUMENT',1,'2024-09-18 19:09:54','2024-11-13 12:51:49'),
(100,27,'Anchor Roma Urban',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Roma_Urban_20240719101908281449018407.pdf','DOCUMENT',1,'2024-09-19 13:52:26','2024-11-13 12:58:53'),
(101,27,'Anchor Roma Classic',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Roma_Classic_20240701160315215798436585.pdf','DOCUMENT',1,'2024-09-19 13:53:00','2024-11-13 12:59:46'),
(102,27,'Anchor Woods',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Woods_2024071910240241258357649.pdf\r\n','DOCUMENT',1,'2024-09-19 13:54:17','2024-11-13 12:59:53'),
(103,27,'Anchor Tiona\r\n',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Tiona_Pricelist_20231226152210589303586790.pdf','DOCUMENT',1,'2024-09-19 13:54:31','2024-11-13 13:00:05'),
(104,27,'Anchor Regent\r\n',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Regent_Pricelist_202312261519269811061971806.pdf\r\n','DOCUMENT',1,'2024-09-19 13:54:40','2024-11-13 13:00:11'),
(105,27,'Anchor Rider\r\n',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Rider_20240701160236617884012732.pdf','DOCUMENT',1,'2024-09-19 13:54:52','2024-11-13 13:00:20'),
(106,27,'Anchor Ziva\r\n',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Ziva_Pricelist_2024070116041025683052652.pdf\r\n','DOCUMENT',1,'2024-09-19 13:55:18','2024-11-13 13:00:24'),
(107,27,'Anchor Penta\r\n','','https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Penta_New_202407011601341081781367076.pdf\r\n','DOCUMENT',1,'2024-09-19 13:55:34','2024-11-13 13:00:29'),
(108,27,'Anchor Penta Flat\r\n',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Flat_Switches_Leaflet_20240610165438206757464178.pdf\r\n','DOCUMENT',1,'2024-09-19 13:55:46','2024-11-13 13:00:36'),
(109,27,'Anchor Penta Modular\r\n',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Penta_Modular_New_202407011602133251007397126.pdf\r\n','DOCUMENT',1,'2024-09-19 13:55:57','2024-11-13 13:00:42'),
(110,27,'Anchor Obel\r\n',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Obel_Switch_202312271622003772090119282.pdf\r\n','DOCUMENT',1,'2024-09-19 13:56:05','2024-11-13 13:00:46'),
(111,27,'Panasonic Vision',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Vision_Pricelist_copy_202407011605333481561496368.pdf\r\n','DOCUMENT',1,'2024-09-19 13:57:56','2024-11-13 13:00:56'),
(112,27,'Panasonic Thea\r\n',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Thea_202402221201063451766673620.pdf\r\n','DOCUMENT',1,'2024-09-19 13:58:19','2024-11-13 13:01:01'),
(113,27,'Panasonic Akina\r\n',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Akina_Pricelist_20231226152335804390397892.pdf\r\n','DOCUMENT',1,'2024-09-19 13:58:33','2024-11-13 13:01:07'),
(114,27,'Panasonic Europa\r\n',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switches_and_sockets/Europa_Pricelist_202312261524050851803549691.pdf\r\n','DOCUMENT',1,'2024-09-19 13:58:52','2024-11-13 13:01:16'),
(115,28,'Anchor Uno E series',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switchgear/Uno_E_Series_Pricelist_Final_20240701152805715719706208.pdf\r\n','DOCUMENT',1,'2024-09-19 14:09:31','2024-11-13 13:11:25'),
(116,28,'UNO\r\n',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switchgear/O_List_Price_202407011526585231338160583.pdf\r\n','DOCUMENT',1,'2024-09-19 15:16:19','2024-11-13 13:11:43'),
(117,28,'Anchor UNO Plus',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switchgear/Uno_Plus_Pricelist_20231226151411859466831394.pdf\r\n','DOCUMENT',1,'2024-09-19 15:16:24','2024-11-13 13:11:48'),
(118,28,'Panasonic Switch Gear',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/switchgear/Panasonic__MCB_Pricelist_SP_20240701152939806907639615.pdf\r\n','DOCUMENT',1,'2024-09-19 15:16:39','2024-11-13 13:11:56'),
(119,29,'Wire & Cables',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/wires_and_cables/Wire_&_Cable_202312261659586681900588413.pdf','DOCUMENT',1,'2024-09-20 16:31:50','2024-11-13 13:14:21'),
(120,30,'Anchor LED Lighting',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/led_lighting/Anchor_LED_Flippe24_Final-040924_copy_20240906161036974903963055.pdf','DOCUMENT',1,'2024-09-20 16:32:42','2024-11-13 13:19:17'),
(121,30,'Panasonic LED Lighting',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/led_lighting/Panasonic_Flipper24_Final_AW_270624_copy_20240906161001092886468504.pdf\r\n','DOCUMENT',1,'2024-09-20 16:33:20','2024-11-13 13:19:36'),
(122,31,'Fan',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/fan/IAQ_Catalogue_202312261458059661432463675.pdf','DOCUMENT',1,'2024-09-20 16:33:49','2024-11-13 13:21:34'),
(123,32,'Anchor Water Heater',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/water_heater/Anchor_Water_Heater_Catalogue_202312261509190941820747043.pdf\r\n','DOCUMENT',1,'2024-09-20 16:34:00','2024-11-13 13:23:56'),
(124,32,'Panasonic Water Heater',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/water_heater/Panasonic_Water_Heater_Catalogue_202312261510068431410046231.pdf\r\n','DOCUMENT',1,'2024-09-20 16:34:02','2024-11-13 13:24:01'),
(125,33,'Thea IQ',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/smart_homes/PANASONIC_THEA_IQ_Catalogue_20231227121517163101674549.pdf\r\n','DOCUMENT',1,'2024-09-20 16:35:30','2024-11-13 13:27:09'),
(126,33,'Vetaar',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/smart_homes/Vetaar_20240209101619851205310505.pdf\r\n','DOCUMENT',1,'2024-09-20 16:35:34','2024-11-13 13:27:14'),
(127,33,'Miraie',NULL,'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/smart_homes/Smart_Controller_Pricelist_20231226152147868345005172.pdf\r\n','DOCUMENT',1,'2024-09-20 16:35:48','2024-11-13 13:27:19');

/*Table structure for table `product_category` */

DROP TABLE IF EXISTS `product_category`;

CREATE TABLE `product_category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `desc` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '1 => Active, 2 => Inactive',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;

/*Data for the table `product_category` */

insert  into `product_category`(`id`,`name`,`desc`,`status`,`created_at`,`updated_at`) values 
(1,'Wiring Device','Anchor Wiring Device',1,'2024-01-16 13:02:28','2024-01-23 16:02:38'),
(2,'Wiring Device','Panasonic Wiring Device',1,'2024-01-16 13:03:43','2024-01-23 16:05:08'),
(3,'Switch Gear Pricelist','',1,'2024-01-16 13:02:28','2024-01-23 16:29:30'),
(4,'Switch Gear Catalogue','',1,'2024-01-16 13:02:27','2024-01-23 16:29:23'),
(5,'Wires & Cable','',1,'2024-01-16 13:02:28','2024-01-23 16:23:19'),
(6,'LED Lighting','',1,'2024-01-16 13:02:27','2024-01-23 16:23:21'),
(7,'Fan','',1,'2024-01-16 13:02:27','2024-01-23 16:23:55'),
(8,'Water Heater','',1,'2024-01-16 13:02:28','2024-01-23 16:24:18'),
(10,'System Components','Panasonic Home Automation',1,'2024-01-16 13:02:28','2024-01-23 16:25:05'),
(11,'System Components','Smart Controller- MirAie',1,'2024-01-18 15:02:08','2024-01-23 16:25:00'),
(12,'System Components','Smart Building',1,'2024-01-16 13:02:28','2024-01-23 16:25:03'),
(13,'Panasonic Housing','',1,'2024-01-16 13:02:27','2024-01-23 16:25:19'),
(15,'Panasonic - Power Tool','',1,'2024-01-16 13:02:27','2024-01-23 16:25:29'),
(16,'Anchor Conduit Pipes','',1,'2024-01-16 13:02:27','2024-01-23 16:25:38');

/*Table structure for table `retailer` */

DROP TABLE IF EXISTS `retailer`;

CREATE TABLE `retailer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `number` varchar(15) DEFAULT NULL,
  `address` varchar(250) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '1 => Active, Inactive',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `retailer` */

/*Table structure for table `setting` */

DROP TABLE IF EXISTS `setting`;

CREATE TABLE `setting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client` int DEFAULT NULL,
  `campaign` int DEFAULT NULL,
  `chat_id` int DEFAULT NULL,
  `ret_last_index` int DEFAULT '0',
  `ele_last_index` int DEFAULT '0',
  `prod_ofset` int DEFAULT '0' COMMENT 'Last index for product',
  `lsto` varchar(100) DEFAULT NULL COMMENT 'Last Selected Template Option',
  `child_option` varchar(100) DEFAULT NULL COMMENT 'sub menu',
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `setting` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
