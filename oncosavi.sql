-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 29, 2026 at 02:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `oncosavi`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `log_name`, `description`, `subject_type`, `event`, `subject_id`, `causer_type`, `causer_id`, `properties`, `batch_uuid`, `created_at`, `updated_at`) VALUES
(1, 'Usuarios', 'El usuario \'Saul Merino\' (ID: 1) ha sido creado', 'App\\Models\\User', 'created', 1, NULL, NULL, '{\"attributes\":{\"name\":\"Saul Merino\",\"email\":\"eduardo_hrdz18@hotmail.com\",\"password\":\"$2y$12$xuOIZpvBfg.BxzEAPQv.l.ptVeWQQchqetqARSHXqJnmMO1eBB8AO\",\"nickname\":\"saulmerino\",\"firma_path\":null,\"sello_path\":null}}', NULL, '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(2, 'Órdenes', 'La orden #1 ha sido creada', 'App\\Models\\Orden', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"cliente_id\":1,\"total\":\"52.00\",\"descuento\":\"0.00\",\"codigo_id\":null,\"fecha\":\"2026-09-13T06:00:00.000000Z\",\"observaciones\":null,\"observaciones_por_area\":null,\"estado\":\"pendiente\",\"muestras_recibidas\":null,\"semanas_gestacion\":null,\"toma_muestra_user_id\":null,\"fecha_toma_muestra\":null,\"medico_id\":null}}', NULL, '2026-09-14 03:19:10', '2026-09-14 03:19:10'),
(3, 'Órdenes', 'La orden #1 ha sido actualizada', 'App\\Models\\Orden', 'updated', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"estado\":\"en proceso\",\"toma_muestra_user_id\":1,\"fecha_toma_muestra\":\"2026-09-14T03:22:48.000000Z\"},\"old\":{\"estado\":\"pendiente\",\"toma_muestra_user_id\":null,\"fecha_toma_muestra\":null}}', NULL, '2026-09-14 03:22:48', '2026-09-14 03:22:48'),
(4, 'Órdenes', 'La orden #2 ha sido creada', 'App\\Models\\Orden', 'created', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"cliente_id\":1,\"total\":\"25.00\",\"descuento\":\"0.00\",\"codigo_id\":null,\"fecha\":\"2026-09-20T06:00:00.000000Z\",\"observaciones\":null,\"observaciones_por_area\":null,\"estado\":\"pendiente\",\"muestras_recibidas\":null,\"semanas_gestacion\":null,\"toma_muestra_user_id\":null,\"fecha_toma_muestra\":null,\"medico_id\":null}}', NULL, '2026-09-21 03:46:32', '2026-09-21 03:46:32'),
(5, 'Órdenes', 'La orden #2 ha sido actualizada', 'App\\Models\\Orden', 'updated', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"estado\":\"en proceso\",\"toma_muestra_user_id\":1,\"fecha_toma_muestra\":\"2026-09-21T03:46:48.000000Z\"},\"old\":{\"estado\":\"pendiente\",\"toma_muestra_user_id\":null,\"fecha_toma_muestra\":null}}', NULL, '2026-09-21 03:46:48', '2026-09-21 03:46:48'),
(6, 'Resultados', 'Resultado ingresado para \'GLÓBULOS ROJOS\' en Orden #2. Valor: 3000000', 'App\\Models\\Resultado', 'created', 1, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":1,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":139,\"resultado\":\"3000000\",\"prueba_nombre_snapshot\":\"GL\\u00d3BULOS ROJOS\",\"valor_referencia_snapshot\":\"3800000 - 5800000\",\"unidades_snapshot\":\"mm\\u00b3\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(7, 'Resultados', 'Resultado ingresado para \'HEMATOCRITO\' en Orden #2. Valor: 38', 'App\\Models\\Resultado', 'created', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":2,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":140,\"resultado\":\"38\",\"prueba_nombre_snapshot\":\"HEMATOCRITO\",\"valor_referencia_snapshot\":\"37 - 53\",\"unidades_snapshot\":\"%\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(8, 'Resultados', 'Resultado ingresado para \'HEMOGLOBINA\' en Orden #2. Valor: 12', 'App\\Models\\Resultado', 'created', 3, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":3,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":141,\"resultado\":\"12\",\"prueba_nombre_snapshot\":\"HEMOGLOBINA\",\"valor_referencia_snapshot\":\"12 - 17\",\"unidades_snapshot\":\"gr\\/dl\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(9, 'Resultados', 'Resultado ingresado para \'V.C.M.\' en Orden #2. Valor: 80', 'App\\Models\\Resultado', 'created', 4, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":4,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":142,\"resultado\":\"80\",\"prueba_nombre_snapshot\":\"V.C.M.\",\"valor_referencia_snapshot\":\"80 - 110\",\"unidades_snapshot\":\"fL\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(10, 'Resultados', 'Resultado ingresado para \'H.C.M.\' en Orden #2. Valor: 25', 'App\\Models\\Resultado', 'created', 5, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":5,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":143,\"resultado\":\"25\",\"prueba_nombre_snapshot\":\"H.C.M.\",\"valor_referencia_snapshot\":\"26 - 38\",\"unidades_snapshot\":\"pg\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(11, 'Resultados', 'Resultado ingresado para \'C.H.C.M.\' en Orden #2. Valor: 30', 'App\\Models\\Resultado', 'created', 6, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":6,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":144,\"resultado\":\"30\",\"prueba_nombre_snapshot\":\"C.H.C.M.\",\"valor_referencia_snapshot\":\"31 - 37\",\"unidades_snapshot\":\"gr\\/dl\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(12, 'Resultados', 'Resultado ingresado para \'GLÓBULOS BLANCOS\' en Orden #2. Valor: 5000', 'App\\Models\\Resultado', 'created', 7, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":7,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":145,\"resultado\":\"5000\",\"prueba_nombre_snapshot\":\"GL\\u00d3BULOS BLANCOS\",\"valor_referencia_snapshot\":\"5000 - 10000\",\"unidades_snapshot\":\"mm\\u00b3\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(13, 'Resultados', 'Resultado ingresado para \'NEUTRÓFILOS\' en Orden #2. Valor: 50', 'App\\Models\\Resultado', 'created', 8, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":8,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":146,\"resultado\":\"50\",\"prueba_nombre_snapshot\":\"NEUTR\\u00d3FILOS\",\"valor_referencia_snapshot\":\"50 - 70\",\"unidades_snapshot\":\"%\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(14, 'Resultados', 'Resultado ingresado para \'LINFOCITOS\' en Orden #2. Valor: 20', 'App\\Models\\Resultado', 'created', 9, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":9,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":147,\"resultado\":\"20\",\"prueba_nombre_snapshot\":\"LINFOCITOS\",\"valor_referencia_snapshot\":\"20 - 40\",\"unidades_snapshot\":\"%\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(15, 'Resultados', 'Resultado ingresado para \'EOSINÓFILOS\' en Orden #2. Valor: 5', 'App\\Models\\Resultado', 'created', 10, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":10,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":148,\"resultado\":\"5\",\"prueba_nombre_snapshot\":\"EOSIN\\u00d3FILOS\",\"valor_referencia_snapshot\":\"0 - 5\",\"unidades_snapshot\":\"%\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(16, 'Resultados', 'Resultado ingresado para \'MONOCITOS\' en Orden #2. Valor: 3', 'App\\Models\\Resultado', 'created', 11, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":11,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":149,\"resultado\":\"3\",\"prueba_nombre_snapshot\":\"MONOCITOS\",\"valor_referencia_snapshot\":\"2 - 8\",\"unidades_snapshot\":\"%\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(17, 'Resultados', 'Resultado ingresado para \'BASÓFILOS\' en Orden #2. Valor: 1', 'App\\Models\\Resultado', 'created', 12, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":12,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":150,\"resultado\":\"1\",\"prueba_nombre_snapshot\":\"BAS\\u00d3FILOS\",\"valor_referencia_snapshot\":\"0 - 1\",\"unidades_snapshot\":\"%\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(18, 'Resultados', 'Resultado ingresado para \'PLAQUETAS\' en Orden #2. Valor: 149999', 'App\\Models\\Resultado', 'created', 13, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":13,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":151,\"resultado\":\"149999\",\"prueba_nombre_snapshot\":\"PLAQUETAS\",\"valor_referencia_snapshot\":\"150000 - 450000\",\"unidades_snapshot\":\"mm\\u00b3\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(19, 'Resultados', 'Resultado ingresado para \'V.P.M.\' en Orden #2. Valor: 6.5', 'App\\Models\\Resultado', 'created', 14, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":14,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":152,\"resultado\":\"6.5\",\"prueba_nombre_snapshot\":\"V.P.M.\",\"valor_referencia_snapshot\":\"6.5 - 11\",\"unidades_snapshot\":\"fL\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(20, 'Resultados', 'Resultado ingresado para \'P.D.W.\' en Orden #2. Valor: 10.5', 'App\\Models\\Resultado', 'created', 15, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":15,\"user_id\":1,\"detalle_orden_id\":6,\"prueba_id\":153,\"resultado\":\"10.5\",\"prueba_nombre_snapshot\":\"P.D.W.\",\"valor_referencia_snapshot\":\"10 - 14\",\"unidades_snapshot\":\"%\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(21, 'Resultados', 'Resultado ingresado para \'ACIDO URICO\' en Orden #2. Valor: 3.33', 'App\\Models\\Resultado', 'created', 16, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":16,\"user_id\":1,\"detalle_orden_id\":7,\"prueba_id\":183,\"resultado\":\"3.33\",\"prueba_nombre_snapshot\":\"ACIDO URICO\",\"valor_referencia_snapshot\":\"HOMBRE 3.4 - 7\",\"unidades_snapshot\":\"mg\\/dL\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(22, 'Resultados', 'Resultado ingresado para \'COLESTEROL TOTAL\' en Orden #2. Valor: 192', 'App\\Models\\Resultado', 'created', 17, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":17,\"user_id\":1,\"detalle_orden_id\":8,\"prueba_id\":190,\"resultado\":\"192\",\"prueba_nombre_snapshot\":\"COLESTEROL TOTAL\",\"valor_referencia_snapshot\":\"0 - 190\",\"unidades_snapshot\":\"mg\\/dL\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(23, 'Resultados', 'Resultado ingresado para \'CREATININA SERICA\' en Orden #2. Valor: 0.69', 'App\\Models\\Resultado', 'created', 18, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":18,\"user_id\":1,\"detalle_orden_id\":9,\"prueba_id\":193,\"resultado\":\"0.69\",\"prueba_nombre_snapshot\":\"CREATININA SERICA\",\"valor_referencia_snapshot\":\"HOMBRE 0.7 - 1.3\",\"unidades_snapshot\":\"mg\\/dl\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(24, 'Resultados', 'Resultado ingresado para \'GLUCOSA EN AYUNA\' en Orden #2. Valor: 59.9', 'App\\Models\\Resultado', 'created', 19, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":19,\"user_id\":1,\"detalle_orden_id\":10,\"prueba_id\":199,\"resultado\":\"59.9\",\"prueba_nombre_snapshot\":\"GLUCOSA EN AYUNA\",\"valor_referencia_snapshot\":\"60 - 110\",\"unidades_snapshot\":\"mg\\/dL\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(25, 'Resultados', 'Resultado ingresado para \'UREA\' en Orden #2. Valor: 10', 'App\\Models\\Resultado', 'created', 20, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":20,\"user_id\":1,\"detalle_orden_id\":11,\"prueba_id\":218,\"resultado\":\"10\",\"prueba_nombre_snapshot\":\"UREA\",\"valor_referencia_snapshot\":\"10 - 50\",\"unidades_snapshot\":\"mg\\/dL\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(26, 'Resultados', 'Resultado ingresado para \'NITROGENO UREICO (BUN)\' en Orden #2. Valor: 5', 'App\\Models\\Resultado', 'created', 21, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":21,\"user_id\":1,\"detalle_orden_id\":11,\"prueba_id\":219,\"resultado\":\"5\",\"prueba_nombre_snapshot\":\"NITROGENO UREICO (BUN)\",\"valor_referencia_snapshot\":\"7 - 24\",\"unidades_snapshot\":\"mg\\/dL\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(27, 'Resultados', 'Resultado ingresado para \'TRIGLICERIDOS\' en Orden #2. Valor: 150', 'App\\Models\\Resultado', 'created', 22, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":22,\"user_id\":1,\"detalle_orden_id\":12,\"prueba_id\":229,\"resultado\":\"150\",\"prueba_nombre_snapshot\":\"TRIGLICERIDOS\",\"valor_referencia_snapshot\":\"0 - 150\",\"unidades_snapshot\":\"mg\\/dL\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(28, 'Resultados', 'Resultado ingresado para \'COLOR\' en Orden #2. Valor: POSITIVO', 'App\\Models\\Resultado', 'created', 23, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":23,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":16,\"resultado\":\"POSITIVO\",\"prueba_nombre_snapshot\":\"COLOR\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(29, 'Resultados', 'Resultado ingresado para \'ASPECTO\' en Orden #2. Valor: POSITIVO', 'App\\Models\\Resultado', 'created', 24, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":24,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":17,\"resultado\":\"POSITIVO\",\"prueba_nombre_snapshot\":\"ASPECTO\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(30, 'Resultados', 'Resultado ingresado para \'PH\' en Orden #2. Valor: POSITIVO', 'App\\Models\\Resultado', 'created', 25, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":25,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":18,\"resultado\":\"POSITIVO\",\"prueba_nombre_snapshot\":\"PH\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(31, 'Resultados', 'Resultado ingresado para \'DENSIDAD\' en Orden #2. Valor: POSITIVO', 'App\\Models\\Resultado', 'created', 26, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":26,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":19,\"resultado\":\"POSITIVO\",\"prueba_nombre_snapshot\":\"DENSIDAD\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(32, 'Resultados', 'Resultado ingresado para \'GLUCOSA\' en Orden #2. Valor: POSITIVO', 'App\\Models\\Resultado', 'created', 27, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":27,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":20,\"resultado\":\"POSITIVO\",\"prueba_nombre_snapshot\":\"GLUCOSA\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(33, 'Resultados', 'Resultado ingresado para \'PROTEINA\' en Orden #2. Valor: POSITIVO', 'App\\Models\\Resultado', 'created', 28, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":28,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":21,\"resultado\":\"POSITIVO\",\"prueba_nombre_snapshot\":\"PROTEINA\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(34, 'Resultados', 'Resultado ingresado para \'CUERPO CETONICO\' en Orden #2. Valor: POSITIVO', 'App\\Models\\Resultado', 'created', 29, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":29,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":22,\"resultado\":\"POSITIVO\",\"prueba_nombre_snapshot\":\"CUERPO CETONICO\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(35, 'Resultados', 'Resultado ingresado para \'UROBILINOGENO\' en Orden #2. Valor: ERY/μL', 'App\\Models\\Resultado', 'created', 30, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":30,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":23,\"resultado\":\"ERY\\/\\u03bcL\",\"prueba_nombre_snapshot\":\"UROBILINOGENO\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(36, 'Resultados', 'Resultado ingresado para \'ESTERAZA LEUCOCITARIA\' en Orden #2. Valor: LEU/μL', 'App\\Models\\Resultado', 'created', 31, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":31,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":24,\"resultado\":\"LEU\\/\\u03bcL\",\"prueba_nombre_snapshot\":\"ESTERAZA LEUCOCITARIA\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(37, 'Resultados', 'Resultado ingresado para \'SANGRE OCULTA\' en Orden #2. Valor: AMARILLO', 'App\\Models\\Resultado', 'created', 32, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":32,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":25,\"resultado\":\"AMARILLO\",\"prueba_nombre_snapshot\":\"SANGRE OCULTA\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(38, 'Resultados', 'Resultado ingresado para \'NITRITOS\' en Orden #2. Valor: POSITIVO', 'App\\Models\\Resultado', 'created', 33, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":33,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":26,\"resultado\":\"POSITIVO\",\"prueba_nombre_snapshot\":\"NITRITOS\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(39, 'Resultados', 'Resultado ingresado para \'BILIRRUBINA\' en Orden #2. Valor: NEGATIVO', 'App\\Models\\Resultado', 'created', 34, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":34,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":27,\"resultado\":\"NEGATIVO\",\"prueba_nombre_snapshot\":\"BILIRRUBINA\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(40, 'Resultados', 'Resultado ingresado para \'ACIDO ASCORBICO\' en Orden #2. Valor: POSITIVO', 'App\\Models\\Resultado', 'created', 35, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":35,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":28,\"resultado\":\"POSITIVO\",\"prueba_nombre_snapshot\":\"ACIDO ASCORBICO\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(41, 'Resultados', 'Resultado ingresado para \'CRISTALES\' en Orden #2. Valor: POSITIVO', 'App\\Models\\Resultado', 'created', 36, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":36,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":29,\"resultado\":\"POSITIVO\",\"prueba_nombre_snapshot\":\"CRISTALES\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(42, 'Resultados', 'Resultado ingresado para \'CILINDROS\' en Orden #2. Valor: NO SE OBSERVAN', 'App\\Models\\Resultado', 'created', 37, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":37,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":30,\"resultado\":\"NO SE OBSERVAN\",\"prueba_nombre_snapshot\":\"CILINDROS\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(43, 'Resultados', 'Resultado ingresado para \'LEUCOCITOS\' en Orden #2. Valor: NO SE OBSERVAN', 'App\\Models\\Resultado', 'created', 38, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":38,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":31,\"resultado\":\"NO SE OBSERVAN\",\"prueba_nombre_snapshot\":\"LEUCOCITOS\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(44, 'Resultados', 'Resultado ingresado para \'HEMATÍES\' en Orden #2. Valor: NO SE OBSERVAN', 'App\\Models\\Resultado', 'created', 39, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":39,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":32,\"resultado\":\"NO SE OBSERVAN\",\"prueba_nombre_snapshot\":\"HEMAT\\u00cdES\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(45, 'Resultados', 'Resultado ingresado para \'CÉLULAS EPITELIALES\' en Orden #2. Valor: NO SE OBSERVAN', 'App\\Models\\Resultado', 'created', 40, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":40,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":33,\"resultado\":\"NO SE OBSERVAN\",\"prueba_nombre_snapshot\":\"C\\u00c9LULAS EPITELIALES\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(46, 'Resultados', 'Resultado ingresado para \'FILAMENTOS MUCOIDES\' en Orden #2. Valor: ERY/μL', 'App\\Models\\Resultado', 'created', 41, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":41,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":34,\"resultado\":\"ERY\\/\\u03bcL\",\"prueba_nombre_snapshot\":\"FILAMENTOS MUCOIDES\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(47, 'Resultados', 'Resultado ingresado para \'BACTERIAS\' en Orden #2. Valor: NEGATIVO', 'App\\Models\\Resultado', 'created', 42, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":42,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":35,\"resultado\":\"NEGATIVO\",\"prueba_nombre_snapshot\":\"BACTERIAS\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(48, 'Resultados', 'Resultado ingresado para \'OTROS\' en Orden #2. Valor: POSITIVO', 'App\\Models\\Resultado', 'created', 43, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":43,\"user_id\":1,\"detalle_orden_id\":13,\"prueba_id\":36,\"resultado\":\"POSITIVO\",\"prueba_nombre_snapshot\":\"OTROS\",\"valor_referencia_snapshot\":\"\",\"unidades_snapshot\":\"\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-21T04:02:10.000000Z\",\"updated_at\":\"2026-09-21T04:02:10.000000Z\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(49, 'Órdenes', 'La orden #2 ha sido actualizada', 'App\\Models\\Orden', 'updated', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"estado\":\"finalizado\"},\"old\":{\"estado\":\"en proceso\"}}', NULL, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(50, 'Órdenes', 'La orden #2 ha sido actualizada', 'App\\Models\\Orden', 'updated', 2, 'App\\Models\\User', 1, '{\"attributes\":{\"observaciones_por_area\":{\"COPROLOG\\u00cdA\":null,\"HEMATOLOG\\u00cdA\":null,\"QU\\u00cdMICA SANGU\\u00cdNEA\":null,\"UROAN\\u00c1LISIS\":null}},\"old\":{\"observaciones_por_area\":null}}', NULL, '2026-09-21 04:02:22', '2026-09-21 04:02:22'),
(51, 'Resultados', 'Resultado ingresado para \'T3 LIBRE (FT3)\' en Orden #1. Valor: 222222', 'App\\Models\\Resultado', 'created', 44, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":44,\"user_id\":1,\"detalle_orden_id\":1,\"prueba_id\":104,\"resultado\":\"222222\",\"prueba_nombre_snapshot\":\"T3 LIBRE (FT3)\",\"valor_referencia_snapshot\":\"2 - 4.4\",\"unidades_snapshot\":\"pg\\/mL\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-28T02:40:31.000000Z\",\"updated_at\":\"2026-09-28T02:40:31.000000Z\"}}', NULL, '2026-09-28 02:40:31', '2026-09-28 02:40:31'),
(52, 'Resultados', 'Resultado ingresado para \'T4 LIBRE (FT4)\' en Orden #1. Valor: 2222222', 'App\\Models\\Resultado', 'created', 45, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":45,\"user_id\":1,\"detalle_orden_id\":2,\"prueba_id\":107,\"resultado\":\"2222222\",\"prueba_nombre_snapshot\":\"T4 LIBRE (FT4)\",\"valor_referencia_snapshot\":\"0.98 - 1.71\",\"unidades_snapshot\":\"ng\\/dL\",\"valor_referencia_externo\":null,\"observaciones\":null,\"fuera_de_rango\":0,\"es_externo\":0,\"alertar\":false,\"created_at\":\"2026-09-28T02:40:31.000000Z\",\"updated_at\":\"2026-09-28T02:40:31.000000Z\"}}', NULL, '2026-09-28 02:40:31', '2026-09-28 02:40:31');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laboratorio_clinico_merino_cache_livewire-rate-limiter:a03482d47b45acecfdd4da52d94a0986080d88b3', 'i:1;', 1790562864),
('laboratorio_clinico_merino_cache_livewire-rate-limiter:a03482d47b45acecfdd4da52d94a0986080d88b3:timer', 'i:1790562864;', 1790562864),
('laboratorio_clinico_merino_cache_spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:204:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:20:\"ver_detalle_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:23:\"cambiar_estado_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:23:\"ver_expediente_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:19:\"access_cotizaciones\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:22:\"generar_pdf_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:23:\"enviar_cotizacion_email\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:20:\"ver_detalle_examenes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:24:\"agregar_pruebas_examenes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:23:\"cambiar_estado_examenes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:23:\"procesar_muestras_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:25:\"ingresar_resultados_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:24:\"imprimir_etiquetas_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:17:\"ver_pruebas_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:12:\"pausar_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:14:\"reanudar_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:15:\"finalizar_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:21:\"generar_reporte_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:14:\"cancelar_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:15:\"restaurar_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:23:\"cambiar_estado_perfiles\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:21:\"ver_pruebas_conjuntas\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:24:\"editar_pruebas_conjuntas\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:26:\"eliminar_pruebas_conjuntas\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:22:\"cambiar_estado_pruebas\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:28:\"cambiar_estado_tipo_examenes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:28:\"acceder_buscador_expedientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:25:\"imprimir_etiquetas_kanban\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:22:\"mover_etiquetas_kanban\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:21:\"cambiar_estado_grupos\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:16:\"ingresos_diarios\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:13:\"view_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:17:\"view_any_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:15:\"create_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:15:\"update_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:16:\"restore_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:20:\"restore_any_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:18:\"replicate_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:16:\"reorder_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:15:\"delete_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:19:\"delete_any_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:21:\"force_delete_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:25:\"force_delete_any_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:11:\"view_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:15:\"view_any_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:13:\"create_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:13:\"update_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:14:\"restore_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:18:\"restore_any_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:16:\"replicate_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:14:\"reorder_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:13:\"delete_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:17:\"delete_any_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:19:\"force_delete_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:23:\"force_delete_any_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:10:\"view_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:14:\"view_any_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:56;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:12:\"create_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:57;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:12:\"update_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:58;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:13:\"restore_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:59;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:17:\"restore_any_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:60;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:15:\"replicate_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:61;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:13:\"reorder_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:62;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:12:\"delete_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:63;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:16:\"delete_any_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:64;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:18:\"force_delete_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:65;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:22:\"force_delete_any_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:66;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:11:\"view_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:67;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:15:\"view_any_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:68;a:4:{s:1:\"a\";i:69;s:1:\"b\";s:13:\"create_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:69;a:4:{s:1:\"a\";i:70;s:1:\"b\";s:13:\"update_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:70;a:4:{s:1:\"a\";i:71;s:1:\"b\";s:14:\"restore_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:71;a:4:{s:1:\"a\";i:72;s:1:\"b\";s:18:\"restore_any_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:72;a:4:{s:1:\"a\";i:73;s:1:\"b\";s:16:\"replicate_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:73;a:4:{s:1:\"a\";i:74;s:1:\"b\";s:14:\"reorder_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:74;a:4:{s:1:\"a\";i:75;s:1:\"b\";s:13:\"delete_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:75;a:4:{s:1:\"a\";i:76;s:1:\"b\";s:17:\"delete_any_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:76;a:4:{s:1:\"a\";i:77;s:1:\"b\";s:19:\"force_delete_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:77;a:4:{s:1:\"a\";i:78;s:1:\"b\";s:23:\"force_delete_any_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:78;a:4:{s:1:\"a\";i:79;s:1:\"b\";s:9:\"view_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:79;a:4:{s:1:\"a\";i:80;s:1:\"b\";s:13:\"view_any_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:80;a:4:{s:1:\"a\";i:81;s:1:\"b\";s:11:\"create_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:81;a:4:{s:1:\"a\";i:82;s:1:\"b\";s:11:\"update_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:82;a:4:{s:1:\"a\";i:83;s:1:\"b\";s:12:\"restore_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:83;a:4:{s:1:\"a\";i:84;s:1:\"b\";s:16:\"restore_any_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:84;a:4:{s:1:\"a\";i:85;s:1:\"b\";s:14:\"replicate_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:85;a:4:{s:1:\"a\";i:86;s:1:\"b\";s:12:\"reorder_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:86;a:4:{s:1:\"a\";i:87;s:1:\"b\";s:11:\"delete_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:87;a:4:{s:1:\"a\";i:88;s:1:\"b\";s:15:\"delete_any_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:88;a:4:{s:1:\"a\";i:89;s:1:\"b\";s:17:\"force_delete_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:89;a:4:{s:1:\"a\";i:90;s:1:\"b\";s:21:\"force_delete_any_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:90;a:4:{s:1:\"a\";i:91;s:1:\"b\";s:17:\"view_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:91;a:4:{s:1:\"a\";i:92;s:1:\"b\";s:21:\"view_any_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:92;a:4:{s:1:\"a\";i:93;s:1:\"b\";s:19:\"create_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:93;a:4:{s:1:\"a\";i:94;s:1:\"b\";s:19:\"update_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:94;a:4:{s:1:\"a\";i:95;s:1:\"b\";s:20:\"restore_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:95;a:4:{s:1:\"a\";i:96;s:1:\"b\";s:24:\"restore_any_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:96;a:4:{s:1:\"a\";i:97;s:1:\"b\";s:22:\"replicate_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:97;a:4:{s:1:\"a\";i:98;s:1:\"b\";s:20:\"reorder_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:98;a:4:{s:1:\"a\";i:99;s:1:\"b\";s:19:\"delete_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:99;a:4:{s:1:\"a\";i:100;s:1:\"b\";s:23:\"delete_any_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:100;a:4:{s:1:\"a\";i:101;s:1:\"b\";s:25:\"force_delete_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:101;a:4:{s:1:\"a\";i:102;s:1:\"b\";s:29:\"force_delete_any_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:102;a:4:{s:1:\"a\";i:103;s:1:\"b\";s:9:\"view_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:103;a:4:{s:1:\"a\";i:104;s:1:\"b\";s:13:\"view_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:104;a:4:{s:1:\"a\";i:105;s:1:\"b\";s:11:\"create_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:105;a:4:{s:1:\"a\";i:106;s:1:\"b\";s:11:\"update_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:106;a:4:{s:1:\"a\";i:107;s:1:\"b\";s:12:\"restore_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:107;a:4:{s:1:\"a\";i:108;s:1:\"b\";s:16:\"restore_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:108;a:4:{s:1:\"a\";i:109;s:1:\"b\";s:14:\"replicate_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:109;a:4:{s:1:\"a\";i:110;s:1:\"b\";s:12:\"reorder_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:110;a:4:{s:1:\"a\";i:111;s:1:\"b\";s:11:\"delete_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:111;a:4:{s:1:\"a\";i:112;s:1:\"b\";s:15:\"delete_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:112;a:4:{s:1:\"a\";i:113;s:1:\"b\";s:17:\"force_delete_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:113;a:4:{s:1:\"a\";i:114;s:1:\"b\";s:21:\"force_delete_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:114;a:4:{s:1:\"a\";i:115;s:1:\"b\";s:18:\"view_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:115;a:4:{s:1:\"a\";i:116;s:1:\"b\";s:22:\"view_any_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:116;a:4:{s:1:\"a\";i:117;s:1:\"b\";s:20:\"create_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:117;a:4:{s:1:\"a\";i:118;s:1:\"b\";s:20:\"update_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:118;a:4:{s:1:\"a\";i:119;s:1:\"b\";s:21:\"restore_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:119;a:4:{s:1:\"a\";i:120;s:1:\"b\";s:25:\"restore_any_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:120;a:4:{s:1:\"a\";i:121;s:1:\"b\";s:23:\"replicate_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:121;a:4:{s:1:\"a\";i:122;s:1:\"b\";s:21:\"reorder_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:122;a:4:{s:1:\"a\";i:123;s:1:\"b\";s:20:\"delete_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:123;a:4:{s:1:\"a\";i:124;s:1:\"b\";s:24:\"delete_any_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:124;a:4:{s:1:\"a\";i:125;s:1:\"b\";s:26:\"force_delete_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:125;a:4:{s:1:\"a\";i:126;s:1:\"b\";s:30:\"force_delete_any_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:126;a:4:{s:1:\"a\";i:127;s:1:\"b\";s:11:\"view_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:127;a:4:{s:1:\"a\";i:128;s:1:\"b\";s:15:\"view_any_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:128;a:4:{s:1:\"a\";i:129;s:1:\"b\";s:13:\"create_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:129;a:4:{s:1:\"a\";i:130;s:1:\"b\";s:13:\"update_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:130;a:4:{s:1:\"a\";i:131;s:1:\"b\";s:14:\"restore_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:131;a:4:{s:1:\"a\";i:132;s:1:\"b\";s:18:\"restore_any_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:132;a:4:{s:1:\"a\";i:133;s:1:\"b\";s:16:\"replicate_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:133;a:4:{s:1:\"a\";i:134;s:1:\"b\";s:14:\"reorder_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:134;a:4:{s:1:\"a\";i:135;s:1:\"b\";s:13:\"delete_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:135;a:4:{s:1:\"a\";i:136;s:1:\"b\";s:17:\"delete_any_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:136;a:4:{s:1:\"a\";i:137;s:1:\"b\";s:19:\"force_delete_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:137;a:4:{s:1:\"a\";i:138;s:1:\"b\";s:23:\"force_delete_any_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:138;a:4:{s:1:\"a\";i:139;s:1:\"b\";s:15:\"view_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:139;a:4:{s:1:\"a\";i:140;s:1:\"b\";s:19:\"view_any_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:140;a:4:{s:1:\"a\";i:141;s:1:\"b\";s:17:\"create_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:141;a:4:{s:1:\"a\";i:142;s:1:\"b\";s:17:\"update_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:142;a:4:{s:1:\"a\";i:143;s:1:\"b\";s:18:\"restore_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:143;a:4:{s:1:\"a\";i:144;s:1:\"b\";s:22:\"restore_any_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:144;a:4:{s:1:\"a\";i:145;s:1:\"b\";s:20:\"replicate_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:145;a:4:{s:1:\"a\";i:146;s:1:\"b\";s:18:\"reorder_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:146;a:4:{s:1:\"a\";i:147;s:1:\"b\";s:17:\"delete_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:147;a:4:{s:1:\"a\";i:148;s:1:\"b\";s:21:\"delete_any_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:148;a:4:{s:1:\"a\";i:149;s:1:\"b\";s:23:\"force_delete_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:149;a:4:{s:1:\"a\";i:150;s:1:\"b\";s:27:\"force_delete_any_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:150;a:4:{s:1:\"a\";i:151;s:1:\"b\";s:18:\"view_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:151;a:4:{s:1:\"a\";i:152;s:1:\"b\";s:22:\"view_any_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:152;a:4:{s:1:\"a\";i:153;s:1:\"b\";s:20:\"create_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:153;a:4:{s:1:\"a\";i:154;s:1:\"b\";s:20:\"update_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:154;a:4:{s:1:\"a\";i:155;s:1:\"b\";s:21:\"restore_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:155;a:4:{s:1:\"a\";i:156;s:1:\"b\";s:25:\"restore_any_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:156;a:4:{s:1:\"a\";i:157;s:1:\"b\";s:23:\"replicate_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:157;a:4:{s:1:\"a\";i:158;s:1:\"b\";s:21:\"reorder_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:158;a:4:{s:1:\"a\";i:159;s:1:\"b\";s:20:\"delete_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:159;a:4:{s:1:\"a\";i:160;s:1:\"b\";s:24:\"delete_any_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:160;a:4:{s:1:\"a\";i:161;s:1:\"b\";s:26:\"force_delete_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:161;a:4:{s:1:\"a\";i:162;s:1:\"b\";s:30:\"force_delete_any_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:162;a:4:{s:1:\"a\";i:163;s:1:\"b\";s:12:\"view_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:163;a:4:{s:1:\"a\";i:164;s:1:\"b\";s:16:\"view_any_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:164;a:4:{s:1:\"a\";i:165;s:1:\"b\";s:14:\"create_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:165;a:4:{s:1:\"a\";i:166;s:1:\"b\";s:14:\"update_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:166;a:4:{s:1:\"a\";i:167;s:1:\"b\";s:15:\"restore_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:167;a:4:{s:1:\"a\";i:168;s:1:\"b\";s:19:\"restore_any_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:168;a:4:{s:1:\"a\";i:169;s:1:\"b\";s:17:\"replicate_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:169;a:4:{s:1:\"a\";i:170;s:1:\"b\";s:15:\"reorder_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:170;a:4:{s:1:\"a\";i:171;s:1:\"b\";s:14:\"delete_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:171;a:4:{s:1:\"a\";i:172;s:1:\"b\";s:18:\"delete_any_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:172;a:4:{s:1:\"a\";i:173;s:1:\"b\";s:20:\"force_delete_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:173;a:4:{s:1:\"a\";i:174;s:1:\"b\";s:24:\"force_delete_any_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:174;a:4:{s:1:\"a\";i:175;s:1:\"b\";s:11:\"view_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:175;a:4:{s:1:\"a\";i:176;s:1:\"b\";s:15:\"view_any_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:176;a:4:{s:1:\"a\";i:177;s:1:\"b\";s:13:\"create_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:177;a:4:{s:1:\"a\";i:178;s:1:\"b\";s:13:\"update_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:178;a:4:{s:1:\"a\";i:179;s:1:\"b\";s:14:\"restore_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:179;a:4:{s:1:\"a\";i:180;s:1:\"b\";s:18:\"restore_any_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:180;a:4:{s:1:\"a\";i:181;s:1:\"b\";s:16:\"replicate_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:181;a:4:{s:1:\"a\";i:182;s:1:\"b\";s:14:\"reorder_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:182;a:4:{s:1:\"a\";i:183;s:1:\"b\";s:13:\"delete_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:183;a:4:{s:1:\"a\";i:184;s:1:\"b\";s:17:\"delete_any_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:184;a:4:{s:1:\"a\";i:185;s:1:\"b\";s:19:\"force_delete_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:185;a:4:{s:1:\"a\";i:186;s:1:\"b\";s:23:\"force_delete_any_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:186;a:4:{s:1:\"a\";i:187;s:1:\"b\";s:17:\"view_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:187;a:4:{s:1:\"a\";i:188;s:1:\"b\";s:21:\"view_any_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:188;a:4:{s:1:\"a\";i:189;s:1:\"b\";s:19:\"create_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:189;a:4:{s:1:\"a\";i:190;s:1:\"b\";s:19:\"update_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:190;a:4:{s:1:\"a\";i:191;s:1:\"b\";s:20:\"restore_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:191;a:4:{s:1:\"a\";i:192;s:1:\"b\";s:24:\"restore_any_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:192;a:4:{s:1:\"a\";i:193;s:1:\"b\";s:22:\"replicate_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:193;a:4:{s:1:\"a\";i:194;s:1:\"b\";s:20:\"reorder_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:194;a:4:{s:1:\"a\";i:195;s:1:\"b\";s:19:\"delete_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:195;a:4:{s:1:\"a\";i:196;s:1:\"b\";s:23:\"delete_any_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:196;a:4:{s:1:\"a\";i:197;s:1:\"b\";s:25:\"force_delete_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:197;a:4:{s:1:\"a\";i:198;s:1:\"b\";s:29:\"force_delete_any_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:198;a:4:{s:1:\"a\";i:199;s:1:\"b\";s:16:\"impersonate_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:199;a:4:{s:1:\"a\";i:200;s:1:\"b\";s:18:\"access_admin_panel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:200;a:4:{s:1:\"a\";i:201;s:1:\"b\";s:15:\"manage_settings\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:201;a:4:{s:1:\"a\";i:202;s:1:\"b\";s:11:\"export_data\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:202;a:4:{s:1:\"a\";i:203;s:1:\"b\";s:11:\"import_data\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:203;a:4:{s:1:\"a\";i:204;s:1:\"b\";s:12:\"view_reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}}s:5:\"roles\";a:3:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:9:\"Recepcion\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:13:\"Laboratorista\";s:1:\"c\";s:3:\"web\";}}}', 1790649204),
('oncosavi_cache_livewire-rate-limiter:a03482d47b45acecfdd4da52d94a0986080d88b3', 'i:1;', 1790641615),
('oncosavi_cache_livewire-rate-limiter:a03482d47b45acecfdd4da52d94a0986080d88b3:timer', 'i:1790641615;', 1790641615),
('oncosavi_cache_spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:204:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:20:\"ver_detalle_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:23:\"cambiar_estado_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:23:\"ver_expediente_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:19:\"access_cotizaciones\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:22:\"generar_pdf_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:23:\"enviar_cotizacion_email\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:20:\"ver_detalle_examenes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:24:\"agregar_pruebas_examenes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:23:\"cambiar_estado_examenes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:23:\"procesar_muestras_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:25:\"ingresar_resultados_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:24:\"imprimir_etiquetas_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:17:\"ver_pruebas_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:12:\"pausar_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:14:\"reanudar_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:15:\"finalizar_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:21:\"generar_reporte_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:14:\"cancelar_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:15:\"restaurar_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:23:\"cambiar_estado_perfiles\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:21:\"ver_pruebas_conjuntas\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:24:\"editar_pruebas_conjuntas\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:26:\"eliminar_pruebas_conjuntas\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:22:\"cambiar_estado_pruebas\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:28:\"cambiar_estado_tipo_examenes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:28:\"acceder_buscador_expedientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:25:\"imprimir_etiquetas_kanban\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:22:\"mover_etiquetas_kanban\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:21:\"cambiar_estado_grupos\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:16:\"ingresos_diarios\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:13:\"view_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:17:\"view_any_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:15:\"create_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:15:\"update_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:16:\"restore_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:20:\"restore_any_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:18:\"replicate_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:16:\"reorder_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:15:\"delete_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:19:\"delete_any_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:21:\"force_delete_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:25:\"force_delete_any_clientes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:11:\"view_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:15:\"view_any_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:13:\"create_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:13:\"update_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:14:\"restore_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:18:\"restore_any_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:16:\"replicate_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:14:\"reorder_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:13:\"delete_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:17:\"delete_any_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:19:\"force_delete_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:23:\"force_delete_any_examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:10:\"view_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:14:\"view_any_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:56;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:12:\"create_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:57;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:12:\"update_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:58;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:13:\"restore_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:59;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:17:\"restore_any_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:60;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:15:\"replicate_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:61;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:13:\"reorder_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:62;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:12:\"delete_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:63;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:16:\"delete_any_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:64;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:18:\"force_delete_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:65;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:22:\"force_delete_any_orden\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:66;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:11:\"view_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:67;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:15:\"view_any_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:68;a:4:{s:1:\"a\";i:69;s:1:\"b\";s:13:\"create_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:69;a:4:{s:1:\"a\";i:70;s:1:\"b\";s:13:\"update_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:70;a:4:{s:1:\"a\";i:71;s:1:\"b\";s:14:\"restore_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:71;a:4:{s:1:\"a\";i:72;s:1:\"b\";s:18:\"restore_any_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:72;a:4:{s:1:\"a\";i:73;s:1:\"b\";s:16:\"replicate_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:73;a:4:{s:1:\"a\";i:74;s:1:\"b\";s:14:\"reorder_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:74;a:4:{s:1:\"a\";i:75;s:1:\"b\";s:13:\"delete_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:75;a:4:{s:1:\"a\";i:76;s:1:\"b\";s:17:\"delete_any_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:76;a:4:{s:1:\"a\";i:77;s:1:\"b\";s:19:\"force_delete_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:77;a:4:{s:1:\"a\";i:78;s:1:\"b\";s:23:\"force_delete_any_perfil\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:78;a:4:{s:1:\"a\";i:79;s:1:\"b\";s:9:\"view_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:79;a:4:{s:1:\"a\";i:80;s:1:\"b\";s:13:\"view_any_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:80;a:4:{s:1:\"a\";i:81;s:1:\"b\";s:11:\"create_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:81;a:4:{s:1:\"a\";i:82;s:1:\"b\";s:11:\"update_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:82;a:4:{s:1:\"a\";i:83;s:1:\"b\";s:12:\"restore_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:83;a:4:{s:1:\"a\";i:84;s:1:\"b\";s:16:\"restore_any_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:84;a:4:{s:1:\"a\";i:85;s:1:\"b\";s:14:\"replicate_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:85;a:4:{s:1:\"a\";i:86;s:1:\"b\";s:12:\"reorder_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:86;a:4:{s:1:\"a\";i:87;s:1:\"b\";s:11:\"delete_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:87;a:4:{s:1:\"a\";i:88;s:1:\"b\";s:15:\"delete_any_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:88;a:4:{s:1:\"a\";i:89;s:1:\"b\";s:17:\"force_delete_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:89;a:4:{s:1:\"a\";i:90;s:1:\"b\";s:21:\"force_delete_any_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:90;a:4:{s:1:\"a\";i:91;s:1:\"b\";s:17:\"view_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:91;a:4:{s:1:\"a\";i:92;s:1:\"b\";s:21:\"view_any_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:92;a:4:{s:1:\"a\";i:93;s:1:\"b\";s:19:\"create_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:93;a:4:{s:1:\"a\";i:94;s:1:\"b\";s:19:\"update_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:94;a:4:{s:1:\"a\";i:95;s:1:\"b\";s:20:\"restore_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:95;a:4:{s:1:\"a\";i:96;s:1:\"b\";s:24:\"restore_any_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:96;a:4:{s:1:\"a\";i:97;s:1:\"b\";s:22:\"replicate_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:97;a:4:{s:1:\"a\";i:98;s:1:\"b\";s:20:\"reorder_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:98;a:4:{s:1:\"a\";i:99;s:1:\"b\";s:19:\"delete_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:99;a:4:{s:1:\"a\";i:100;s:1:\"b\";s:23:\"delete_any_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:100;a:4:{s:1:\"a\";i:101;s:1:\"b\";s:25:\"force_delete_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:101;a:4:{s:1:\"a\";i:102;s:1:\"b\";s:29:\"force_delete_any_tipo::examen\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:102;a:4:{s:1:\"a\";i:103;s:1:\"b\";s:9:\"view_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:103;a:4:{s:1:\"a\";i:104;s:1:\"b\";s:13:\"view_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:104;a:4:{s:1:\"a\";i:105;s:1:\"b\";s:11:\"create_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:105;a:4:{s:1:\"a\";i:106;s:1:\"b\";s:11:\"update_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:106;a:4:{s:1:\"a\";i:107;s:1:\"b\";s:12:\"restore_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:107;a:4:{s:1:\"a\";i:108;s:1:\"b\";s:16:\"restore_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:108;a:4:{s:1:\"a\";i:109;s:1:\"b\";s:14:\"replicate_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:109;a:4:{s:1:\"a\";i:110;s:1:\"b\";s:12:\"reorder_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:110;a:4:{s:1:\"a\";i:111;s:1:\"b\";s:11:\"delete_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:111;a:4:{s:1:\"a\";i:112;s:1:\"b\";s:15:\"delete_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:112;a:4:{s:1:\"a\";i:113;s:1:\"b\";s:17:\"force_delete_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:113;a:4:{s:1:\"a\";i:114;s:1:\"b\";s:21:\"force_delete_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:114;a:4:{s:1:\"a\";i:115;s:1:\"b\";s:18:\"view_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:115;a:4:{s:1:\"a\";i:116;s:1:\"b\";s:22:\"view_any_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:116;a:4:{s:1:\"a\";i:117;s:1:\"b\";s:20:\"create_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:117;a:4:{s:1:\"a\";i:118;s:1:\"b\";s:20:\"update_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:118;a:4:{s:1:\"a\";i:119;s:1:\"b\";s:21:\"restore_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:119;a:4:{s:1:\"a\";i:120;s:1:\"b\";s:25:\"restore_any_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:120;a:4:{s:1:\"a\";i:121;s:1:\"b\";s:23:\"replicate_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:121;a:4:{s:1:\"a\";i:122;s:1:\"b\";s:21:\"reorder_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:122;a:4:{s:1:\"a\";i:123;s:1:\"b\";s:20:\"delete_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:123;a:4:{s:1:\"a\";i:124;s:1:\"b\";s:24:\"delete_any_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:124;a:4:{s:1:\"a\";i:125;s:1:\"b\";s:26:\"force_delete_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:125;a:4:{s:1:\"a\";i:126;s:1:\"b\";s:30:\"force_delete_any_activity::log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:126;a:4:{s:1:\"a\";i:127;s:1:\"b\";s:11:\"view_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:127;a:4:{s:1:\"a\";i:128;s:1:\"b\";s:15:\"view_any_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:128;a:4:{s:1:\"a\";i:129;s:1:\"b\";s:13:\"create_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:129;a:4:{s:1:\"a\";i:130;s:1:\"b\";s:13:\"update_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:130;a:4:{s:1:\"a\";i:131;s:1:\"b\";s:14:\"restore_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:131;a:4:{s:1:\"a\";i:132;s:1:\"b\";s:18:\"restore_any_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:132;a:4:{s:1:\"a\";i:133;s:1:\"b\";s:16:\"replicate_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:133;a:4:{s:1:\"a\";i:134;s:1:\"b\";s:14:\"reorder_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:134;a:4:{s:1:\"a\";i:135;s:1:\"b\";s:13:\"delete_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:135;a:4:{s:1:\"a\";i:136;s:1:\"b\";s:17:\"delete_any_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:136;a:4:{s:1:\"a\";i:137;s:1:\"b\";s:19:\"force_delete_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:137;a:4:{s:1:\"a\";i:138;s:1:\"b\";s:23:\"force_delete_any_codigo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:138;a:4:{s:1:\"a\";i:139;s:1:\"b\";s:15:\"view_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:139;a:4:{s:1:\"a\";i:140;s:1:\"b\";s:19:\"view_any_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:140;a:4:{s:1:\"a\";i:141;s:1:\"b\";s:17:\"create_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:141;a:4:{s:1:\"a\";i:142;s:1:\"b\";s:17:\"update_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:142;a:4:{s:1:\"a\";i:143;s:1:\"b\";s:18:\"restore_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:143;a:4:{s:1:\"a\";i:144;s:1:\"b\";s:22:\"restore_any_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:144;a:4:{s:1:\"a\";i:145;s:1:\"b\";s:20:\"replicate_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:145;a:4:{s:1:\"a\";i:146;s:1:\"b\";s:18:\"reorder_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:146;a:4:{s:1:\"a\";i:147;s:1:\"b\";s:17:\"delete_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:147;a:4:{s:1:\"a\";i:148;s:1:\"b\";s:21:\"delete_any_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:148;a:4:{s:1:\"a\";i:149;s:1:\"b\";s:23:\"force_delete_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:149;a:4:{s:1:\"a\";i:150;s:1:\"b\";s:27:\"force_delete_any_cotizacion\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:150;a:4:{s:1:\"a\";i:151;s:1:\"b\";s:18:\"view_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:151;a:4:{s:1:\"a\";i:152;s:1:\"b\";s:22:\"view_any_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:152;a:4:{s:1:\"a\";i:153;s:1:\"b\";s:20:\"create_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:153;a:4:{s:1:\"a\";i:154;s:1:\"b\";s:20:\"update_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:154;a:4:{s:1:\"a\";i:155;s:1:\"b\";s:21:\"restore_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:155;a:4:{s:1:\"a\";i:156;s:1:\"b\";s:25:\"restore_any_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:156;a:4:{s:1:\"a\";i:157;s:1:\"b\";s:23:\"replicate_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:157;a:4:{s:1:\"a\";i:158;s:1:\"b\";s:21:\"reorder_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:158;a:4:{s:1:\"a\";i:159;s:1:\"b\";s:20:\"delete_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:159;a:4:{s:1:\"a\";i:160;s:1:\"b\";s:24:\"delete_any_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:160;a:4:{s:1:\"a\";i:161;s:1:\"b\";s:26:\"force_delete_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:161;a:4:{s:1:\"a\";i:162;s:1:\"b\";s:30:\"force_delete_any_grupo::etario\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:162;a:4:{s:1:\"a\";i:163;s:1:\"b\";s:12:\"view_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:163;a:4:{s:1:\"a\";i:164;s:1:\"b\";s:16:\"view_any_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:164;a:4:{s:1:\"a\";i:165;s:1:\"b\";s:14:\"create_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:165;a:4:{s:1:\"a\";i:166;s:1:\"b\";s:14:\"update_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:166;a:4:{s:1:\"a\";i:167;s:1:\"b\";s:15:\"restore_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:167;a:4:{s:1:\"a\";i:168;s:1:\"b\";s:19:\"restore_any_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:168;a:4:{s:1:\"a\";i:169;s:1:\"b\";s:17:\"replicate_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:169;a:4:{s:1:\"a\";i:170;s:1:\"b\";s:15:\"reorder_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:170;a:4:{s:1:\"a\";i:171;s:1:\"b\";s:14:\"delete_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:171;a:4:{s:1:\"a\";i:172;s:1:\"b\";s:18:\"delete_any_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:172;a:4:{s:1:\"a\";i:173;s:1:\"b\";s:20:\"force_delete_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:173;a:4:{s:1:\"a\";i:174;s:1:\"b\";s:24:\"force_delete_any_muestra\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:174;a:4:{s:1:\"a\";i:175;s:1:\"b\";s:11:\"view_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:175;a:4:{s:1:\"a\";i:176;s:1:\"b\";s:15:\"view_any_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:176;a:4:{s:1:\"a\";i:177;s:1:\"b\";s:13:\"create_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:177;a:4:{s:1:\"a\";i:178;s:1:\"b\";s:13:\"update_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:178;a:4:{s:1:\"a\";i:179;s:1:\"b\";s:14:\"restore_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:179;a:4:{s:1:\"a\";i:180;s:1:\"b\";s:18:\"restore_any_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:180;a:4:{s:1:\"a\";i:181;s:1:\"b\";s:16:\"replicate_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:181;a:4:{s:1:\"a\";i:182;s:1:\"b\";s:14:\"reorder_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:182;a:4:{s:1:\"a\";i:183;s:1:\"b\";s:13:\"delete_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:183;a:4:{s:1:\"a\";i:184;s:1:\"b\";s:17:\"delete_any_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:184;a:4:{s:1:\"a\";i:185;s:1:\"b\";s:19:\"force_delete_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:185;a:4:{s:1:\"a\";i:186;s:1:\"b\";s:23:\"force_delete_any_prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:186;a:4:{s:1:\"a\";i:187;s:1:\"b\";s:17:\"view_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:187;a:4:{s:1:\"a\";i:188;s:1:\"b\";s:21:\"view_any_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:188;a:4:{s:1:\"a\";i:189;s:1:\"b\";s:19:\"create_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:189;a:4:{s:1:\"a\";i:190;s:1:\"b\";s:19:\"update_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:190;a:4:{s:1:\"a\";i:191;s:1:\"b\";s:20:\"restore_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:191;a:4:{s:1:\"a\";i:192;s:1:\"b\";s:24:\"restore_any_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:192;a:4:{s:1:\"a\";i:193;s:1:\"b\";s:22:\"replicate_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:193;a:4:{s:1:\"a\";i:194;s:1:\"b\";s:20:\"reorder_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:194;a:4:{s:1:\"a\";i:195;s:1:\"b\";s:19:\"delete_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:195;a:4:{s:1:\"a\";i:196;s:1:\"b\";s:23:\"delete_any_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:196;a:4:{s:1:\"a\";i:197;s:1:\"b\";s:25:\"force_delete_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:197;a:4:{s:1:\"a\";i:198;s:1:\"b\";s:29:\"force_delete_any_tipo::prueba\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:198;a:4:{s:1:\"a\";i:199;s:1:\"b\";s:16:\"impersonate_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:199;a:4:{s:1:\"a\";i:200;s:1:\"b\";s:18:\"access_admin_panel\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:200;a:4:{s:1:\"a\";i:201;s:1:\"b\";s:15:\"manage_settings\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:201;a:4:{s:1:\"a\";i:202;s:1:\"b\";s:11:\"export_data\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:202;a:4:{s:1:\"a\";i:203;s:1:\"b\";s:11:\"import_data\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:203;a:4:{s:1:\"a\";i:204;s:1:\"b\";s:12:\"view_reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}}s:5:\"roles\";a:3:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:9:\"Recepcion\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:13:\"Laboratorista\";s:1:\"c\";s:3:\"web\";}}}', 1790725039);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `NumeroExp` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `apellido` varchar(255) NOT NULL,
  `dui` varchar(10) DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `grupo_etario` varchar(255) DEFAULT NULL,
  `genero` varchar(255) NOT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clientes`
--

INSERT INTO `clientes` (`id`, `NumeroExp`, `nombre`, `apellido`, `dui`, `edad`, `fecha_nacimiento`, `grupo_etario`, `genero`, `telefono`, `correo`, `direccion`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'MD26001', 'Manuel Enrique', 'Dominguez Lopez', NULL, NULL, '1996-02-12', NULL, 'Masculino', '72001156', 'manuenrike@gmail.com', '5a Calle Oritente, Casa #63, Barrio El Santuario,San Vicente.', 'Activo', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `codigos`
--

CREATE TABLE `codigos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(255) NOT NULL,
  `tipo_descuento` varchar(255) NOT NULL,
  `valor_descuento` decimal(10,2) NOT NULL,
  `es_limitado` tinyint(1) NOT NULL DEFAULT 0,
  `limite_usos` int(11) DEFAULT NULL,
  `usos_actuales` int(11) NOT NULL DEFAULT 0,
  `tiene_vencimiento` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detalle_orden`
--

CREATE TABLE `detalle_orden` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `orden_id` bigint(20) UNSIGNED NOT NULL,
  `examen_id` bigint(20) UNSIGNED NOT NULL,
  `perfil_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre_examen` varchar(255) NOT NULL,
  `nombre_perfil` varchar(255) DEFAULT NULL,
  `precio_examen` decimal(10,2) NOT NULL,
  `precio_perfil` decimal(10,2) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `pruebas_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pruebas_snapshot`)),
  `muestras_recibidas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`muestras_recibidas`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `order_column` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detalle_orden`
--

INSERT INTO `detalle_orden` (`id`, `orden_id`, `examen_id`, `perfil_id`, `nombre_examen`, `nombre_perfil`, `precio_examen`, `precio_perfil`, `status`, `pruebas_snapshot`, `muestras_recibidas`, `created_at`, `updated_at`, `order_column`) VALUES
(1, 1, 51, 6, 'T3 LIBRE', 'PERFIL TIROIDEO LIBRE', 15.00, 35.00, 'quimica_sanguinea', '[{\"id\":104,\"nombre\":\"T3 LIBRE (FT3)\",\"tipo_conjunto\":null,\"tipo_prueba_id\":null,\"valores_referencia\":[{\"grupo_etario_id\":8,\"genero\":\"Ambos\",\"valor_min\":\"2.00\",\"valor_max\":\"4.40\",\"operador\":\"rango\",\"unidades\":\"pg\\/mL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":7,\"genero\":\"Ambos\",\"valor_min\":\"2.30\",\"valor_max\":\"4.20\",\"operador\":\"rango\",\"unidades\":\"pg\\/mL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":6,\"genero\":\"Ambos\",\"valor_min\":\"2.30\",\"valor_max\":\"4.20\",\"operador\":\"rango\",\"unidades\":\"pg\\/mL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":9,\"genero\":\"Ambos\",\"valor_min\":\"2.00\",\"valor_max\":\"4.40\",\"operador\":\"rango\",\"unidades\":\"pg\\/mL\",\"descriptivo\":null,\"nota\":null}]}]', '[21]', '2026-09-14 03:19:10', '2026-09-14 03:22:48', 1),
(2, 1, 53, 6, 'T4 LIBRE', 'PERFIL TIROIDEO LIBRE', 15.00, 35.00, 'quimica_sanguinea', '[{\"id\":107,\"nombre\":\"T4 LIBRE (FT4)\",\"tipo_conjunto\":null,\"tipo_prueba_id\":null,\"valores_referencia\":[{\"grupo_etario_id\":8,\"genero\":\"Ambos\",\"valor_min\":\"0.98\",\"valor_max\":\"1.71\",\"operador\":\"rango\",\"unidades\":\"ng\\/dL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":4,\"genero\":\"Ambos\",\"valor_min\":\"0.90\",\"valor_max\":\"2.30\",\"operador\":\"rango\",\"unidades\":\"ng\\/dL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":6,\"genero\":\"Ambos\",\"valor_min\":\"0.80\",\"valor_max\":\"1.80\",\"operador\":\"rango\",\"unidades\":\"ng\\/dL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":7,\"genero\":\"Ambos\",\"valor_min\":\"0.80\",\"valor_max\":\"1.60\",\"operador\":\"rango\",\"unidades\":\"ng\\/dL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":9,\"genero\":\"Ambos\",\"valor_min\":\"0.98\",\"valor_max\":\"1.71\",\"operador\":\"rango\",\"unidades\":\"ng\\/dL\",\"descriptivo\":null,\"nota\":null}]}]', '[21]', '2026-09-14 03:19:10', '2026-09-14 03:22:48', 2),
(3, 1, 57, 6, 'TSH 3RA GENERACION', 'PERFIL TIROIDEO LIBRE', 15.00, 35.00, 'quimica_sanguinea', '[{\"id\":109,\"nombre\":\"HORMONA ESTIMULANTES DE TIROIDES (TSH 3 GENERACION)\",\"tipo_conjunto\":null,\"tipo_prueba_id\":null,\"valores_referencia\":[{\"grupo_etario_id\":8,\"genero\":\"Ambos\",\"valor_min\":\"0.30\",\"valor_max\":\"4.20\",\"operador\":\"rango\",\"unidades\":\"uUI\\/mL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":7,\"genero\":\"Ambos\",\"valor_min\":\"0.40\",\"valor_max\":\"4.00\",\"operador\":\"rango\",\"unidades\":\"uUI\\/mL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"0.80\",\"valor_max\":\"8.20\",\"operador\":\"rango\",\"unidades\":\"uUI\\/mL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":6,\"genero\":\"Ambos\",\"valor_min\":\"0.60\",\"valor_max\":\"6.00\",\"operador\":\"rango\",\"unidades\":\"uUI\\/mL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"0.30\",\"valor_max\":\"4.20\",\"operador\":\"rango\",\"unidades\":\"uUI\\/mL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"0.30\",\"valor_max\":\"4.20\",\"operador\":\"rango\",\"unidades\":\"uUI\\/mL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":7,\"genero\":\"Ambos\",\"valor_min\":\"0.40\",\"valor_max\":\"4.00\",\"operador\":\"rango\",\"unidades\":\"uUI\\/mL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"0.80\",\"valor_max\":\"8.20\",\"operador\":\"rango\",\"unidades\":\"uUI\\/mL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":6,\"genero\":\"Ambos\",\"valor_min\":\"0.60\",\"valor_max\":\"6.00\",\"operador\":\"rango\",\"unidades\":\"uUI\\/mL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":8,\"genero\":\"Ambos\",\"valor_min\":\"0.30\",\"valor_max\":\"4.20\",\"operador\":\"rango\",\"unidades\":\"uUI\\/mL\",\"descriptivo\":null,\"nota\":null}]}]', '[21]', '2026-09-14 03:19:10', '2026-09-14 03:22:48', 3),
(4, 1, 160, NULL, 'GENERAL DE ORINA', NULL, 2.00, NULL, 'uroanalisis', '[{\"id\":16,\"nombre\":\"COLOR\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":17,\"nombre\":\"ASPECTO\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":18,\"nombre\":\"PH\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":19,\"nombre\":\"DENSIDAD\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":20,\"nombre\":\"GLUCOSA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":21,\"nombre\":\"PROTEINA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":22,\"nombre\":\"CUERPO CETONICO\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":23,\"nombre\":\"UROBILINOGENO\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":24,\"nombre\":\"ESTERAZA LEUCOCITARIA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":25,\"nombre\":\"SANGRE OCULTA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":26,\"nombre\":\"NITRITOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":27,\"nombre\":\"BILIRRUBINA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":28,\"nombre\":\"ACIDO ASCORBICO\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":29,\"nombre\":\"CRISTALES\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":30,\"nombre\":\"CILINDROS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":31,\"nombre\":\"LEUCOCITOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":32,\"nombre\":\"HEMAT\\u00cdES\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":33,\"nombre\":\"C\\u00c9LULAS EPITELIALES\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":34,\"nombre\":\"FILAMENTOS MUCOIDES\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":35,\"nombre\":\"BACTERIAS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":36,\"nombre\":\"OTROS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]}]', '[13]', '2026-09-14 03:19:10', '2026-09-14 03:22:48', 4),
(5, 2, 22, 2, 'GENERAL DE HECES', 'PERFIL DE RUTINA', 2.00, 25.00, 'coprologia', '[{\"id\":1,\"nombre\":\"COLOR\",\"tipo_conjunto\":null,\"tipo_prueba_id\":2,\"valores_referencia\":[]},{\"id\":3,\"nombre\":\"CONSISTENCIA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":2,\"valores_referencia\":[]},{\"id\":4,\"nombre\":\"RESTOS ALIMENTICIOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":2,\"valores_referencia\":[]},{\"id\":5,\"nombre\":\"SANGRE OCULTA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":2,\"valores_referencia\":[]},{\"id\":6,\"nombre\":\"MUCUS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":2,\"valores_referencia\":[]},{\"id\":7,\"nombre\":\"OTROS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":2,\"valores_referencia\":[]},{\"id\":8,\"nombre\":\"METAZOARIOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":9,\"nombre\":\"PROTOZOARIOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":10,\"nombre\":\"LEVADURAS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":11,\"nombre\":\"PARTICULAS DE GRASAS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":12,\"nombre\":\"MICROBIOTA INTESTINAL\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":13,\"nombre\":\"HEMATIES\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":14,\"nombre\":\"LEUCOCITOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":15,\"nombre\":\"OTROS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]}]', '[6]', '2026-09-21 03:46:32', '2026-09-21 03:46:48', 5),
(6, 2, 65, 2, 'HEMOGRAMA', 'PERFIL DE RUTINA', 5.00, 25.00, 'hematologia', '[{\"id\":139,\"nombre\":\"GL\\u00d3BULOS ROJOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":4,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"3800000.00\",\"valor_max\":\"5800000.00\",\"operador\":\"rango\",\"unidades\":\"mm\\u00b3\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"3700000.00\",\"valor_max\":\"5500000.00\",\"operador\":\"rango\",\"unidades\":\"mm\\u00b3\",\"descriptivo\":null,\"nota\":null}]},{\"id\":140,\"nombre\":\"HEMATOCRITO\",\"tipo_conjunto\":null,\"tipo_prueba_id\":4,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"37.00\",\"valor_max\":\"53.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"35.00\",\"valor_max\":\"40.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null}]},{\"id\":141,\"nombre\":\"HEMOGLOBINA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":4,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"12.00\",\"valor_max\":\"17.00\",\"operador\":\"rango\",\"unidades\":\"gr\\/dl\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"11.50\",\"valor_max\":\"13.50\",\"operador\":\"rango\",\"unidades\":\"gr\\/dl\",\"descriptivo\":null,\"nota\":null}]},{\"id\":142,\"nombre\":\"V.C.M.\",\"tipo_conjunto\":null,\"tipo_prueba_id\":4,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"80.00\",\"valor_max\":\"110.00\",\"operador\":\"rango\",\"unidades\":\"fL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"80.00\",\"valor_max\":\"110.00\",\"operador\":\"rango\",\"unidades\":\"fL\",\"descriptivo\":null,\"nota\":null}]},{\"id\":143,\"nombre\":\"H.C.M.\",\"tipo_conjunto\":null,\"tipo_prueba_id\":4,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"26.00\",\"valor_max\":\"38.00\",\"operador\":\"rango\",\"unidades\":\"pg\",\"descriptivo\":null,\"nota\":null}]},{\"id\":144,\"nombre\":\"C.H.C.M.\",\"tipo_conjunto\":null,\"tipo_prueba_id\":4,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"31.00\",\"valor_max\":\"37.00\",\"operador\":\"rango\",\"unidades\":\"gr\\/dl\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"31.00\",\"valor_max\":\"37.00\",\"operador\":\"rango\",\"unidades\":\"gr\\/dl\",\"descriptivo\":null,\"nota\":null}]},{\"id\":145,\"nombre\":\"GL\\u00d3BULOS BLANCOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":5,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"5000.00\",\"valor_max\":\"10000.00\",\"operador\":\"rango\",\"unidades\":\"mm\\u00b3\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"5000.00\",\"valor_max\":\"15000.00\",\"operador\":\"rango\",\"unidades\":\"mm\\u00b3\",\"descriptivo\":null,\"nota\":null}]},{\"id\":146,\"nombre\":\"NEUTR\\u00d3FILOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":5,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"50.00\",\"valor_max\":\"70.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null}]},{\"id\":147,\"nombre\":\"LINFOCITOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":5,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"20.00\",\"valor_max\":\"40.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"20.00\",\"valor_max\":\"40.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null}]},{\"id\":148,\"nombre\":\"EOSIN\\u00d3FILOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":5,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"0.00\",\"valor_max\":\"5.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"0.00\",\"valor_max\":\"5.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null}]},{\"id\":149,\"nombre\":\"MONOCITOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":5,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"2.00\",\"valor_max\":\"8.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"2.00\",\"valor_max\":\"8.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null}]},{\"id\":150,\"nombre\":\"BAS\\u00d3FILOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":5,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"0.00\",\"valor_max\":\"1.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"0.00\",\"valor_max\":\"1.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null}]},{\"id\":151,\"nombre\":\"PLAQUETAS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":6,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"150000.00\",\"valor_max\":\"450000.00\",\"operador\":\"rango\",\"unidades\":\"mm\\u00b3\",\"descriptivo\":null,\"nota\":null}]},{\"id\":152,\"nombre\":\"V.P.M.\",\"tipo_conjunto\":null,\"tipo_prueba_id\":6,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"6.50\",\"valor_max\":\"11.00\",\"operador\":\"rango\",\"unidades\":\"fL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"6.50\",\"valor_max\":\"11.00\",\"operador\":\"rango\",\"unidades\":\"fL\",\"descriptivo\":null,\"nota\":null}]},{\"id\":153,\"nombre\":\"P.D.W.\",\"tipo_conjunto\":null,\"tipo_prueba_id\":6,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"10.00\",\"valor_max\":\"14.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":5,\"genero\":\"Ambos\",\"valor_min\":\"10.00\",\"valor_max\":\"14.00\",\"operador\":\"rango\",\"unidades\":\"%\",\"descriptivo\":null,\"nota\":null}]},{\"id\":154,\"nombre\":\"OBSERVACIONES\",\"tipo_conjunto\":null,\"tipo_prueba_id\":null,\"valores_referencia\":[]}]', '[15]', '2026-09-21 03:46:32', '2026-09-21 03:46:48', 6),
(7, 2, 114, 2, 'ACIDO ÚRICO', 'PERFIL DE RUTINA', 4.00, 25.00, 'quimica_sanguinea', '[{\"id\":183,\"nombre\":\"ACIDO URICO\",\"tipo_conjunto\":null,\"tipo_prueba_id\":null,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Masculino\",\"valor_min\":\"3.40\",\"valor_max\":\"7.00\",\"operador\":\"rango\",\"unidades\":\"mg\\/dL\",\"descriptivo\":\"HOMBRE\",\"nota\":null},{\"grupo_etario_id\":10,\"genero\":\"Femenino\",\"valor_min\":\"2.40\",\"valor_max\":\"5.70\",\"operador\":\"rango\",\"unidades\":\"mg\\/dL\",\"descriptivo\":\"MUJER\",\"nota\":null}]}]', '[21]', '2026-09-21 03:46:32', '2026-09-21 03:46:48', 7),
(8, 2, 124, 2, 'COLESTEROL TOTAL', 'PERFIL DE RUTINA', 4.00, 25.00, 'quimica_sanguinea', '[{\"id\":190,\"nombre\":\"COLESTEROL TOTAL\",\"tipo_conjunto\":null,\"tipo_prueba_id\":null,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"0.00\",\"valor_max\":\"190.00\",\"operador\":\"rango\",\"unidades\":\"mg\\/dL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":7,\"genero\":\"Ambos\",\"valor_min\":\"0.00\",\"valor_max\":\"170.00\",\"operador\":\"rango\",\"unidades\":\"mg\\/dL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":6,\"genero\":\"Ambos\",\"valor_min\":\"0.00\",\"valor_max\":\"170.00\",\"operador\":\"rango\",\"unidades\":\"mg\\/dL\",\"descriptivo\":null,\"nota\":null},{\"grupo_etario_id\":9,\"genero\":\"Ambos\",\"valor_min\":\"0.00\",\"valor_max\":\"190.00\",\"operador\":\"rango\",\"unidades\":\"mg\\/dL\",\"descriptivo\":null,\"nota\":null}]}]', '[21]', '2026-09-21 03:46:32', '2026-09-21 03:46:48', 8),
(9, 2, 127, 2, 'CREATININA', 'PERFIL DE RUTINA', 4.00, 25.00, 'quimica_sanguinea', '[{\"id\":193,\"nombre\":\"CREATININA SERICA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":null,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Masculino\",\"valor_min\":\"0.70\",\"valor_max\":\"1.30\",\"operador\":\"rango\",\"unidades\":\"mg\\/dl\",\"descriptivo\":\"HOMBRE\",\"nota\":null},{\"grupo_etario_id\":10,\"genero\":\"Femenino\",\"valor_min\":\"0.60\",\"valor_max\":\"1.10\",\"operador\":\"rango\",\"unidades\":\"mg\\/dl\",\"descriptivo\":\"MUJER\",\"nota\":null},{\"grupo_etario_id\":6,\"genero\":\"Ambos\",\"valor_min\":\"0.50\",\"valor_max\":\"1.00\",\"operador\":\"rango\",\"unidades\":\"mg\\/dl\",\"descriptivo\":\"NI\\u00d1OS\",\"nota\":null},{\"grupo_etario_id\":7,\"genero\":\"Ambos\",\"valor_min\":\"0.50\",\"valor_max\":\"1.00\",\"operador\":\"rango\",\"unidades\":null,\"descriptivo\":null,\"nota\":null}]}]', '[21]', '2026-09-21 03:46:32', '2026-09-21 03:46:48', 9),
(10, 2, 134, 2, 'GLUCOSA', 'PERFIL DE RUTINA', 3.00, 25.00, 'quimica_sanguinea', '[{\"id\":199,\"nombre\":\"GLUCOSA EN AYUNA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":null,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"60.00\",\"valor_max\":\"110.00\",\"operador\":\"rango\",\"unidades\":\"mg\\/dL\",\"descriptivo\":null,\"nota\":null}]}]', '[21]', '2026-09-21 03:46:32', '2026-09-21 03:46:48', 10),
(11, 2, 143, 2, 'NITRÓGENO UREICO', 'PERFIL DE RUTINA', 4.00, 25.00, 'quimica_sanguinea', '[{\"id\":218,\"nombre\":\"UREA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":null,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"10.00\",\"valor_max\":\"50.00\",\"operador\":\"rango\",\"unidades\":\"mg\\/dL\",\"descriptivo\":null,\"nota\":null}]},{\"id\":219,\"nombre\":\"NITROGENO UREICO (BUN)\",\"tipo_conjunto\":null,\"tipo_prueba_id\":null,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"7.00\",\"valor_max\":\"24.00\",\"operador\":\"rango\",\"unidades\":\"mg\\/dL\",\"descriptivo\":null,\"nota\":null}]}]', '[21]', '2026-09-21 03:46:32', '2026-09-21 03:46:48', 11),
(12, 2, 149, 2, 'TRIGLICÉRIDOS', 'PERFIL DE RUTINA', 4.00, 25.00, 'quimica_sanguinea', '[{\"id\":229,\"nombre\":\"TRIGLICERIDOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":null,\"valores_referencia\":[{\"grupo_etario_id\":10,\"genero\":\"Ambos\",\"valor_min\":\"0.00\",\"valor_max\":\"150.00\",\"operador\":\"rango\",\"unidades\":\"mg\\/dL\",\"descriptivo\":null,\"nota\":null}]}]', '[21]', '2026-09-21 03:46:32', '2026-09-21 03:46:48', 12),
(13, 2, 160, 2, 'GENERAL DE ORINA', 'PERFIL DE RUTINA', 2.00, 25.00, 'uroanalisis', '[{\"id\":16,\"nombre\":\"COLOR\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":17,\"nombre\":\"ASPECTO\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":18,\"nombre\":\"PH\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":19,\"nombre\":\"DENSIDAD\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":20,\"nombre\":\"GLUCOSA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":21,\"nombre\":\"PROTEINA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":22,\"nombre\":\"CUERPO CETONICO\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":23,\"nombre\":\"UROBILINOGENO\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":24,\"nombre\":\"ESTERAZA LEUCOCITARIA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":25,\"nombre\":\"SANGRE OCULTA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":26,\"nombre\":\"NITRITOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":27,\"nombre\":\"BILIRRUBINA\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":28,\"nombre\":\"ACIDO ASCORBICO\",\"tipo_conjunto\":null,\"tipo_prueba_id\":3,\"valores_referencia\":[]},{\"id\":29,\"nombre\":\"CRISTALES\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":30,\"nombre\":\"CILINDROS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":31,\"nombre\":\"LEUCOCITOS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":32,\"nombre\":\"HEMAT\\u00cdES\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":33,\"nombre\":\"C\\u00c9LULAS EPITELIALES\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":34,\"nombre\":\"FILAMENTOS MUCOIDES\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":35,\"nombre\":\"BACTERIAS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]},{\"id\":36,\"nombre\":\"OTROS\",\"tipo_conjunto\":null,\"tipo_prueba_id\":1,\"valores_referencia\":[]}]', '[13]', '2026-09-21 03:46:32', '2026-09-21 03:46:48', 13);

-- --------------------------------------------------------

--
-- Table structure for table `detalle_orden_perfils`
--

CREATE TABLE `detalle_orden_perfils` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `orden_id` bigint(20) UNSIGNED NOT NULL,
  `perfil_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detalle_perfil`
--

CREATE TABLE `detalle_perfil` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `perfil_id` bigint(20) UNSIGNED NOT NULL,
  `examen_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detalle_perfil`
--

INSERT INTO `detalle_perfil` (`id`, `perfil_id`, `examen_id`, `created_at`, `updated_at`) VALUES
(1, 1, 117, NULL, NULL),
(2, 1, 119, NULL, NULL),
(3, 1, 132, NULL, NULL),
(4, 1, 147, NULL, NULL),
(5, 1, 148, NULL, NULL),
(6, 2, 22, NULL, NULL),
(7, 2, 160, NULL, NULL),
(8, 2, 65, NULL, NULL),
(9, 2, 114, NULL, NULL),
(10, 2, 124, NULL, NULL),
(11, 2, 127, NULL, NULL),
(12, 2, 134, NULL, NULL),
(13, 2, 143, NULL, NULL),
(14, 2, 149, NULL, NULL),
(15, 3, 33, NULL, NULL),
(16, 3, 34, NULL, NULL),
(17, 3, 65, NULL, NULL),
(18, 3, 114, NULL, NULL),
(19, 3, 127, NULL, NULL),
(20, 3, 143, NULL, NULL),
(21, 3, 160, NULL, NULL),
(22, 4, 65, NULL, NULL),
(23, 4, 103, NULL, NULL),
(24, 4, 100, NULL, NULL),
(25, 4, 102, NULL, NULL),
(26, 4, 105, NULL, NULL),
(27, 4, 134, NULL, NULL),
(28, 4, 160, NULL, NULL),
(29, 5, 52, NULL, NULL),
(30, 5, 54, NULL, NULL),
(31, 5, 57, NULL, NULL),
(32, 6, 51, NULL, NULL),
(33, 6, 53, NULL, NULL),
(34, 6, 57, NULL, NULL),
(35, 7, 29, NULL, NULL),
(36, 7, 30, NULL, NULL),
(37, 7, 31, NULL, NULL),
(38, 7, 32, NULL, NULL),
(39, 7, 33, NULL, NULL),
(40, 7, 34, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `examens`
--

CREATE TABLE `examens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo_examen_id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `es_externo` tinyint(1) NOT NULL DEFAULT 0,
  `precio` decimal(10,2) NOT NULL,
  `recipiente` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `examens`
--

INSERT INTO `examens` (`id`, `tipo_examen_id`, `nombre`, `es_externo`, `precio`, `recipiente`, `estado`, `created_at`, `updated_at`) VALUES
(1, 1, 'BACILOSCOPIA-BAAR', 0, 10.00, 'uroanalisis', 1, NULL, NULL),
(2, 1, 'COLORACION GRAM (FROTIS VAGINAL)', 0, 20.00, 'cultivo_secreciones', 1, NULL, NULL),
(3, 1, 'COLORACIÓN DE GRAM', 0, 10.00, 'uroanalisis', 1, NULL, NULL),
(4, 1, 'COPROCULTIVO', 0, 12.00, 'coprologia', 1, NULL, NULL),
(5, 1, 'CULTIVO DE HONGOS', 0, 20.00, 'cultivo_secreciones', 1, NULL, NULL),
(6, 1, 'CULTIVO DE SECRECIONES', 0, 15.00, 'coprologia', 1, NULL, NULL),
(7, 1, 'DIRECTO KOH', 0, 15.00, 'cultivo_secreciones', 1, NULL, NULL),
(8, 1, 'ESPERMOGRAMA', 0, 20.00, 'uroanalisis', 1, NULL, NULL),
(9, 1, 'UROCULTIVO', 0, 10.00, 'uroanalisis', 1, NULL, NULL),
(10, 2, 'ANTICOAGULANTE LUPICO (CUALITATIVO)', 0, 40.00, 'cuagulacion', 1, NULL, NULL),
(11, 2, 'DIMERO-D', 1, 50.00, 'cuagulacion', 1, NULL, NULL),
(12, 2, 'FIBRINÓGENO', 1, 15.00, 'cuagulacion', 1, NULL, NULL),
(13, 2, 'RETRACCIÓN DE COAGULO', 0, 20.00, 'cuagulacion', 1, NULL, NULL),
(14, 2, 'TIEMPO DE COAGULACIÓN', 0, 10.00, 'cuagulacion', 1, NULL, NULL),
(15, 2, 'TIEMPO DE SANGRAMIENTO', 0, 10.00, 'cuagulacion', 1, NULL, NULL),
(16, 2, 'TIEMPO DE TROMB. PARCIAL ACT.', 0, 12.00, 'cuagulacion', 1, NULL, NULL),
(17, 2, 'TIEMPO DE TROMBINA', 1, 12.00, 'cuagulacion', 1, NULL, NULL),
(18, 2, 'TIEMPO Y VALOR DE PROTROMBINA', 0, 10.00, 'cuagulacion', 1, NULL, NULL),
(19, 3, 'AG. SALMONELLA TYPHI', 0, 20.00, 'coprologia', 1, NULL, NULL),
(20, 3, 'AZUL DE METILENO', 0, 10.00, 'coprologia', 1, NULL, NULL),
(21, 3, 'CONCENTRADO EN HECES', 1, 8.00, 'coprologia', 1, NULL, NULL),
(22, 3, 'GENERAL DE HECES', 0, 2.00, 'coprologia', 1, NULL, NULL),
(23, 3, 'HELICOBACTER PYLORI-AG', 0, 15.00, 'coprologia', 1, NULL, NULL),
(24, 3, 'IGM TIFOIDEA (SALMONELLA TYPHI - SALMONELLA PARATYPHI)', 0, 30.00, 'coprologia', 1, NULL, NULL),
(25, 3, 'PH EN HECES', 0, 20.00, 'coprologia', 1, NULL, NULL),
(26, 3, 'ROTAVIRUS EN HECES', 1, 25.00, 'coprologia', 1, NULL, NULL),
(27, 3, 'SANGRE OCULTA', 0, 15.00, 'coprologia', 1, NULL, NULL),
(28, 3, 'SUSTANCIA REDUCTORA', 0, 20.00, 'coprologia', 1, NULL, NULL),
(29, 4, 'CALCIO', 0, 8.00, 'quimica_sanguinea', 1, NULL, NULL),
(30, 4, 'CLORO', 0, 8.00, 'quimica_sanguinea', 1, NULL, NULL),
(31, 4, 'FÓSFORO', 0, 8.00, 'quimica_sanguinea', 1, NULL, NULL),
(32, 4, 'MAGNESIO', 0, 8.00, 'quimica_sanguinea', 1, NULL, NULL),
(33, 4, 'POTASIO', 0, 8.00, 'quimica_sanguinea', 1, NULL, NULL),
(34, 4, 'SODIO', 0, 8.00, 'quimica_sanguinea', 1, NULL, NULL),
(35, 5, 'AC. ANTITIROGLOBULINICOS (ATT)', 1, 40.00, 'quimica_sanguinea', 1, NULL, NULL),
(36, 5, 'ACTH (HORMONA ADRENOCORTICOTROPICA)', 0, 50.00, 'quimica_sanguinea', 1, NULL, NULL),
(37, 5, 'ANTI CCP (PÉPTIDO CÍCLICO CITRULINADO)', 1, 80.00, 'quimica_sanguinea', 1, NULL, NULL),
(38, 5, 'B-HCG-CUANT', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(39, 5, 'CORTISOL AM', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(40, 5, 'CORTISOL PM', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(41, 5, 'ESTRADIOL (E2)', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(42, 5, 'FSH (HORMONA FOLÍCULO ESTIMULANTE)', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(43, 5, 'HORMONA DE CRECIMIENTO', 1, 50.00, 'quimica_sanguinea', 1, NULL, NULL),
(44, 5, 'HORMONA PARATIROIDEA PHT', 0, 50.00, 'quimica_sanguinea', 1, NULL, NULL),
(45, 5, 'INSULINA', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(46, 5, 'INSULINA POST-PRANDIAL', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(47, 5, 'LH', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(48, 5, 'LH (HORMONA LUTEINIZANTE)', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(49, 5, 'PROGESTERONA', 0, 50.00, 'quimica_sanguinea', 1, NULL, NULL),
(50, 5, 'PROLACTINA', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(51, 5, 'T3 LIBRE', 0, 15.00, 'quimica_sanguinea', 1, NULL, NULL),
(52, 5, 'T3 TOTAL', 0, 12.00, 'quimica_sanguinea', 1, NULL, NULL),
(53, 5, 'T4 LIBRE', 0, 15.00, 'quimica_sanguinea', 1, NULL, NULL),
(54, 5, 'T4 TOTAL', 0, 12.00, 'quimica_sanguinea', 1, NULL, NULL),
(55, 5, 'TESTOSTERONA', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(56, 5, 'TIROGLOBULINAS', 1, 70.00, 'quimica_sanguinea', 1, NULL, NULL),
(57, 5, 'TSH 3RA GENERACION', 0, 15.00, 'quimica_sanguinea', 1, NULL, NULL),
(58, 6, 'CONCENTRADO STRAUT (T.CRUZI)', 0, 15.00, 'hematologia', 1, NULL, NULL),
(59, 6, 'CÉLULAS L.E.', 0, 25.00, 'hematologia', 1, NULL, NULL),
(60, 6, 'EOSINÓFILOS EN SANGRE', 0, 10.00, 'hematologia', 1, NULL, NULL),
(61, 6, 'EOSINÓFILOS NASALES', 0, 10.00, 'hematologia', 1, NULL, NULL),
(62, 6, 'ERITROSEDIMENTACIÓN', 0, 6.00, 'hematologia', 1, NULL, NULL),
(63, 6, 'FROTIS DE SANGRE PERIFÉRICA', 0, 10.00, 'hematologia', 1, NULL, NULL),
(64, 6, 'HB Y HT', 0, 5.00, 'hematologia', 1, NULL, NULL),
(65, 6, 'HEMOGRAMA', 0, 5.00, 'hematologia', 1, NULL, NULL),
(66, 6, 'LEUCOGRAMA', 0, 5.00, 'hematologia', 1, NULL, NULL),
(67, 6, 'PLAQUETAS', 0, 5.00, 'hematologia', 1, NULL, NULL),
(68, 6, 'PLASMODIUM (GOTA GRUESA)', 0, 15.00, 'hematologia', 1, NULL, NULL),
(69, 6, 'RETICULOCITOS', 0, 10.00, 'hematologia', 1, NULL, NULL),
(70, 7, 'AC. ANTI- TRYPANOSOMA CRUZI TOTALES (CHAGAS)', 1, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(71, 7, 'AC. ANTI-TIROIDEOPEROXIDADA', 1, 50.00, 'quimica_sanguinea', 1, NULL, NULL),
(72, 7, 'AC. ANTICITRULINADOS', 1, 50.00, 'quimica_sanguinea', 1, NULL, NULL),
(73, 7, 'ANTI-CARDIOLIPINASIGM', 1, 60.00, 'quimica_sanguinea', 1, NULL, NULL),
(74, 7, 'ANTI-MULLERIANA', 0, 150.00, 'quimica_sanguinea', 1, NULL, NULL),
(75, 7, 'ANTIESTREPTOLISINA O (ASO)', 0, 10.00, 'quimica_sanguinea', 1, NULL, NULL),
(76, 7, 'ANTIGENO COVID-19', 0, 15.00, 'quimica_sanguinea', 1, NULL, NULL),
(77, 7, 'ANTIMIOTICONDRIALESIGG', 1, 60.00, 'quimica_sanguinea', 1, NULL, NULL),
(78, 7, 'ANTINUCLEARES AC (ANA)', 1, 40.00, 'quimica_sanguinea', 1, NULL, NULL),
(79, 7, 'ANTÍGENOS FEBRILES', 0, 10.00, 'quimica_sanguinea', 1, NULL, NULL),
(80, 7, 'CHLAMYDIA TRACHOMATIS – AG', 0, 25.00, 'cultivo_secreciones', 1, NULL, NULL),
(81, 7, 'DEHIDROEPIANDROSTERONA SULFATO (DHEA-SO4)', 1, 110.00, 'quimica_sanguinea', 1, NULL, NULL),
(82, 7, 'DENGUE IGG/IGM+AG(DUO)', 0, 25.00, 'quimica_sanguinea', 1, NULL, NULL),
(83, 7, 'FACTOR REUMATOIDEO (LATEX RA)', 0, 10.00, 'quimica_sanguinea', 1, NULL, NULL),
(84, 7, 'FTA - ABS (TREPONEMA)', 1, 130.00, 'quimica_sanguinea', 1, NULL, NULL),
(85, 7, 'GONORREA – AG.', 0, 25.00, 'cultivo_secreciones', 1, NULL, NULL),
(86, 7, 'HELICOBACTER PYLORI AC. IGG', 0, 15.00, 'quimica_sanguinea', 1, NULL, NULL),
(87, 7, 'HEPATITIS A AC. IGM', 0, 40.00, 'quimica_sanguinea', 1, NULL, NULL),
(88, 7, 'HEPATITIS B AG. DE SUPERFICIE', 0, 40.00, 'quimica_sanguinea', 1, NULL, NULL),
(89, 7, 'HEPATITIS C AC', 0, 40.00, 'quimica_sanguinea', 1, NULL, NULL),
(90, 7, 'HERPES IGM (TIPO II)', 1, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(91, 7, 'IGE TOTAL', 1, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(92, 7, 'INMUNOGLOBULINASIGA (MICROSOMAL)', 1, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(93, 7, 'INMUNOGLOBULINASIGE', 1, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(94, 7, 'INMUNOGLOBULINASIGG (MICROSOMAL)', 1, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(95, 7, 'INMUNOGLOBULINASIGM (MICROSOMAL)', 1, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(96, 7, 'MONOTEST', 1, 25.00, 'quimica_sanguinea', 1, NULL, NULL),
(97, 7, 'PROCALCITONINA', 1, 80.00, 'quimica_sanguinea', 1, NULL, NULL),
(98, 7, 'PROTEÍNA C REACTIVA', 0, 10.00, 'quimica_sanguinea', 1, NULL, NULL),
(99, 7, 'PRUEBA DE EMBARAZO SANGRE', 0, 7.00, 'quimica_sanguinea', 1, NULL, NULL),
(100, 7, 'TIPEO SANGUÍNEO Y FACTOR RH', 0, 5.00, 'quimica_sanguinea', 1, NULL, NULL),
(101, 7, 'TOXOPLASMA GONDII IGG', 0, 25.00, 'quimica_sanguinea', 1, NULL, NULL),
(102, 7, 'TOXOPLASMA GONDII IGM', 0, 25.00, 'quimica_sanguinea', 1, NULL, NULL),
(103, 7, 'VDRL (PRUEBA DE SIFILIS)', 0, 10.00, 'quimica_sanguinea', 1, NULL, NULL),
(104, 7, 'VIH AC. (3A GENERACIÓN)', 0, 30.00, 'quimica_sanguinea', 1, NULL, NULL),
(105, 7, 'VIH PRUEBA RAPIDA', 0, 10.00, 'quimica_sanguinea', 1, NULL, NULL),
(106, 8, 'ALFA FETO PROTEINA', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(107, 8, 'CA 125', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(108, 8, 'CA 15-3', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(109, 8, 'CA 19-9', 1, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(110, 8, 'CEA AG. CARCIOEMBRIONARIO', 1, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(111, 8, 'PSA LIBRE', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(112, 8, 'PSA TOTAL', 0, 25.00, 'quimica_sanguinea', 1, NULL, NULL),
(113, 8, 'RELACIÓN PSA TOTAL/LIBRE', 0, 60.00, 'quimica_sanguinea', 1, NULL, NULL),
(114, 9, 'ACIDO ÚRICO', 0, 4.00, 'quimica_sanguinea', 1, NULL, NULL),
(115, 9, 'ALBUMINA', 0, 8.00, 'quimica_sanguinea', 1, NULL, NULL),
(116, 9, 'AMILASA', 0, 12.00, 'quimica_sanguinea', 1, NULL, NULL),
(117, 9, 'BILIRRUBINA DIRECTA', 0, 6.00, 'quimica_sanguinea', 1, NULL, NULL),
(118, 9, 'BILIRRUBINA INDIRECTA', 0, 6.00, 'quimica_sanguinea', 1, NULL, NULL),
(119, 9, 'BILIRRUBINA TOTAL', 0, 6.00, 'quimica_sanguinea', 1, NULL, NULL),
(120, 9, 'CITOMEGALOVIRUS IGM', 0, 30.00, 'quimica_sanguinea', 1, NULL, NULL),
(121, 9, 'COLESTEROL ALTA DENSIDAD - HDL', 0, 6.00, 'quimica_sanguinea', 1, NULL, NULL),
(122, 9, 'COLESTEROL BAJA DENSIDAD - LDL', 0, 6.00, 'quimica_sanguinea', 1, NULL, NULL),
(123, 9, 'COLESTEROL MUY BAJA DENSIDAD (VLDL CALCULADO)', 1, 12.00, 'quimica_sanguinea', 1, NULL, NULL),
(124, 9, 'COLESTEROL TOTAL', 0, 4.00, 'quimica_sanguinea', 1, NULL, NULL),
(125, 9, 'CREATIN FOSFOKINASA (CPK)', 0, 15.00, 'quimica_sanguinea', 1, NULL, NULL),
(126, 9, 'CREATIN FOSFOKINASA (CPKMB)', 1, 20.00, 'quimica_sanguinea', 1, NULL, NULL),
(127, 9, 'CREATININA', 0, 4.00, 'quimica_sanguinea', 1, NULL, NULL),
(128, 9, 'DESHIDROGENASA LACTIDA (LDH)', 0, 15.00, 'quimica_sanguinea', 1, NULL, NULL),
(129, 9, 'FERRITINA', 0, 30.00, 'quimica_sanguinea', 1, NULL, NULL),
(130, 9, 'FILTRADO GLOMERULAR', 0, 10.00, 'quimica_sanguinea', 1, NULL, NULL),
(131, 9, 'FOSFATASA ACIDA', 1, 12.00, 'quimica_sanguinea', 1, NULL, NULL),
(132, 9, 'FOSFATASA ALCALINA', 0, 8.00, 'quimica_sanguinea', 1, NULL, NULL),
(133, 9, 'GAMMA GLUTAMIL (GCT)', 0, 15.00, 'quimica_sanguinea', 1, NULL, NULL),
(134, 9, 'GLUCOSA', 0, 3.00, 'quimica_sanguinea', 1, NULL, NULL),
(135, 9, 'GLUCOSA POST PRANDIAL', 0, 3.00, 'quimica_sanguinea', 1, NULL, NULL),
(136, 9, 'GLUCOSA TOLERANCIA 2 HORAS', 0, 20.00, 'quimica_sanguinea', 1, NULL, NULL),
(137, 9, 'GLUCOSA TOLERANCIA 3 HORAS', 0, 25.00, 'quimica_sanguinea', 1, NULL, NULL),
(138, 9, 'GLUCOSA TOLERANCIA 5 HORAS', 0, 40.00, 'quimica_sanguinea', 1, NULL, NULL),
(139, 9, 'HEMOGLOBINA GLICOSILADA AIC', 0, 15.00, 'hematologia', 1, NULL, NULL),
(140, 9, 'HIERRO CAPACIDAD DE FIJACIÓN', 1, 20.00, 'quimica_sanguinea', 1, NULL, NULL),
(141, 9, 'HIERRO SÉRICO', 1, 10.00, 'quimica_sanguinea', 1, NULL, NULL),
(142, 9, 'LIPASA', 0, 20.00, 'quimica_sanguinea', 1, NULL, NULL),
(143, 9, 'NITRÓGENO UREICO', 0, 4.00, 'quimica_sanguinea', 1, NULL, NULL),
(144, 9, 'PROTEINA TOTALES Y DIF', 0, 12.00, 'quimica_sanguinea', 1, NULL, NULL),
(145, 9, 'PROTEÍNA C REACTIVA CARDIACA', 0, 30.00, 'quimica_sanguinea', 1, NULL, NULL),
(146, 9, 'TEST O\' SULLIVAN', 0, 20.00, 'quimica_sanguinea', 1, NULL, NULL),
(147, 9, 'TRANSAMINASA OXALACÉTICA', 0, 6.00, 'quimica_sanguinea', 1, NULL, NULL),
(148, 9, 'TRANSAMINASA PIRÚVICA', 0, 6.00, 'quimica_sanguinea', 1, NULL, NULL),
(149, 9, 'TRIGLICÉRIDOS', 0, 4.00, 'quimica_sanguinea', 1, NULL, NULL),
(150, 10, 'ACIDO ÚRICO ORINA 24H', 0, 15.00, 'uroanalisis', 1, NULL, NULL),
(151, 10, 'CALCIO ORINA DE 24H', 0, 15.00, 'uroanalisis', 1, NULL, NULL),
(152, 10, 'CLORO ORINA DE 24H', 0, 15.00, 'uroanalisis', 1, NULL, NULL),
(153, 10, 'CREATININA EN ORINA AL AZAR', 1, 10.00, 'uroanalisis', 1, NULL, NULL),
(154, 10, 'DEPURACIÓN DE CREATININA 24H', 0, 15.00, 'quimica_sanguinea', 1, NULL, NULL),
(155, 10, 'FÓSFORO ORINA 24H', 1, 15.00, 'uroanalisis', 1, NULL, NULL),
(156, 10, 'NITRÓGENO UREICO ORINA DE 24H', 0, 15.00, 'uroanalisis', 1, NULL, NULL),
(157, 10, 'POTASIO ORINA DE 24H', 0, 15.00, 'uroanalisis', 1, NULL, NULL),
(158, 10, 'PROTEÍNAS EN ORINA DE 24H', 0, 15.00, 'uroanalisis', 1, NULL, NULL),
(159, 11, 'ALBUMINA EN ORINA AL AZAR', 0, 10.00, 'uroanalisis', 1, NULL, NULL),
(160, 11, 'GENERAL DE ORINA', 0, 2.00, 'uroanalisis', 1, NULL, NULL),
(161, 11, 'MICROALBUMINA EN ORINA AL AZAR', 0, 10.00, 'uroanalisis', 1, NULL, NULL),
(162, 11, 'PROTEINA EN ORINA AL AZAR', 0, 10.00, 'uroanalisis', 1, NULL, NULL),
(163, 11, 'PROTEINAS EN ORINA DE 24 HORAS', 1, 15.00, 'uroanalisis', 1, NULL, NULL),
(164, 11, 'PRUEBA DE EMBARAZO EN ORINA', 0, 5.00, 'uroanalisis', 1, NULL, NULL),
(165, 11, 'RELACION ALBUMINA/ CREATININA EN ORINA AL AZAR', 0, 20.00, 'uroanalisis', 1, NULL, NULL),
(166, 12, 'PROBNP', 0, 50.00, 'quimica_sanguinea', 1, NULL, NULL),
(167, 12, 'TROPONINA I (CTNI)', 0, 35.00, 'quimica_sanguinea', 1, NULL, NULL),
(168, 13, 'VITAMINA D', 0, 50.00, 'quimica_sanguinea', 1, NULL, NULL),
(169, 15, 'HEMOCULTIVO', 1, 25.00, 'coprologia', 1, NULL, NULL),
(170, 15, 'HEMOCULTIVO', 1, 25.00, 'hematologia', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `examen_muestra`
--

CREATE TABLE `examen_muestra` (
  `examen_id` bigint(20) UNSIGNED NOT NULL,
  `muestra_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `examen_muestra`
--

INSERT INTO `examen_muestra` (`examen_id`, `muestra_id`) VALUES
(1, 5),
(2, 19),
(3, 12),
(3, 16),
(3, 17),
(3, 18),
(3, 19),
(4, 6),
(5, 2),
(5, 22),
(6, 7),
(6, 9),
(6, 16),
(6, 17),
(6, 18),
(6, 19),
(6, 23),
(7, 2),
(7, 22),
(8, 20),
(9, 13),
(10, 14),
(11, 14),
(12, 14),
(13, 15),
(14, 15),
(15, 15),
(16, 14),
(17, 14),
(18, 14),
(19, 6),
(20, 6),
(21, 6),
(22, 6),
(23, 6),
(24, 6),
(25, 6),
(26, 6),
(27, 6),
(28, 6),
(29, 21),
(30, 21),
(31, 21),
(32, 21),
(33, 21),
(34, 21),
(35, 21),
(36, 21),
(37, 21),
(38, 21),
(39, 21),
(40, 21),
(41, 21),
(42, 21),
(43, 21),
(44, 21),
(45, 21),
(46, 21),
(47, 21),
(48, 21),
(49, 21),
(50, 21),
(51, 21),
(52, 21),
(53, 21),
(54, 21),
(55, 21),
(56, 21),
(57, 21),
(58, 15),
(59, 15),
(60, 15),
(61, 23),
(62, 15),
(63, 15),
(64, 15),
(65, 15),
(66, 15),
(67, 15),
(68, 15),
(69, 15),
(70, 15),
(71, 21),
(72, 21),
(73, 21),
(74, 21),
(75, 21),
(76, 11),
(77, 21),
(78, 21),
(79, 21),
(80, 11),
(81, 21),
(82, 21),
(83, 21),
(84, 21),
(85, 11),
(86, 21),
(87, 21),
(88, 21),
(89, 21),
(90, 21),
(91, 21),
(92, 21),
(93, 21),
(94, 21),
(95, 21),
(96, 21),
(97, 21),
(98, 21),
(99, 21),
(100, 21),
(101, 21),
(102, 21),
(103, 21),
(104, 21),
(105, 21),
(106, 21),
(107, 21),
(108, 21),
(109, 21),
(110, 21),
(111, 21),
(112, 21),
(113, 21),
(114, 21),
(115, 21),
(116, 21),
(117, 21),
(118, 21),
(119, 21),
(120, 21),
(121, 21),
(122, 21),
(123, 21),
(124, 21),
(125, 21),
(126, 21),
(127, 21),
(128, 21),
(129, 21),
(130, 21),
(131, 21),
(132, 21),
(133, 21),
(134, 21),
(135, 21),
(136, 21),
(137, 21),
(138, 21),
(139, 15),
(140, 21),
(141, 21),
(142, 21),
(143, 21),
(144, 21),
(145, 21),
(146, 21),
(147, 21),
(148, 21),
(149, 21),
(150, 13),
(151, 13),
(152, 13),
(153, 13),
(154, 13),
(155, 13),
(156, 13),
(157, 13),
(158, 13),
(159, 13),
(160, 13),
(161, 13),
(162, 13),
(163, 13),
(164, 13),
(165, 13),
(166, 21),
(167, 21),
(168, 21),
(169, 15),
(170, 15);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grupos_etarios`
--

CREATE TABLE `grupos_etarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `edad_min` int(11) NOT NULL,
  `edad_max` int(11) NOT NULL,
  `unidad_tiempo` enum('días','semanas','meses','años') NOT NULL,
  `genero` enum('Masculino','Femenino','Ambos') NOT NULL DEFAULT 'Ambos',
  `estado` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grupos_etarios`
--

INSERT INTO `grupos_etarios` (`id`, `nombre`, `edad_min`, `edad_max`, `unidad_tiempo`, `genero`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'EMBARAZO TEMPRANO', 0, 12, 'semanas', 'Femenino', 1, NULL, NULL),
(2, 'EMBARAZO MEDIO', 13, 27, 'semanas', 'Femenino', 1, NULL, NULL),
(3, 'EMBARAZO TARDÍO', 28, 42, 'semanas', 'Femenino', 1, NULL, NULL),
(4, 'NEONATOS', 0, 28, 'días', 'Ambos', 1, NULL, NULL),
(5, 'LACTANTES', 1, 12, 'meses', 'Ambos', 1, NULL, NULL),
(6, 'NIÑOS', 1, 12, 'años', 'Ambos', 1, NULL, NULL),
(7, 'ADOLESCENTES', 13, 17, 'años', 'Ambos', 1, NULL, NULL),
(8, 'ADULTOS', 18, 64, 'años', 'Ambos', 1, NULL, NULL),
(9, 'ADULTOS MAYORES', 65, 120, 'años', 'Ambos', 1, NULL, NULL),
(10, 'TODAS LAS EDADES', 0, 120, 'años', 'Ambos', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicos`
--

CREATE TABLE `medicos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_04_14_000529_create_perfils_table', 1),
(5, '2025_04_14_034928_create_tipo_examens_table', 1),
(6, '2025_04_14_205903_create_examens_table', 1),
(7, '2025_04_14_212409_create_clientes_table', 1),
(8, '2025_04_25_054139_create_detalle_perfils_table', 1),
(9, '2025_05_16_002924_create_ordens_table', 1),
(10, '2025_05_21_023635_create_detalle_orden_table', 1),
(11, '2025_05_21_024721_create_detalle_orden_perfils_table', 1),
(12, '2025_08_03_231812_add_order_to_detalle_orden_table', 1),
(13, '2025_09_08_224947_create_permission_tables', 1),
(14, '2025_09_22_233906_create_muestras_table', 1),
(15, '2025_09_22_234430_add_sample_and_pause_fields_to_ordens_table', 1),
(16, '2025_09_24_001212_create_pruebas_tables', 1),
(17, '2025_09_25_052049_create_reactivos_and_grupos_etarios_tables', 1),
(18, '2025_09_28_195307_create_valor_referencias_table', 1),
(19, '2025_09_29_023045_add_estado_to_reactivos_table', 1),
(20, '2025_09_29_152022_create_resultados_table', 1),
(21, '2025_10_18_214516_create_codigos_table', 1),
(22, '2025_10_21_003553_add_flags_to_codigos_table', 1),
(23, '2025_11_11_030108_add_muestras_recibidas_to_detalle_orden_table', 1),
(24, '2025_11_11_031600_add_toma_muestra_fields_to_ordens_table', 1),
(25, '2025_11_12_031120_create_activity_log_table', 1),
(26, '2025_11_12_031121_add_event_column_to_activity_log_table', 1),
(27, '2025_11_12_031122_add_batch_uuid_column_to_activity_log_table', 1),
(28, '2025_11_12_232903_add_cupon_fields_to_ordens_table', 1),
(29, '2025_11_14_214228_add_firma_and_sello_to_users_table', 1),
(30, '2025_11_15_100506_add_nickname_to_users_table', 1),
(31, '2025_11_15_111448_add_snapshot_fields_to_resultados_table', 1),
(32, '2025_11_21_121845_add_semanas_gestacion_to_ordenes_table', 1),
(33, '2025_11_21_203859_add_user_id_to_resultados_table', 1),
(34, '2025_11_28_120021_add_pruebas_snapshot_to_detalle_orden_table', 1),
(35, '2025_11_28_144645_transform_reactivos_to_many_to_many', 1),
(36, '2025_11_30_235424_add_es_historico_to_reactivos_table', 1),
(37, '2025_12_18_221849_add_grupo_etario_to_clientes_table', 1),
(38, '2025_12_18_225835_modify_grupo_etario_column_in_clientes_table', 1),
(39, '2026_01_03_220738_add_edad_to_clientes_table', 1),
(40, '2026_01_07_205052_create_medicos_table', 1),
(41, '2026_01_07_205053_add_medico_id_to_ordens_table', 1),
(42, '2026_02_25_194316_add_alertar_to_resultados_table', 1),
(43, '2026_02_28_122118_add_observaciones_por_area_to_ordens_table', 1),
(44, '2026_05_18_000000_move_valor_referencias_to_pruebas_and_drop_reactivos', 1),
(45, '2026_08_25_000001_add_dui_to_clientes_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1);

-- --------------------------------------------------------

--
-- Table structure for table `muestras`
--

CREATE TABLE `muestras` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `instrucciones_paciente` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `muestras`
--

INSERT INTO `muestras` (`id`, `nombre`, `descripcion`, `instrucciones_paciente`, `created_at`, `updated_at`) VALUES
(1, 'BACILOSCOPIA', NULL, NULL, NULL, NULL),
(2, 'CABELLO', NULL, NULL, NULL, NULL),
(3, 'CULTIVO DE ESPUTO', NULL, NULL, NULL, NULL),
(4, 'CULTIVO DE LIQUIDO CEFALORRAQUIDEO', NULL, NULL, NULL, NULL),
(5, 'FLEMA', NULL, NULL, NULL, NULL),
(6, 'HECES', NULL, NULL, NULL, NULL),
(7, 'HISOPADO ANAL', NULL, NULL, NULL, NULL),
(8, 'HISOPADO BUCAL', NULL, NULL, NULL, NULL),
(9, 'HISOPADO DE HERIDAS', NULL, NULL, NULL, NULL),
(10, 'HISOPADO DE OIDO', NULL, NULL, NULL, NULL),
(11, 'HISOPADO FARINGEO', NULL, NULL, NULL, NULL),
(12, 'HISOPADO OCULAR', NULL, NULL, NULL, NULL),
(13, 'ORINA', NULL, NULL, NULL, NULL),
(14, 'PLASMA', NULL, NULL, NULL, NULL),
(15, 'SANGRE COMPLETA', NULL, NULL, NULL, NULL),
(16, 'SECRECIÓN DE ABSCESO', NULL, NULL, NULL, NULL),
(17, 'SECRECIONES NASALES', NULL, NULL, NULL, NULL),
(18, 'SECRECIONES URETRALES', NULL, NULL, NULL, NULL),
(19, 'SECRECIONES VAGINALES', NULL, NULL, NULL, NULL),
(20, 'SEMEN', NULL, NULL, NULL, NULL),
(21, 'SUERO', NULL, NULL, NULL, NULL),
(22, 'UÑAS', NULL, NULL, NULL, NULL),
(23, 'HISOPADO NASAL', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ordens`
--

CREATE TABLE `ordens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `semanas_gestacion` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha` date NOT NULL,
  `observaciones` text DEFAULT NULL,
  `observaciones_por_area` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`observaciones_por_area`)),
  `estado` enum('pendiente','pausada','en proceso','finalizado','cancelado') NOT NULL DEFAULT 'pendiente',
  `fecha_toma_muestra` timestamp NULL DEFAULT NULL,
  `motivo_pausa` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `toma_muestra_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `codigo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `medico_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ordens`
--

INSERT INTO `ordens` (`id`, `cliente_id`, `semanas_gestacion`, `total`, `descuento`, `fecha`, `observaciones`, `observaciones_por_area`, `estado`, `fecha_toma_muestra`, `motivo_pausa`, `created_at`, `updated_at`, `toma_muestra_user_id`, `codigo_id`, `medico_id`) VALUES
(1, 1, NULL, 52.00, 0.00, '2026-09-13', NULL, NULL, 'en proceso', '2026-09-14 03:22:48', NULL, '2026-09-14 03:19:10', '2026-09-14 03:22:48', 1, NULL, NULL),
(2, 1, NULL, 25.00, 0.00, '2026-09-20', NULL, '{\"COPROLOG\\u00cdA\":null,\"HEMATOLOG\\u00cdA\":null,\"QU\\u00cdMICA SANGU\\u00cdNEA\":null,\"UROAN\\u00c1LISIS\":null}', 'finalizado', '2026-09-21 03:46:48', NULL, '2026-09-21 03:46:32', '2026-09-21 04:02:22', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `perfil`
--

CREATE TABLE `perfil` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `precio` decimal(8,2) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `perfil`
--

INSERT INTO `perfil` (`id`, `nombre`, `precio`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'PERFIL HEPÁTICO', 35.00, 1, NULL, NULL),
(2, 'PERFIL DE RUTINA', 25.00, 1, NULL, NULL),
(3, 'PERFIL RENAL', 30.00, 1, NULL, NULL),
(4, 'PERFIL PRENATAL', 50.00, 1, NULL, NULL),
(5, 'PERFIL TIROIDEO TOTAL', 25.00, 1, NULL, NULL),
(6, 'PERFIL TIROIDEO LIBRE', 35.00, 1, NULL, NULL),
(7, 'PERFIL ELECTROLITOS', 48.00, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'ver_detalle_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(2, 'cambiar_estado_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(3, 'ver_expediente_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(4, 'access_cotizaciones', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(5, 'generar_pdf_cotizacion', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(6, 'enviar_cotizacion_email', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(7, 'ver_detalle_examenes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(8, 'agregar_pruebas_examenes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(9, 'cambiar_estado_examenes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(10, 'procesar_muestras_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(11, 'ingresar_resultados_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(12, 'imprimir_etiquetas_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(13, 'ver_pruebas_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(14, 'pausar_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(15, 'reanudar_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(16, 'finalizar_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(17, 'generar_reporte_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(18, 'cancelar_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(19, 'restaurar_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(20, 'cambiar_estado_perfiles', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(21, 'ver_pruebas_conjuntas', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(22, 'editar_pruebas_conjuntas', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(23, 'eliminar_pruebas_conjuntas', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(24, 'cambiar_estado_pruebas', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(25, 'cambiar_estado_tipo_examenes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(26, 'acceder_buscador_expedientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(27, 'imprimir_etiquetas_kanban', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(28, 'mover_etiquetas_kanban', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(29, 'cambiar_estado_grupos', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(30, 'ingresos_diarios', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(31, 'view_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(32, 'view_any_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(33, 'create_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(34, 'update_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(35, 'restore_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(36, 'restore_any_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(37, 'replicate_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(38, 'reorder_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(39, 'delete_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(40, 'delete_any_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(41, 'force_delete_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(42, 'force_delete_any_clientes', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(43, 'view_examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(44, 'view_any_examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(45, 'create_examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(46, 'update_examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(47, 'restore_examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(48, 'restore_any_examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(49, 'replicate_examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(50, 'reorder_examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(51, 'delete_examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(52, 'delete_any_examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(53, 'force_delete_examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(54, 'force_delete_any_examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(55, 'view_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(56, 'view_any_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(57, 'create_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(58, 'update_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(59, 'restore_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(60, 'restore_any_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(61, 'replicate_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(62, 'reorder_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(63, 'delete_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(64, 'delete_any_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(65, 'force_delete_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(66, 'force_delete_any_orden', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(67, 'view_perfil', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(68, 'view_any_perfil', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(69, 'create_perfil', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(70, 'update_perfil', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(71, 'restore_perfil', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(72, 'restore_any_perfil', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(73, 'replicate_perfil', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(74, 'reorder_perfil', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(75, 'delete_perfil', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(76, 'delete_any_perfil', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(77, 'force_delete_perfil', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(78, 'force_delete_any_perfil', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(79, 'view_role', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(80, 'view_any_role', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(81, 'create_role', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(82, 'update_role', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(83, 'restore_role', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(84, 'restore_any_role', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(85, 'replicate_role', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(86, 'reorder_role', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(87, 'delete_role', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(88, 'delete_any_role', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(89, 'force_delete_role', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(90, 'force_delete_any_role', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(91, 'view_tipo::examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(92, 'view_any_tipo::examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(93, 'create_tipo::examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(94, 'update_tipo::examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(95, 'restore_tipo::examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(96, 'restore_any_tipo::examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(97, 'replicate_tipo::examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(98, 'reorder_tipo::examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(99, 'delete_tipo::examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(100, 'delete_any_tipo::examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(101, 'force_delete_tipo::examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(102, 'force_delete_any_tipo::examen', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(103, 'view_user', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(104, 'view_any_user', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(105, 'create_user', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(106, 'update_user', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(107, 'restore_user', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(108, 'restore_any_user', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(109, 'replicate_user', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(110, 'reorder_user', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(111, 'delete_user', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(112, 'delete_any_user', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(113, 'force_delete_user', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(114, 'force_delete_any_user', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(115, 'view_activity::log', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(116, 'view_any_activity::log', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(117, 'create_activity::log', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(118, 'update_activity::log', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(119, 'restore_activity::log', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(120, 'restore_any_activity::log', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(121, 'replicate_activity::log', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(122, 'reorder_activity::log', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(123, 'delete_activity::log', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(124, 'delete_any_activity::log', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(125, 'force_delete_activity::log', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(126, 'force_delete_any_activity::log', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(127, 'view_codigo', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(128, 'view_any_codigo', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(129, 'create_codigo', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(130, 'update_codigo', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(131, 'restore_codigo', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(132, 'restore_any_codigo', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(133, 'replicate_codigo', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(134, 'reorder_codigo', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(135, 'delete_codigo', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(136, 'delete_any_codigo', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(137, 'force_delete_codigo', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(138, 'force_delete_any_codigo', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(139, 'view_cotizacion', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(140, 'view_any_cotizacion', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(141, 'create_cotizacion', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(142, 'update_cotizacion', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(143, 'restore_cotizacion', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(144, 'restore_any_cotizacion', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(145, 'replicate_cotizacion', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(146, 'reorder_cotizacion', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(147, 'delete_cotizacion', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(148, 'delete_any_cotizacion', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(149, 'force_delete_cotizacion', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(150, 'force_delete_any_cotizacion', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(151, 'view_grupo::etario', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(152, 'view_any_grupo::etario', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(153, 'create_grupo::etario', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(154, 'update_grupo::etario', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(155, 'restore_grupo::etario', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(156, 'restore_any_grupo::etario', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(157, 'replicate_grupo::etario', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(158, 'reorder_grupo::etario', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(159, 'delete_grupo::etario', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(160, 'delete_any_grupo::etario', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(161, 'force_delete_grupo::etario', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(162, 'force_delete_any_grupo::etario', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(163, 'view_muestra', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(164, 'view_any_muestra', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(165, 'create_muestra', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(166, 'update_muestra', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(167, 'restore_muestra', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(168, 'restore_any_muestra', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(169, 'replicate_muestra', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(170, 'reorder_muestra', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(171, 'delete_muestra', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(172, 'delete_any_muestra', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(173, 'force_delete_muestra', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(174, 'force_delete_any_muestra', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(175, 'view_prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(176, 'view_any_prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(177, 'create_prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(178, 'update_prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(179, 'restore_prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(180, 'restore_any_prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(181, 'replicate_prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(182, 'reorder_prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(183, 'delete_prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(184, 'delete_any_prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(185, 'force_delete_prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(186, 'force_delete_any_prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(187, 'view_tipo::prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(188, 'view_any_tipo::prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(189, 'create_tipo::prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(190, 'update_tipo::prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(191, 'restore_tipo::prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(192, 'restore_any_tipo::prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(193, 'replicate_tipo::prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(194, 'reorder_tipo::prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(195, 'delete_tipo::prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(196, 'delete_any_tipo::prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(197, 'force_delete_tipo::prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(198, 'force_delete_any_tipo::prueba', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(199, 'impersonate_user', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(200, 'access_admin_panel', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(201, 'manage_settings', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(202, 'export_data', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(203, 'import_data', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(204, 'view_reports', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `pruebas`
--

CREATE TABLE `pruebas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `examen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `tipo_prueba_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo_conjunto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pruebas`
--

INSERT INTO `pruebas` (`id`, `nombre`, `examen_id`, `estado`, `tipo_prueba_id`, `tipo_conjunto`, `created_at`, `updated_at`) VALUES
(1, 'COLOR', 22, 'activo', 2, NULL, NULL, NULL),
(3, 'CONSISTENCIA', 22, 'activo', 2, NULL, NULL, NULL),
(4, 'RESTOS ALIMENTICIOS', 22, 'activo', 2, NULL, NULL, NULL),
(5, 'SANGRE OCULTA', 22, 'activo', 2, NULL, NULL, NULL),
(6, 'MUCUS', 22, 'activo', 2, NULL, NULL, NULL),
(7, 'OTROS', 22, 'activo', 2, NULL, NULL, NULL),
(8, 'METAZOARIOS', 22, 'activo', 1, NULL, NULL, NULL),
(9, 'PROTOZOARIOS', 22, 'activo', 1, NULL, NULL, NULL),
(10, 'LEVADURAS', 22, 'activo', 1, NULL, NULL, NULL),
(11, 'PARTICULAS DE GRASAS', 22, 'activo', 1, NULL, NULL, NULL),
(12, 'MICROBIOTA INTESTINAL', 22, 'activo', 1, NULL, NULL, NULL),
(13, 'HEMATIES', 22, 'activo', 1, NULL, NULL, NULL),
(14, 'LEUCOCITOS', 22, 'activo', 1, NULL, NULL, NULL),
(15, 'OTROS', 22, 'activo', 1, NULL, NULL, NULL),
(16, 'COLOR', 160, 'activo', 3, NULL, NULL, NULL),
(17, 'ASPECTO', 160, 'activo', 3, NULL, NULL, NULL),
(18, 'PH', 160, 'activo', 3, NULL, NULL, NULL),
(19, 'DENSIDAD', 160, 'activo', 3, NULL, NULL, NULL),
(20, 'GLUCOSA', 160, 'activo', 3, NULL, NULL, NULL),
(21, 'PROTEINA', 160, 'activo', 3, NULL, NULL, NULL),
(22, 'CUERPO CETONICO', 160, 'activo', 3, NULL, NULL, NULL),
(23, 'UROBILINOGENO', 160, 'activo', 3, NULL, NULL, NULL),
(24, 'ESTERAZA LEUCOCITARIA', 160, 'activo', 3, NULL, NULL, NULL),
(25, 'SANGRE OCULTA', 160, 'activo', 3, NULL, NULL, NULL),
(26, 'NITRITOS', 160, 'activo', 3, NULL, NULL, NULL),
(27, 'BILIRRUBINA', 160, 'activo', 3, NULL, NULL, NULL),
(28, 'ACIDO ASCORBICO', 160, 'activo', 3, NULL, NULL, NULL),
(29, 'CRISTALES', 160, 'activo', 1, NULL, NULL, NULL),
(30, 'CILINDROS', 160, 'activo', 1, NULL, NULL, NULL),
(31, 'LEUCOCITOS', 160, 'activo', 1, NULL, NULL, NULL),
(32, 'HEMATÍES', 160, 'activo', 1, NULL, NULL, NULL),
(33, 'CÉLULAS EPITELIALES', 160, 'activo', 1, NULL, NULL, NULL),
(34, 'FILAMENTOS MUCOIDES', 160, 'activo', 1, NULL, NULL, NULL),
(35, 'BACTERIAS', 160, 'activo', 1, NULL, NULL, NULL),
(36, 'OTROS', 160, 'activo', 1, NULL, NULL, NULL),
(37, 'MICROORGANISMOS AISLADOS', 4, 'activo', NULL, NULL, NULL, NULL),
(38, 'PERIODO DE INCUBACIÓN', 4, 'activo', NULL, NULL, NULL, NULL),
(39, 'SENSIBLE', 4, 'activo', NULL, NULL, NULL, NULL),
(40, 'INTERMEDIO', 4, 'activo', NULL, NULL, NULL, NULL),
(41, 'RESISTENTES', 4, 'activo', NULL, NULL, NULL, NULL),
(42, 'RESULTADO', 20, 'activo', NULL, NULL, NULL, NULL),
(43, 'RESULTADOS', 28, 'activo', NULL, NULL, NULL, NULL),
(44, 'MICROORGANISMOS AISLADOS', 9, 'activo', NULL, NULL, NULL, NULL),
(45, 'RECUENTO', 9, 'activo', NULL, NULL, NULL, NULL),
(46, 'PRUEBA DE EMBARAZO', 164, 'activo', NULL, NULL, NULL, NULL),
(48, 'GRUPO SANGUINEO', 100, 'activo', NULL, NULL, NULL, NULL),
(49, 'FACTOR RH', 100, 'activo', NULL, NULL, NULL, NULL),
(50, 'BACILOSCOPIA (BAAR)', 1, 'activo', NULL, NULL, NULL, NULL),
(51, 'COLORACIÓN DE GRAM', 3, 'activo', NULL, NULL, NULL, NULL),
(52, 'CULTIVO DE HONGOS', 5, 'activo', NULL, NULL, NULL, NULL),
(53, 'TIPO DE MUESTRA', 5, 'activo', NULL, NULL, NULL, NULL),
(54, 'SECRECIÓN DE', 6, 'activo', NULL, NULL, NULL, NULL),
(55, 'CULTIVO DE SECRECIÓN DE', 6, 'activo', NULL, NULL, NULL, NULL),
(56, 'MICROORGANISMO AISLADO', 6, 'activo', NULL, NULL, NULL, NULL),
(57, 'SENSIBLE', 6, 'activo', NULL, NULL, NULL, NULL),
(58, 'INTERMEDIO', 6, 'activo', NULL, NULL, NULL, NULL),
(59, 'RESISTENTE', 6, 'activo', NULL, NULL, NULL, NULL),
(60, 'KOH', 7, 'activo', NULL, NULL, NULL, NULL),
(61, 'TIEMPO DE PROTROMBINA', 12, 'activo', NULL, NULL, NULL, NULL),
(62, 'VALOR PORCENTUAL', 12, 'activo', NULL, NULL, NULL, NULL),
(63, 'INR', 12, 'activo', NULL, NULL, NULL, NULL),
(64, 'ISI', 12, 'activo', NULL, NULL, NULL, NULL),
(65, 'RADIO', 12, 'activo', NULL, NULL, NULL, NULL),
(66, 'TIEMPO DE TROMBOPLASTINA PARCIAL ACTIVA', 12, 'activo', NULL, NULL, NULL, NULL),
(67, 'TIEMPO DE CUAGULACIÓN', 12, 'activo', NULL, NULL, NULL, NULL),
(68, 'TIEMPO DE SANGRAMIENTO', 15, 'activo', NULL, NULL, NULL, NULL),
(69, 'TIEMPO DE TROMBINA', 12, 'activo', NULL, NULL, NULL, NULL),
(70, 'FIBRINOGENO', 12, 'activo', NULL, NULL, NULL, NULL),
(71, 'RETRACCION DE COAGULO', 13, 'activo', NULL, NULL, NULL, NULL),
(72, 'MINUTOS - SEGUNDOS', 14, 'activo', NULL, NULL, NULL, NULL),
(74, 'MINUTOS - SEGUNDOS', 15, 'activo', NULL, NULL, NULL, NULL),
(75, 'TIEMPO DE TROMBOPLASTINA PARCIAL ACTIVA', 16, 'activo', NULL, NULL, NULL, NULL),
(80, 'TIEMPO DE TROMBINA', 17, 'activo', NULL, NULL, NULL, NULL),
(81, 'TIEMPO DE PROTROMBINA', 18, 'activo', NULL, NULL, NULL, NULL),
(82, 'VALOR PORCENTUAL', 18, 'activo', NULL, NULL, NULL, NULL),
(83, 'INR', 18, 'activo', NULL, NULL, NULL, NULL),
(84, 'ISI', 18, 'activo', NULL, NULL, NULL, NULL),
(85, 'RADIO', 18, 'activo', NULL, NULL, NULL, NULL),
(86, 'HELICOBACTER PYLORI - AG', 23, 'activo', NULL, NULL, NULL, NULL),
(87, 'SANGRE OCULTA EN HECES (CUANTITATIVO)', 27, 'activo', NULL, NULL, NULL, NULL),
(88, 'CALCIO', 29, 'activo', NULL, NULL, NULL, NULL),
(89, 'CLORO', 30, 'activo', NULL, NULL, NULL, NULL),
(90, 'FOSFORO', 31, 'activo', NULL, NULL, NULL, NULL),
(91, 'MAGNESIO', 32, 'activo', NULL, NULL, NULL, NULL),
(92, 'POTASIO', 33, 'activo', NULL, NULL, NULL, NULL),
(93, 'SODIO', 34, 'activo', NULL, NULL, NULL, NULL),
(94, 'BETA HCG CUANTITATIVO', 38, 'activo', NULL, NULL, NULL, NULL),
(97, 'HORMONA DE CRECIMIENTO', 43, 'activo', NULL, NULL, NULL, NULL),
(98, 'HORMONA PARATIROIDEA (PTH)', 44, 'activo', NULL, NULL, NULL, NULL),
(99, 'INSULINA PREPRANDIAL 0 MINUTOS', 45, 'activo', NULL, NULL, NULL, NULL),
(100, 'INSULINA 120 MINUTOS (POSTPANDRIAL)', 46, 'activo', NULL, NULL, NULL, NULL),
(101, 'HORMONA LEUTINIZANTE', 47, 'activo', NULL, NULL, NULL, NULL),
(102, 'PROGESTERONA', 49, 'activo', NULL, NULL, NULL, NULL),
(103, 'PROLACTINA', 50, 'activo', NULL, NULL, NULL, NULL),
(104, 'T3 LIBRE (FT3)', 51, 'activo', NULL, NULL, NULL, NULL),
(105, 'TRIYODOTIRONINA (T3)', 52, 'activo', NULL, NULL, NULL, NULL),
(106, 'TIROXINA (T4)', 54, 'activo', NULL, NULL, NULL, NULL),
(107, 'T4 LIBRE (FT4)', 53, 'activo', NULL, NULL, NULL, NULL),
(108, 'TESTOSTERONA (TE)', 55, 'activo', NULL, NULL, NULL, NULL),
(109, 'HORMONA ESTIMULANTES DE TIROIDES (TSH 3 GENERACION)', 57, 'activo', NULL, NULL, NULL, NULL),
(110, 'CELULAS LE', 59, 'activo', NULL, NULL, NULL, NULL),
(111, 'CONCENTRADO STRAUT', 58, 'activo', NULL, NULL, NULL, NULL),
(112, 'EOSINOFILOS NASALES', 61, 'activo', NULL, NULL, NULL, NULL),
(113, 'ERITROSEDIMENTACION', 62, 'activo', NULL, NULL, NULL, NULL),
(114, 'LINEA ROJA', 63, 'activo', NULL, NULL, NULL, NULL),
(115, 'LINEA BLANCA', 63, 'activo', NULL, NULL, NULL, NULL),
(116, 'LINEA PLAQUETARIA', 63, 'activo', NULL, NULL, NULL, NULL),
(117, 'HEMATOCRITO', 64, 'activo', NULL, NULL, NULL, NULL),
(118, 'HEMOGLOBINA', 64, 'activo', NULL, NULL, NULL, NULL),
(119, 'GOTA GRUESA (PLASMODIUM SSP)', 68, 'activo', NULL, NULL, NULL, NULL),
(120, 'PLAQUETAS', 67, 'activo', NULL, NULL, NULL, NULL),
(121, 'RETICULOCITOS', 69, 'activo', NULL, NULL, NULL, NULL),
(122, 'AC. ANTI-TIROIDEOGLOBULINA', 35, 'activo', NULL, NULL, NULL, NULL),
(123, 'AC. ANTI.TIROIDEOPEROXIDASA', 71, 'activo', NULL, NULL, NULL, NULL),
(124, 'ANTIGENO COVID-19 (SARS COV-2) HISOPADO NASOFARINGEO', 76, 'activo', NULL, NULL, NULL, NULL),
(125, 'ANTIESTREPTOLISINA O (ASTO)', 75, 'activo', NULL, NULL, NULL, NULL),
(126, 'SALMONELLA TYPHI H', 79, 'activo', NULL, NULL, NULL, NULL),
(127, 'SALMONELLA TYPHI O', 79, 'activo', NULL, NULL, NULL, NULL),
(128, 'SALMONELLA PARATYPHI AH', 79, 'activo', NULL, NULL, NULL, NULL),
(129, 'SALMONELLA PARATYPHI BH', 79, 'activo', NULL, NULL, NULL, NULL),
(130, 'BRUCELLA ABORTUS', 79, 'activo', NULL, NULL, NULL, NULL),
(131, 'PROTEUS OX19', 79, 'activo', NULL, NULL, NULL, NULL),
(132, 'AC. ANTIMITOCONDRIALES IGG', 77, 'activo', NULL, NULL, NULL, NULL),
(133, 'ANTI- CARDIOLIPINA IGM', 73, 'activo', NULL, NULL, NULL, NULL),
(134, 'GLOBULOS BLANCOS', 66, 'activo', NULL, NULL, NULL, NULL),
(135, 'NEUTROFILOS', 66, 'activo', NULL, NULL, NULL, NULL),
(136, 'LINFOCITOS', 66, 'activo', NULL, NULL, NULL, NULL),
(137, 'EOSINOFILOS', 66, 'activo', NULL, NULL, NULL, NULL),
(138, 'BASOFILO', 66, 'activo', NULL, NULL, NULL, NULL),
(139, 'GLÓBULOS ROJOS', 65, 'activo', 4, NULL, NULL, NULL),
(140, 'HEMATOCRITO', 65, 'activo', 4, NULL, NULL, NULL),
(141, 'HEMOGLOBINA', 65, 'activo', 4, NULL, NULL, NULL),
(142, 'V.C.M.', 65, 'activo', 4, NULL, NULL, NULL),
(143, 'H.C.M.', 65, 'activo', 4, NULL, NULL, NULL),
(144, 'C.H.C.M.', 65, 'activo', 4, NULL, NULL, NULL),
(145, 'GLÓBULOS BLANCOS', 65, 'activo', 5, NULL, NULL, NULL),
(146, 'NEUTRÓFILOS', 65, 'activo', 5, NULL, NULL, NULL),
(147, 'LINFOCITOS', 65, 'activo', 5, NULL, NULL, NULL),
(148, 'EOSINÓFILOS', 65, 'activo', 5, NULL, NULL, NULL),
(149, 'MONOCITOS', 65, 'activo', 5, NULL, NULL, NULL),
(150, 'BASÓFILOS', 65, 'activo', 5, NULL, NULL, NULL),
(151, 'PLAQUETAS', 65, 'activo', 6, NULL, NULL, NULL),
(152, 'V.P.M.', 65, 'activo', 6, NULL, NULL, NULL),
(153, 'P.D.W.', 65, 'activo', 6, NULL, NULL, NULL),
(154, 'OBSERVACIONES', 65, 'activo', NULL, NULL, NULL, NULL),
(155, 'ANA-8 (ELISA)', 78, 'activo', NULL, NULL, NULL, NULL),
(156, 'DENGUE AC. IGG', 82, 'activo', NULL, NULL, NULL, NULL),
(157, 'DENGUE AC. IGM', 82, 'activo', NULL, NULL, NULL, NULL),
(158, 'NS1', 82, 'activo', NULL, NULL, NULL, NULL),
(159, 'FACTOR REUMATOIDEO (LATEX RA)', 83, 'activo', NULL, NULL, NULL, NULL),
(160, 'FTA- ABS TREPONEMA', 84, 'activo', NULL, NULL, NULL, NULL),
(161, 'HELICOBACTER PYLORI AC. IGG', 86, 'activo', 7, NULL, NULL, NULL),
(162, 'HEPATITIS A IGM', 87, 'activo', NULL, NULL, NULL, NULL),
(163, 'AG. HEPATITIS B (HBSAG)', 88, 'activo', NULL, NULL, NULL, NULL),
(164, 'AC. HEPATITIS (ANTI-HCV)', 89, 'activo', NULL, NULL, NULL, NULL),
(165, 'INMUNOGLOBULINA E (IGE TOTAL)', 91, 'activo', NULL, NULL, NULL, NULL),
(166, 'INMUNOGLOBULINA IGA (MICROSOMAL)', 92, 'activo', NULL, NULL, NULL, NULL),
(167, 'INMUNOGLOBULINA IGG (MICROSOMAL)', 94, 'activo', NULL, NULL, NULL, NULL),
(168, 'INMUNOGLOBULINA IGM (MICROSOMAL)', 95, 'activo', NULL, NULL, NULL, NULL),
(169, 'MONOTEST', 96, 'activo', NULL, NULL, NULL, NULL),
(170, 'PRUEBA DE EMBARAZO EN SANGRE (B-HCG CUALITATIVA)', 99, 'activo', NULL, NULL, NULL, NULL),
(171, 'PROTEINA C REACTIVA', 98, 'activo', NULL, NULL, NULL, NULL),
(172, 'VDRL(PRUEBA DE SIFILIS)', 103, 'activo', NULL, NULL, NULL, NULL),
(173, 'TOXOPLASMA GONDII IGM', 102, 'activo', NULL, NULL, NULL, NULL),
(174, 'VIRUS DE INMUNODEFICIENCIA HUMANA (VIH 3 GENERACION)', 104, 'activo', NULL, NULL, NULL, NULL),
(175, 'VIRUS DE INMUNODEFICIENCIA HUMANA (VIH PRUEBA RAPIDA)', 105, 'activo', NULL, NULL, NULL, NULL),
(176, 'ALFAFETOPROTEINA (AFP)', 106, 'activo', NULL, NULL, NULL, NULL),
(177, 'CA-125', 107, 'activo', NULL, NULL, NULL, NULL),
(178, 'CA 15-3', 108, 'activo', NULL, NULL, NULL, NULL),
(179, 'CA 19-9', 109, 'activo', NULL, NULL, NULL, NULL),
(180, 'CEA AG.- CARCIOEMBRIONARIO', 110, 'activo', NULL, NULL, NULL, NULL),
(181, 'ANTIGENO PROSTATICO LIBRE (PSA LIBRE)', 111, 'activo', NULL, NULL, NULL, NULL),
(182, 'ANTIGENO PROSTATICO TOTAL (PSA TOTAL)', 112, 'activo', NULL, NULL, NULL, NULL),
(183, 'ACIDO URICO', 114, 'activo', NULL, NULL, NULL, NULL),
(184, 'ALBUMINA', 115, 'activo', NULL, NULL, NULL, NULL),
(185, 'AMILASA', 116, 'activo', NULL, NULL, NULL, NULL),
(186, 'BILIRRUBINA DIRECTA', 117, 'activo', NULL, NULL, NULL, NULL),
(187, 'BILIRRUBINA TOTAL', 119, 'activo', NULL, NULL, NULL, NULL),
(188, 'COLESTEROL ALTA DENSIDAD - HDL', 121, 'activo', NULL, NULL, NULL, NULL),
(189, 'COLESTEROL BAJA DENSIDAD - LDL', 122, 'activo', NULL, NULL, NULL, NULL),
(190, 'COLESTEROL TOTAL', 124, 'activo', NULL, NULL, NULL, NULL),
(191, 'CREATIN FOSFOKINASA TOTAL (CPK TOTAL)', 125, 'activo', NULL, NULL, NULL, NULL),
(192, 'CREATIN FOSFOKINASA FRACCION MB (CPK - MB)', 126, 'activo', NULL, NULL, NULL, NULL),
(193, 'CREATININA SERICA', 127, 'activo', NULL, NULL, NULL, NULL),
(194, 'DESHIDROGENASA LACTIDA (LDH)', 128, 'activo', NULL, NULL, NULL, NULL),
(195, 'FERRITINA', 129, 'activo', NULL, NULL, NULL, NULL),
(196, 'FOSFATASA ACIDA', 131, 'activo', NULL, NULL, NULL, NULL),
(197, 'FOSFATASA ALCALINA', 132, 'activo', NULL, NULL, NULL, NULL),
(198, 'GAMMA GLUTAMIL TRANSPEPTIDASA (GGT)', 133, 'activo', NULL, NULL, NULL, NULL),
(199, 'GLUCOSA EN AYUNA', 134, 'activo', NULL, NULL, NULL, NULL),
(200, 'GLUCOSA POST PRANDIAL', 135, 'activo', NULL, NULL, NULL, NULL),
(201, 'GLUCOSA EN AYUNA', 136, 'activo', NULL, NULL, NULL, NULL),
(202, 'GLUCOSA 1 HORA', 136, 'activo', NULL, NULL, NULL, NULL),
(203, 'GLUCOSA 2 HORAS', 136, 'activo', NULL, NULL, NULL, NULL),
(204, 'GLUCOSA EN AYUNA', 137, 'activo', NULL, NULL, NULL, NULL),
(205, 'GLUCOSA 1 HORAS', 137, 'activo', NULL, NULL, NULL, NULL),
(206, 'GLUCOSA 2 HORAS', 137, 'activo', NULL, NULL, NULL, NULL),
(207, 'GLUCOSA 3 HORAS', 137, 'activo', NULL, NULL, NULL, NULL),
(208, 'GLUCOSA EN AYUNA', 138, 'activo', NULL, NULL, NULL, NULL),
(209, 'GLUCOSA 1 HORAS', 138, 'activo', NULL, NULL, NULL, NULL),
(210, 'GLUCOSA 2 HORAS', 138, 'activo', NULL, NULL, NULL, NULL),
(211, 'GLUCOSA 3 HORAS', 138, 'activo', NULL, NULL, NULL, NULL),
(212, 'GLUCOSA 4 HORAS', 138, 'activo', NULL, NULL, NULL, NULL),
(213, 'GLUCOSA 5 HORAS', 138, 'activo', NULL, NULL, NULL, NULL),
(214, 'HEMOGLOBINA GLICOSILADA (HBA1C)', 139, 'activo', NULL, NULL, NULL, NULL),
(215, 'HIERRO CAPACIDAD DE FIJACION', 140, 'activo', NULL, NULL, NULL, NULL),
(216, 'HIERRO SERICO', 141, 'activo', NULL, NULL, NULL, NULL),
(217, 'LIPASA', 142, 'activo', NULL, NULL, NULL, NULL),
(218, 'UREA', 143, 'activo', NULL, NULL, NULL, NULL),
(219, 'NITROGENO UREICO (BUN)', 143, 'activo', NULL, NULL, NULL, NULL),
(220, 'PROTEINA TOTALES', 144, 'activo', NULL, NULL, NULL, NULL),
(221, 'ALBUMINA', 144, 'activo', NULL, NULL, NULL, NULL),
(222, 'GLOBULINA', 144, 'activo', NULL, NULL, NULL, NULL),
(223, 'RELACION A/G', 144, 'activo', NULL, NULL, NULL, NULL),
(224, 'PCR ULTRASENSIBLE (PCR-HS)', 145, 'activo', NULL, NULL, NULL, NULL),
(225, 'GLUCOSA EN AYUNA', 146, 'activo', NULL, NULL, NULL, NULL),
(226, 'GLUCOSA 1 HORA', 146, 'activo', NULL, NULL, NULL, NULL),
(227, 'TRANSAMINASA GLUTAMICO OXALACETICA  (TGO/AST)', 147, 'activo', NULL, NULL, NULL, NULL),
(228, 'TRASAMINASA GLUTAMICO PIRUVICA (TGP/ALT)', 148, 'activo', NULL, NULL, NULL, NULL),
(229, 'TRIGLICERIDOS', 149, 'activo', NULL, NULL, NULL, NULL),
(230, 'ACIDO URICO EN ORINA 24 HORAS}', 150, 'activo', NULL, NULL, NULL, NULL),
(231, 'CALCIO EN ORINA DE 24 HORAS', 151, 'activo', NULL, NULL, NULL, NULL),
(232, 'CLORO EN ORINA DE 24 HORAS', 152, 'activo', NULL, NULL, NULL, NULL),
(233, 'DEPURACION', 154, 'activo', NULL, NULL, NULL, NULL),
(234, 'CREATININA EN ORINA', 154, 'activo', NULL, NULL, NULL, NULL),
(235, 'CREATININA EN SANGRE', 154, 'activo', NULL, NULL, NULL, NULL),
(236, 'VOLUMEN', 154, 'activo', NULL, NULL, NULL, NULL),
(237, 'FOSFORO EN ORINA DE 24 HORAS', 155, 'activo', NULL, NULL, NULL, NULL),
(238, 'NITROGENO UREICO EN ORINA DE 24 HORAS', 156, 'activo', NULL, NULL, NULL, NULL),
(239, 'POTASIO DE ORINA DE 24 HORAS', 157, 'activo', NULL, NULL, NULL, NULL),
(240, 'PROTEINA EN ORINA DE 24 HORAS', 158, 'activo', NULL, NULL, NULL, NULL),
(241, 'VOLUMEN', 158, 'activo', NULL, NULL, NULL, NULL),
(242, 'EOSINOFILOS EN SANGRE', 60, 'activo', NULL, NULL, NULL, NULL),
(243, 'ANTIGENO PROSTATICO TOTAL (PSA TOTAL)', 113, 'activo', NULL, NULL, NULL, NULL),
(244, 'ANTIGENO PROSTATICO LIBRE (PSA LIBRE)', 113, 'activo', NULL, NULL, NULL, NULL),
(245, 'RELACION TOTAL LIBRE', 113, 'activo', NULL, NULL, NULL, NULL),
(246, 'PEPTIDO NETRIURETICO TIPO B (NT-PROBNP)', 166, 'activo', NULL, NULL, NULL, NULL),
(248, 'VITAMINA D TOTAL', 168, 'activo', NULL, NULL, NULL, NULL),
(249, 'TROPONINA I (CTNI)', 167, 'activo', NULL, NULL, NULL, NULL),
(250, 'TOXOPLASMA GONDII IGG', 101, 'activo', NULL, NULL, NULL, NULL),
(251, 'PH EN HECES', 25, 'activo', NULL, NULL, NULL, NULL),
(252, 'ANTI- MULLERIANA', 74, 'activo', NULL, NULL, NULL, NULL),
(255, 'SENSIBLE', 9, 'activo', NULL, NULL, NULL, NULL),
(256, 'INTERMEDIO', 9, 'activo', NULL, NULL, NULL, NULL),
(257, 'RESISTENTE', 9, 'activo', NULL, NULL, NULL, NULL),
(258, 'PROTEINAS EN ORINA AL AZAR', 162, 'activo', NULL, NULL, NULL, NULL),
(260, 'ANTI CCP (PÉPTIDO CÍCLICO CITRULINADO)', 37, 'activo', NULL, NULL, NULL, NULL),
(263, 'ANTICOAGULANTE LUPICO (CUALITATIVO)', 10, 'activo', NULL, NULL, NULL, NULL),
(264, 'RELACION ALBUMINA/ CREATININA EN ORINA AL AZAR', 165, 'activo', NULL, NULL, NULL, NULL),
(265, 'VOLUMEN', 8, 'activo', 2, NULL, NULL, NULL),
(266, 'ASPECTO', 8, 'activo', 2, NULL, NULL, NULL),
(267, 'LICUEFACCION', 8, 'activo', 2, NULL, NULL, NULL),
(268, 'VISCOSIDAD', 8, 'activo', 2, NULL, NULL, NULL),
(269, 'PH', 8, 'activo', 2, NULL, NULL, NULL),
(270, 'RECUENTO', 8, 'activo', 1, NULL, NULL, NULL),
(271, 'LEUCOCITOS', 8, 'activo', 1, NULL, NULL, NULL),
(272, 'HEMATIES', 8, 'activo', 1, NULL, NULL, NULL),
(273, 'CELULAS URETRALES', 8, 'activo', 1, NULL, NULL, NULL),
(274, 'CELULAS REDONDAS', 8, 'activo', 1, NULL, NULL, NULL),
(275, 'AGLUTINACION', 8, 'activo', 1, NULL, NULL, NULL),
(276, 'MORFOLOGIA NORMAL', 8, 'activo', 10, NULL, NULL, NULL),
(278, 'DOBLE COLA', 8, 'activo', 11, NULL, NULL, NULL),
(279, 'CABEZA EN GLOBO', 8, 'activo', 11, NULL, NULL, NULL),
(280, 'CABEZA DE ALFILER', 8, 'activo', 11, NULL, NULL, NULL),
(281, 'DOBLE CABEZA', 8, 'activo', 11, NULL, NULL, NULL),
(282, 'GRADO 0 (INMOVILES), 1H, (1:1)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(283, 'GRADO 0 (INMOVILES), 2H, (1:2)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(284, 'GRADO 0 (INMOVILES), 3H, (1:3)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(285, 'GRADO 0 (INMOVILES), 4H, (1:4)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(286, 'GRADO 1 (UN SOLO LUGAR), 1H, (2:1)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(287, 'GRADO 1 (UN SOLO LUGAR), 2H, (2:2)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(288, 'GRADO 1 (UN SOLO LUGAR), 3H, (2:3)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(289, 'GRADO 1 (UN SOLO LUGAR), 4H, (2:4)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(290, 'GRADO 2 (ONDULADO LENTO), 1H, (3:1)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(291, 'GRADO 2 (ONDULADO LENTO), 2H, (3:2)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(292, 'GRADO 2 (ONDULADO LENTO), 3H, (3:3)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(293, 'GRADO 2 (ONDULADO LENTO), 4H, (3:4)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(294, 'GRADO 3 (VERTICAL RAPIDO), 1H, (4:1)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(295, 'GRADO 3 (VERTICAL RAPIDO), 2H, (4:2)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(296, 'GRADO 3 (VERTICAL RAPIDO), 3H, (4:3)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(297, 'GRADO 3 (VERTICAL RAPIDO), 4H, (4:4)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(298, 'VIABILIDAD, 1H, (5:1)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(299, 'VIABILIDAD, 2H, (5:2)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(300, 'VIABILIDAD, 3H, (5:3)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(301, 'VIABILIDAD, 4H, (5:4)', 8, 'activo', NULL, 'conjunto_69a8ea15de676', NULL, NULL),
(302, 'ESTRADIOL', 41, 'activo', NULL, NULL, NULL, NULL),
(303, 'FILTRADO GLOMERULAR', 130, 'activo', NULL, NULL, NULL, NULL),
(304, 'BILIRRUBINA INDIRECTA', 118, 'activo', NULL, NULL, NULL, NULL),
(305, 'IGM TIFOIDEA (SALMONELLA TYPHI - SALMONELLA PARATYPHI)', 24, 'activo', NULL, NULL, NULL, NULL),
(306, 'AG. SALMONELLA TYPHI', 35, 'activo', NULL, NULL, NULL, NULL),
(307, 'AG. SALMONELLA TYPHI', 19, 'activo', NULL, NULL, NULL, NULL),
(308, 'AG. SALMONELLA PARATYPHI', 19, 'activo', NULL, NULL, NULL, NULL),
(309, 'ALBUMINA EN ORINA AL AZAR', 159, 'activo', NULL, NULL, NULL, NULL),
(310, 'CORTISOL AM', 39, 'activo', NULL, NULL, NULL, NULL),
(311, 'CORTISOL PM', 40, 'activo', NULL, NULL, NULL, NULL),
(312, 'FSH (HORMONA FOLÍCULO ESTIMULANTE)', 42, 'activo', NULL, NULL, NULL, NULL),
(313, 'LH (HORMONA LUTEINIZANTE)', 48, 'activo', NULL, NULL, NULL, NULL),
(314, 'ACTH (HORMONA ADRENOCORTICOTROPICA)', 36, 'activo', NULL, NULL, NULL, NULL),
(315, 'CREATININA EN ORINA AL AZAR', 162, 'activo', NULL, NULL, NULL, NULL),
(316, 'MICROALBUMINA EN ORINA AL AZAR', 161, 'activo', NULL, NULL, NULL, NULL),
(317, 'HERPES IGM (TIPO II)', 90, 'activo', NULL, NULL, NULL, NULL),
(318, 'GONORREA – AG.', 85, 'activo', NULL, NULL, NULL, NULL),
(319, 'CHLAMYDIA TRACHOMATIS – AG', 80, 'activo', NULL, NULL, NULL, NULL),
(320, 'DIMERO D', 10, 'inactivo', NULL, NULL, NULL, NULL),
(321, 'DIMERO-D', 11, 'activo', NULL, NULL, NULL, NULL),
(322, 'CONCENTRADO EN HECES', 21, 'activo', NULL, NULL, NULL, NULL),
(323, 'CITOMEGALOVIRUS IGM', 120, 'activo', NULL, NULL, NULL, NULL),
(324, 'COLORACION GRAM (FROTIS VAGINAL)', 2, 'activo', NULL, NULL, NULL, NULL),
(325, 'GONORREA – AG.', 85, 'activo', NULL, NULL, NULL, NULL),
(326, 'CHLAMYDIA TRACHOMATIS – AG.', 80, 'activo', NULL, NULL, NULL, NULL),
(327, 'PROCALCITONINA', 97, 'activo', NULL, NULL, NULL, NULL),
(328, 'DEHIDROEPIANDROSTERONA SULFATO (DHEA-SO4)', 81, 'activo', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `resultados`
--

CREATE TABLE `resultados` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `detalle_orden_id` bigint(20) UNSIGNED NOT NULL,
  `prueba_id` bigint(20) UNSIGNED DEFAULT NULL,
  `resultado` varchar(255) NOT NULL,
  `prueba_nombre_snapshot` varchar(255) DEFAULT NULL,
  `valor_referencia_snapshot` varchar(255) DEFAULT NULL,
  `unidades_snapshot` varchar(255) DEFAULT NULL,
  `valor_referencia_externo` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fuera_de_rango` tinyint(1) NOT NULL DEFAULT 0,
  `es_externo` tinyint(1) NOT NULL DEFAULT 0,
  `alertar` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resultados`
--

INSERT INTO `resultados` (`id`, `user_id`, `detalle_orden_id`, `prueba_id`, `resultado`, `prueba_nombre_snapshot`, `valor_referencia_snapshot`, `unidades_snapshot`, `valor_referencia_externo`, `observaciones`, `fuera_de_rango`, `es_externo`, `alertar`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 139, '3000000', 'GLÓBULOS ROJOS', '3800000 - 5800000', 'mm³', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(2, 1, 6, 140, '38', 'HEMATOCRITO', '37 - 53', '%', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(3, 1, 6, 141, '12', 'HEMOGLOBINA', '12 - 17', 'gr/dl', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(4, 1, 6, 142, '80', 'V.C.M.', '80 - 110', 'fL', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(5, 1, 6, 143, '25', 'H.C.M.', '26 - 38', 'pg', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(6, 1, 6, 144, '30', 'C.H.C.M.', '31 - 37', 'gr/dl', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(7, 1, 6, 145, '5000', 'GLÓBULOS BLANCOS', '5000 - 10000', 'mm³', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(8, 1, 6, 146, '50', 'NEUTRÓFILOS', '50 - 70', '%', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(9, 1, 6, 147, '20', 'LINFOCITOS', '20 - 40', '%', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(10, 1, 6, 148, '5', 'EOSINÓFILOS', '0 - 5', '%', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(11, 1, 6, 149, '3', 'MONOCITOS', '2 - 8', '%', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(12, 1, 6, 150, '1', 'BASÓFILOS', '0 - 1', '%', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(13, 1, 6, 151, '149999', 'PLAQUETAS', '150000 - 450000', 'mm³', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(14, 1, 6, 152, '6.5', 'V.P.M.', '6.5 - 11', 'fL', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(15, 1, 6, 153, '10.5', 'P.D.W.', '10 - 14', '%', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(16, 1, 7, 183, '3.33', 'ACIDO URICO', 'HOMBRE 3.4 - 7', 'mg/dL', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(17, 1, 8, 190, '192', 'COLESTEROL TOTAL', '0 - 190', 'mg/dL', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(18, 1, 9, 193, '0.69', 'CREATININA SERICA', 'HOMBRE 0.7 - 1.3', 'mg/dl', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(19, 1, 10, 199, '59.9', 'GLUCOSA EN AYUNA', '60 - 110', 'mg/dL', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(20, 1, 11, 218, '10', 'UREA', '10 - 50', 'mg/dL', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(21, 1, 11, 219, '5', 'NITROGENO UREICO (BUN)', '7 - 24', 'mg/dL', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(22, 1, 12, 229, '150', 'TRIGLICERIDOS', '0 - 150', 'mg/dL', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(23, 1, 13, 16, 'POSITIVO', 'COLOR', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(24, 1, 13, 17, 'POSITIVO', 'ASPECTO', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(25, 1, 13, 18, 'POSITIVO', 'PH', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(26, 1, 13, 19, 'POSITIVO', 'DENSIDAD', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(27, 1, 13, 20, 'POSITIVO', 'GLUCOSA', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(28, 1, 13, 21, 'POSITIVO', 'PROTEINA', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(29, 1, 13, 22, 'POSITIVO', 'CUERPO CETONICO', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(30, 1, 13, 23, 'ERY/μL', 'UROBILINOGENO', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(31, 1, 13, 24, 'LEU/μL', 'ESTERAZA LEUCOCITARIA', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(32, 1, 13, 25, 'AMARILLO', 'SANGRE OCULTA', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(33, 1, 13, 26, 'POSITIVO', 'NITRITOS', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(34, 1, 13, 27, 'NEGATIVO', 'BILIRRUBINA', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(35, 1, 13, 28, 'POSITIVO', 'ACIDO ASCORBICO', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(36, 1, 13, 29, 'POSITIVO', 'CRISTALES', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(37, 1, 13, 30, 'NO SE OBSERVAN', 'CILINDROS', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(38, 1, 13, 31, 'NO SE OBSERVAN', 'LEUCOCITOS', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(39, 1, 13, 32, 'NO SE OBSERVAN', 'HEMATÍES', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(40, 1, 13, 33, 'NO SE OBSERVAN', 'CÉLULAS EPITELIALES', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(41, 1, 13, 34, 'ERY/μL', 'FILAMENTOS MUCOIDES', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(42, 1, 13, 35, 'NEGATIVO', 'BACTERIAS', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(43, 1, 13, 36, 'POSITIVO', 'OTROS', '', '', NULL, NULL, 0, 0, 0, '2026-09-21 04:02:10', '2026-09-21 04:02:10'),
(44, 1, 1, 104, '222222', 'T3 LIBRE (FT3)', '2 - 4.4', 'pg/mL', NULL, NULL, 0, 0, 0, '2026-09-28 02:40:31', '2026-09-28 02:40:31'),
(45, 1, 2, 107, '2222222', 'T4 LIBRE (FT4)', '0.98 - 1.71', 'ng/dL', NULL, NULL, 0, 0, 0, '2026-09-28 02:40:31', '2026-09-28 02:40:31');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'web', '2026-08-27 03:58:03', '2026-08-27 03:58:03'),
(2, 'Recepcion', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04'),
(3, 'Laboratorista', 'web', '2026-08-27 03:58:04', '2026-08-27 03:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(2, 1),
(3, 1),
(3, 2),
(4, 1),
(4, 2),
(5, 1),
(5, 2),
(6, 1),
(6, 2),
(7, 1),
(7, 3),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(11, 3),
(12, 1),
(12, 2),
(13, 1),
(13, 3),
(14, 1),
(14, 3),
(15, 1),
(15, 3),
(16, 1),
(16, 3),
(17, 1),
(17, 2),
(18, 1),
(18, 2),
(19, 1),
(20, 1),
(21, 1),
(21, 3),
(22, 1),
(23, 1),
(24, 1),
(25, 1),
(26, 1),
(26, 2),
(27, 1),
(28, 1),
(29, 1),
(30, 1),
(31, 1),
(31, 2),
(32, 1),
(32, 2),
(33, 1),
(33, 2),
(34, 1),
(34, 2),
(35, 1),
(36, 1),
(37, 1),
(38, 1),
(39, 1),
(40, 1),
(41, 1),
(42, 1),
(43, 1),
(43, 2),
(43, 3),
(44, 1),
(44, 2),
(44, 3),
(45, 1),
(46, 1),
(47, 1),
(48, 1),
(49, 1),
(50, 1),
(51, 1),
(52, 1),
(53, 1),
(54, 1),
(55, 1),
(55, 2),
(55, 3),
(56, 1),
(56, 2),
(56, 3),
(57, 1),
(57, 2),
(58, 1),
(58, 2),
(59, 1),
(60, 1),
(61, 1),
(62, 1),
(63, 1),
(64, 1),
(65, 1),
(66, 1),
(67, 1),
(67, 2),
(68, 1),
(68, 2),
(69, 1),
(70, 1),
(71, 1),
(72, 1),
(73, 1),
(74, 1),
(75, 1),
(76, 1),
(77, 1),
(78, 1),
(79, 1),
(80, 1),
(81, 1),
(82, 1),
(83, 1),
(84, 1),
(85, 1),
(86, 1),
(87, 1),
(88, 1),
(89, 1),
(90, 1),
(91, 1),
(92, 1),
(93, 1),
(94, 1),
(95, 1),
(96, 1),
(97, 1),
(98, 1),
(99, 1),
(100, 1),
(101, 1),
(102, 1),
(103, 1),
(104, 1),
(105, 1),
(106, 1),
(107, 1),
(108, 1),
(109, 1),
(110, 1),
(111, 1),
(112, 1),
(113, 1),
(114, 1),
(115, 1),
(115, 3),
(116, 1),
(116, 3),
(117, 1),
(118, 1),
(119, 1),
(120, 1),
(121, 1),
(122, 1),
(123, 1),
(124, 1),
(125, 1),
(126, 1),
(127, 1),
(128, 1),
(128, 2),
(129, 1),
(130, 1),
(131, 1),
(132, 1),
(133, 1),
(134, 1),
(135, 1),
(136, 1),
(137, 1),
(138, 1),
(139, 1),
(140, 1),
(140, 2),
(141, 1),
(141, 2),
(142, 1),
(143, 1),
(144, 1),
(145, 1),
(146, 1),
(147, 1),
(148, 1),
(149, 1),
(150, 1),
(151, 1),
(152, 1),
(153, 1),
(154, 1),
(155, 1),
(156, 1),
(157, 1),
(158, 1),
(159, 1),
(160, 1),
(161, 1),
(162, 1),
(163, 1),
(164, 1),
(164, 3),
(165, 1),
(166, 1),
(167, 1),
(168, 1),
(169, 1),
(170, 1),
(171, 1),
(172, 1),
(173, 1),
(174, 1),
(175, 1),
(175, 3),
(176, 1),
(176, 3),
(177, 1),
(178, 1),
(179, 1),
(180, 1),
(181, 1),
(182, 1),
(183, 1),
(184, 1),
(185, 1),
(186, 1),
(187, 1),
(188, 1),
(189, 1),
(190, 1),
(191, 1),
(192, 1),
(193, 1),
(194, 1),
(195, 1),
(196, 1),
(197, 1),
(198, 1),
(199, 1),
(200, 1),
(200, 2),
(200, 3),
(201, 1),
(202, 1),
(203, 1),
(204, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('6AVexBDFGlLQ33G3UPsRAvxQTpUzehO8f6AsJQEe', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiam9DZVBLT1FJY1pzUFFkdVFKekJ5bnF1QWhNS1lrcUUxTE1NR1FiWSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1790641529),
('DspvwmXASNoSloRTfNCAtijvA1fUHnwMl0WKDlxc', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'YTo3OntzOjY6Il90b2tlbiI7czo0MDoidzAyU3piNWFqSUJQZ2tyNVR1d2RiZFFrVTRzSkU4c2liRU9iVXpROSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjUxOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvb3JkZW5lcy8xL2luZ3Jlc2FyLXJlc3VsdGFkb3MiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjYwOiIkMnkkMTIkeHVPSVpwdkJmZy5CeHpFQVBRdi5sLnB0VmVXUVFjaHFldHFBUlNIWHFKbm1NTzFlQkI4QU8iO3M6ODoiZmlsYW1lbnQiO2E6MDp7fX0=', 1790563247),
('ywpvyGtW7gto5qtCSzPXB2Ed7bu74mhjjpSPVirC', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoidDFNeG5IcHJmcFVoa0hnRERLeVNXUDBuV1FwalpWcDdUV3kwTko5cyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9vcmRlbmVzIjt9czozOiJ1cmwiO2E6MDp7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjA6IiQyeSQxMiR4dU9JWnB2QmZnLkJ4ekVBUFF2LmwucHRWZVdRUWNocWV0cUFSU0hYcUpubU1PMWVCQjhBTyI7fQ==', 1790641941);

-- --------------------------------------------------------

--
-- Table structure for table `tipos_pruebas`
--

CREATE TABLE `tipos_pruebas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tipos_pruebas`
--

INSERT INTO `tipos_pruebas` (`id`, `nombre`, `created_at`, `updated_at`) VALUES
(1, 'MICROSCOPICO', NULL, NULL),
(2, 'MACROSCOPICO', NULL, NULL),
(3, 'FISICO - QUIMICO', NULL, NULL),
(4, 'LINEA ROJA', NULL, NULL),
(5, 'LINEA BLANCA', NULL, NULL),
(6, 'LINEA PLAQUETARIA', NULL, NULL),
(7, 'HELICOBACTER PYLORI AC. IGG', NULL, NULL),
(8, 'INSULINA 120 MINUTOS (POSTPANDRIAL)', NULL, NULL),
(9, 'INSULINA 0 MINUTOS', NULL, NULL),
(10, 'MORFOLOGIA', NULL, NULL),
(11, 'MORFOLOGIA ANORMAL', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tipo_examens`
--

CREATE TABLE `tipo_examens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tipo_examens`
--

INSERT INTO `tipo_examens` (`id`, `nombre`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'BACTERIOLOGÍA', 1, NULL, NULL),
(2, 'COAGULACIÓN', 1, NULL, NULL),
(3, 'COPROLOGÍA', 1, NULL, NULL),
(4, 'ELECTROLITOS', 1, NULL, NULL),
(5, 'ENDOCRINOLOGÍA', 1, NULL, NULL),
(6, 'HEMATOLOGÍA', 1, NULL, NULL),
(7, 'INMUNOLOGÍA', 1, NULL, NULL),
(8, 'MARCADORES TUMORALES', 1, NULL, NULL),
(9, 'QUÍMICA SANGUÍNEA', 1, NULL, NULL),
(10, 'QUÍMICA URINARIA', 1, NULL, NULL),
(11, 'UROANÁLISIS', 1, NULL, NULL),
(12, 'CARDIOVASCULAR', 1, NULL, NULL),
(13, 'MINERALES', 1, NULL, NULL),
(14, 'PROTEINA EN ORINA AL AZAR', 1, NULL, NULL),
(15, 'HEMOCULTIVO', 1, NULL, NULL),
(16, 'HEMOCULTIVO', 1, NULL, NULL),
(17, 'CREATININA EN ORINA AL AZAR', 1, NULL, NULL),
(18, 'CREATININA EN ORINA AL AZAR', 1, NULL, NULL),
(19, 'AC. ANTI- TRYPANOSOMA CRUZI TOTALES (CHAGAS)', 1, NULL, NULL),
(20, 'DIMERO D', 1, NULL, NULL),
(21, 'DIMERO D', 1, NULL, NULL),
(22, 'CITOMEGALOVIRUS IGM', 1, NULL, NULL),
(23, 'TIROGLOBULINAS', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `firma_path` varchar(255) DEFAULT NULL,
  `sello_path` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `nickname`, `email`, `email_verified_at`, `password`, `firma_path`, `sello_path`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Saul Merino', 'saulmerino', 'eduardo_hrdz18@hotmail.com', '2026-08-27 03:58:03', '$2y$12$xuOIZpvBfg.BxzEAPQv.l.ptVeWQQchqetqARSHXqJnmMO1eBB8AO', NULL, NULL, 'Os9gYrK3HJNjyE2htnsuToOJm3CncgDHQOeFDyamo0jSNN4yXwDOc3LRpQ0t', '2026-08-27 03:58:03', '2026-08-27 03:58:03');

-- --------------------------------------------------------

--
-- Table structure for table `valor_referencias`
--

CREATE TABLE `valor_referencias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `prueba_id` bigint(20) UNSIGNED DEFAULT NULL,
  `grupo_etario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `operador` varchar(255) NOT NULL DEFAULT 'rango' COMMENT 'Define el tipo de lógica: rango, <=, >=, <, >, =',
  `descriptivo` varchar(255) DEFAULT NULL COMMENT 'Ej: Fumadores, No Fumadores, Riesgoso',
  `genero` varchar(255) DEFAULT NULL COMMENT 'Masculino, Femenino, Ambos.',
  `valor_min` decimal(13,2) DEFAULT NULL,
  `valor_max` decimal(13,2) DEFAULT NULL,
  `unidades` varchar(255) DEFAULT NULL,
  `nota` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `valor_referencias`
--

INSERT INTO `valor_referencias` (`id`, `prueba_id`, `grupo_etario_id`, `operador`, `descriptivo`, `genero`, `valor_min`, `valor_max`, `unidades`, `nota`, `created_at`, `updated_at`) VALUES
(1, 227, 10, 'rango', NULL, 'Masculino', 0.00, 38.00, 'U/L', NULL, NULL, NULL),
(2, 227, 10, 'rango', NULL, 'Femenino', 0.00, 31.00, 'U/L', NULL, NULL, NULL),
(3, 197, 10, 'rango', NULL, 'Ambos', 27.00, 100.00, 'U/L', NULL, NULL, NULL),
(5, 194, 10, 'rango', NULL, 'Ambos', 230.00, 460.00, 'U/L', NULL, NULL, NULL),
(6, 229, 10, 'rango', NULL, 'Ambos', 0.00, 150.00, 'mg/dL', NULL, NULL, NULL),
(7, 183, 10, 'rango', 'HOMBRE', 'Masculino', 3.40, 7.00, 'mg/dL', NULL, NULL, NULL),
(8, 183, 10, 'rango', 'MUJER', 'Femenino', 2.40, 5.70, 'mg/dL', NULL, NULL, NULL),
(9, 186, 10, 'rango', NULL, 'Ambos', 0.00, 0.25, 'mg/dL', NULL, NULL, NULL),
(10, 186, 4, 'rango', NULL, 'Ambos', 0.00, 0.30, 'mg/dL', NULL, NULL, NULL),
(11, 186, 5, 'rango', NULL, 'Ambos', 0.00, 0.20, 'mg/dL', NULL, NULL, NULL),
(12, 186, 7, 'rango', NULL, 'Ambos', 0.00, 0.20, 'mg/dL', NULL, NULL, NULL),
(13, 186, 6, 'rango', NULL, 'Ambos', 0.00, 0.20, 'mg/dL', NULL, NULL, NULL),
(14, 187, 10, 'rango', NULL, 'Ambos', 0.10, 1.20, 'mg/dL', NULL, NULL, NULL),
(15, 187, 6, 'rango', NULL, 'Ambos', 0.20, 1.20, 'mg/dL', NULL, NULL, NULL),
(16, 187, 7, 'rango', NULL, 'Ambos', 0.20, 1.20, 'mg/dL', NULL, NULL, NULL),
(17, 187, 4, '<=', NULL, 'Ambos', NULL, 5.00, 'mg/dL', NULL, NULL, NULL),
(18, 190, 10, 'rango', NULL, 'Ambos', 0.00, 190.00, 'mg/dL', NULL, NULL, NULL),
(19, 190, 7, 'rango', NULL, 'Ambos', 0.00, 170.00, 'mg/dL', NULL, NULL, NULL),
(20, 190, 6, 'rango', NULL, 'Ambos', 0.00, 170.00, 'mg/dL', NULL, NULL, NULL),
(21, 188, 10, '>', 'Riesgo Menor', 'Ambos', 55.00, NULL, 'mg/dL', NULL, NULL, NULL),
(22, 188, 10, 'rango', 'Riesgo Normal', 'Ambos', 35.00, 55.00, 'mg/dL', NULL, NULL, NULL),
(23, 188, 10, '<', 'Riesgo Elevado', 'Ambos', NULL, 35.00, 'mg/dL', NULL, NULL, NULL),
(26, 181, 10, '<', NULL, 'Masculino', NULL, 1.00, 'ng/mL', NULL, NULL, NULL),
(30, 182, 10, 'rango', 'NORMAL O BAJO RIESGO', 'Ambos', 0.00, 4.00, 'ng/mL', NULL, NULL, NULL),
(31, 182, 10, 'rango', 'MEDIANO RIESGO', 'Ambos', 4.00, 10.00, 'ng/mL', NULL, NULL, NULL),
(32, 182, 10, '>', 'ALTO RIESGO', 'Ambos', 10.00, NULL, 'ng/mL', NULL, NULL, NULL),
(33, 109, 8, 'rango', NULL, 'Ambos', 0.30, 4.20, 'uUI/mL', NULL, NULL, NULL),
(34, 109, 7, 'rango', NULL, 'Ambos', 0.40, 4.00, 'uUI/mL', NULL, NULL, NULL),
(35, 109, 5, 'rango', NULL, 'Ambos', 0.80, 8.20, 'uUI/mL', NULL, NULL, NULL),
(36, 109, 6, 'rango', NULL, 'Ambos', 0.60, 6.00, 'uUI/mL', NULL, NULL, NULL),
(37, 106, 10, 'rango', NULL, 'Ambos', 5.10, 14.10, 'ug/dL', NULL, NULL, NULL),
(38, 106, 7, 'rango', NULL, 'Ambos', 5.00, 12.00, 'ug/dL', NULL, NULL, NULL),
(39, 106, 6, 'rango', NULL, 'Ambos', 5.00, 12.00, 'ug/dL', NULL, NULL, NULL),
(40, 245, 10, '>', 'PROBABILIDAD DE HIPERPLASIA BENIGNA', 'Masculino', 25.00, NULL, '%', NULL, NULL, NULL),
(41, 245, 10, 'rango', 'PROBABILIDAD MEDIANA DE NEOPLASIA PROSTÁTICA', 'Masculino', 11.00, 25.00, '%', NULL, NULL, NULL),
(42, 245, 10, '<', 'PROBABILIDAD ALTA DE NEOPLASIA PROSTÁTICA', 'Masculino', NULL, 11.00, '%', NULL, NULL, NULL),
(43, 105, 10, 'rango', NULL, 'Ambos', 0.80, 2.00, 'ng/mL', NULL, NULL, NULL),
(44, 105, 4, 'rango', NULL, 'Ambos', 0.80, 2.20, 'ng/mL', NULL, NULL, NULL),
(45, 105, 5, 'rango', NULL, 'Ambos', 1.00, 2.50, 'ng/mL', NULL, NULL, NULL),
(46, 105, 6, 'rango', NULL, 'Ambos', 1.00, 2.40, 'ng/mL', NULL, NULL, NULL),
(47, 105, 7, 'rango', NULL, 'Ambos', 0.80, 2.00, 'ng/mL', NULL, NULL, NULL),
(48, 104, 8, 'rango', NULL, 'Ambos', 2.00, 4.40, 'pg/mL', NULL, NULL, NULL),
(49, 104, 7, 'rango', NULL, 'Ambos', 2.30, 4.20, 'pg/mL', NULL, NULL, NULL),
(50, 104, 6, 'rango', NULL, 'Ambos', 2.30, 4.20, 'pg/mL', NULL, NULL, NULL),
(51, 107, 8, 'rango', NULL, 'Ambos', 0.98, 1.71, 'ng/dL', NULL, NULL, NULL),
(52, 107, 4, 'rango', NULL, 'Ambos', 0.90, 2.30, 'ng/dL', NULL, NULL, NULL),
(53, 107, 6, 'rango', NULL, 'Ambos', 0.80, 1.80, 'ng/dL', NULL, NULL, NULL),
(54, 107, 7, 'rango', NULL, 'Ambos', 0.80, 1.60, 'ng/dL', NULL, NULL, NULL),
(55, 246, 10, 'rango', 'MENORES A 75 AÑOS', 'Ambos', 0.00, 300.00, 'pg/mL', NULL, NULL, NULL),
(56, 246, 10, 'rango', 'MAYOR O IGUAL A 75 AÑOS', 'Ambos', 0.00, 450.00, 'pg/mL', NULL, NULL, NULL),
(57, 224, 10, '<', 'BAJO RIESGO', 'Ambos', NULL, 1.00, 'mg/dL', 'LOS PACIENTES CON ELEVADAS CONCENTRACIONES DE HSCPR TIENEN UN RIESGO ELEVADO DE DESARROLLAR UN INFARTO MIOCARDICO Y UNA SEVERA ENFERMEDAD VASCULAR PERIFERICA.', NULL, NULL),
(58, 224, 10, 'rango', 'MEDIANO RIESGO', 'Ambos', 1.00, 3.00, 'mg/dL', NULL, NULL, NULL),
(59, 224, 10, '>', 'ALTO RIESGO', 'Ambos', 3.00, NULL, 'mg/dL', NULL, NULL, NULL),
(60, 243, 10, 'rango', 'NORMAL O BAJO RIESGO', 'Ambos', 0.00, 4.00, 'ng/mL', NULL, NULL, NULL),
(61, 243, 10, 'rango', 'MEDIANO RIESGO', 'Ambos', 4.00, 10.00, 'ng/mL', NULL, NULL, NULL),
(62, 243, 10, '>', 'ALTO RIESGO', 'Ambos', 10.00, NULL, 'ng/mL', NULL, NULL, NULL),
(63, 244, 10, '<', NULL, 'Masculino', NULL, 1.00, 'ng/mL', NULL, NULL, NULL),
(64, 199, 10, 'rango', NULL, 'Ambos', 60.00, 110.00, 'mg/dL', NULL, NULL, NULL),
(65, 209, 10, '<', NULL, 'Ambos', NULL, 200.00, 'mg/dL', NULL, NULL, NULL),
(66, 210, 10, '<', NULL, 'Ambos', NULL, 180.00, 'mg/dL', NULL, NULL, NULL),
(67, 211, 10, '<', NULL, 'Ambos', NULL, 140.00, 'mg/dL', NULL, NULL, NULL),
(68, 212, 10, '<', NULL, 'Ambos', NULL, 120.00, 'mg/dL', NULL, NULL, NULL),
(69, 213, 10, '<', NULL, 'Ambos', NULL, 100.00, 'mg/dL', NULL, NULL, NULL),
(70, 205, 10, '<', NULL, 'Ambos', NULL, 200.00, 'mg/dL', NULL, NULL, NULL),
(71, 206, 10, '<', NULL, 'Ambos', NULL, 180.00, 'mg/dL', NULL, NULL, NULL),
(72, 207, 10, '<', NULL, 'Ambos', NULL, 140.00, 'mg/dL', NULL, NULL, NULL),
(73, 202, 10, '<', NULL, 'Ambos', NULL, 200.00, 'mg/dL', NULL, NULL, NULL),
(74, 203, 10, '<', NULL, 'Ambos', NULL, 180.00, 'mg/dL', NULL, NULL, NULL),
(75, 200, 10, '<', NULL, 'Ambos', NULL, 140.00, 'mg/dL', NULL, NULL, NULL),
(76, 226, 10, '<', NULL, 'Ambos', NULL, 140.00, 'mg/dL', NULL, NULL, NULL),
(77, 225, 10, 'rango', NULL, 'Ambos', 60.00, 110.00, 'mg/dL', NULL, NULL, NULL),
(78, 208, 10, 'rango', NULL, 'Ambos', 60.00, 110.00, 'mg/dL', NULL, NULL, NULL),
(79, 204, 10, 'rango', NULL, 'Ambos', 60.00, 110.00, 'mg/dL', NULL, NULL, NULL),
(80, 201, 10, 'rango', NULL, 'Ambos', 60.00, 110.00, 'mg/dL', NULL, NULL, NULL),
(82, 139, 10, 'rango', NULL, 'Ambos', 3800000.00, 5800000.00, 'mm³', NULL, NULL, NULL),
(83, 139, 5, 'rango', NULL, 'Ambos', 3700000.00, 5500000.00, 'mm³', NULL, NULL, NULL),
(84, 140, 10, 'rango', NULL, 'Ambos', 37.00, 53.00, '%', NULL, NULL, NULL),
(85, 140, 5, 'rango', NULL, 'Ambos', 35.00, 40.00, '%', NULL, NULL, NULL),
(86, 141, 10, 'rango', NULL, 'Ambos', 12.00, 17.00, 'gr/dl', NULL, NULL, NULL),
(87, 141, 5, 'rango', NULL, 'Ambos', 11.50, 13.50, 'gr/dl', NULL, NULL, NULL),
(88, 142, 10, 'rango', NULL, 'Ambos', 80.00, 110.00, 'fL', NULL, NULL, NULL),
(89, 142, 5, 'rango', NULL, 'Ambos', 80.00, 110.00, 'fL', NULL, NULL, NULL),
(90, 143, 10, 'rango', NULL, 'Ambos', 26.00, 38.00, 'pg', NULL, NULL, NULL),
(92, 144, 10, 'rango', NULL, 'Ambos', 31.00, 37.00, 'gr/dl', NULL, NULL, NULL),
(93, 144, 5, 'rango', NULL, 'Ambos', 31.00, 37.00, 'gr/dl', NULL, NULL, NULL),
(94, 145, 10, 'rango', NULL, 'Ambos', 5000.00, 10000.00, 'mm³', NULL, NULL, NULL),
(95, 145, 5, 'rango', NULL, 'Ambos', 5000.00, 15000.00, 'mm³', NULL, NULL, NULL),
(96, 146, 10, 'rango', NULL, 'Ambos', 50.00, 70.00, '%', NULL, NULL, NULL),
(98, 147, 10, 'rango', NULL, 'Ambos', 20.00, 40.00, '%', NULL, NULL, NULL),
(99, 147, 5, 'rango', NULL, 'Ambos', 20.00, 40.00, '%', NULL, NULL, NULL),
(100, 148, 10, 'rango', NULL, 'Ambos', 0.00, 5.00, '%', NULL, NULL, NULL),
(101, 148, 5, 'rango', NULL, 'Ambos', 0.00, 5.00, '%', NULL, NULL, NULL),
(102, 149, 10, 'rango', NULL, 'Ambos', 2.00, 8.00, '%', NULL, NULL, NULL),
(103, 149, 5, 'rango', NULL, 'Ambos', 2.00, 8.00, '%', NULL, NULL, NULL),
(104, 150, 10, 'rango', NULL, 'Ambos', 0.00, 1.00, '%', NULL, NULL, NULL),
(105, 150, 5, 'rango', NULL, 'Ambos', 0.00, 1.00, '%', NULL, NULL, NULL),
(106, 151, 10, 'rango', NULL, 'Ambos', 150000.00, 450000.00, 'mm³', NULL, NULL, NULL),
(108, 152, 10, 'rango', NULL, 'Ambos', 6.50, 11.00, 'fL', NULL, NULL, NULL),
(109, 152, 5, 'rango', NULL, 'Ambos', 6.50, 11.00, 'fL', NULL, NULL, NULL),
(110, 153, 10, 'rango', NULL, 'Ambos', 10.00, 14.00, '%', NULL, NULL, NULL),
(111, 153, 5, 'rango', NULL, 'Ambos', 10.00, 14.00, '%', NULL, NULL, NULL),
(112, 93, 10, 'rango', NULL, 'Ambos', 135.00, 148.00, 'mmol/L', NULL, NULL, NULL),
(113, 92, 10, 'rango', NULL, 'Ambos', 3.50, 5.30, 'mmol/L', NULL, NULL, NULL),
(114, 89, 10, 'rango', NULL, 'Ambos', 98.00, 107.00, 'mmol/L', NULL, NULL, NULL),
(115, 88, 10, 'rango', NULL, 'Ambos', 8.60, 11.00, 'mg/dl', NULL, NULL, NULL),
(116, 91, 10, 'rango', NULL, 'Ambos', 1.60, 2.50, 'mg/dl', NULL, NULL, NULL),
(117, 90, 10, 'rango', NULL, 'Ambos', 2.50, 4.50, 'mg/dL', NULL, NULL, NULL),
(118, 234, 10, 'rango', NULL, 'Femenino', 11.00, 20.00, 'mg/Kg/24h', NULL, NULL, NULL),
(119, 234, 10, 'rango', NULL, 'Masculino', 21.00, 26.00, 'mg/Kg/24h', NULL, NULL, NULL),
(120, 235, 10, 'rango', 'HOMBRE', 'Masculino', 0.70, 1.30, 'mg/dl', NULL, NULL, NULL),
(121, 235, 10, 'rango', 'MUJER', 'Femenino', 0.60, 1.10, 'mg/dl', NULL, NULL, NULL),
(122, 236, 10, 'rango', NULL, 'Ambos', 800.00, 2000.00, 'ml', NULL, NULL, NULL),
(123, 240, 10, '<', NULL, 'Ambos', NULL, 150.00, 'mg/24 horas', NULL, NULL, NULL),
(124, 113, 10, 'rango', NULL, 'Femenino', 0.00, 15.00, 'mm/Hora', NULL, NULL, NULL),
(125, 113, 10, 'rango', NULL, 'Masculino', 0.00, 7.00, 'mm/Hora', NULL, NULL, NULL),
(126, 159, 10, '<', NULL, 'Ambos', NULL, 8.00, 'UI/mL', NULL, NULL, NULL),
(127, 171, 10, '<', NULL, 'Ambos', NULL, 6.00, 'mg/L', NULL, NULL, NULL),
(128, 125, 10, '<', NULL, 'Ambos', NULL, 200.00, 'UI/mL', NULL, NULL, NULL),
(129, 179, 10, '<', NULL, 'Ambos', NULL, 34.00, 'U/mL', NULL, NULL, NULL),
(130, 177, 10, '<', NULL, 'Ambos', NULL, 35.00, 'U/mL', NULL, NULL, NULL),
(131, 156, 10, '<', 'Negativo', 'Ambos', NULL, 0.90, 'COI', NULL, NULL, NULL),
(132, 156, 10, 'rango', 'Intermedio', 'Ambos', 0.90, 1.10, 'COI', NULL, NULL, NULL),
(133, 156, 10, '>', 'Positivo', 'Ambos', 1.10, NULL, 'COI', NULL, NULL, NULL),
(134, 157, 10, '<', 'Negativo', 'Ambos', NULL, 0.90, 'COI', NULL, NULL, NULL),
(135, 157, 10, 'rango', 'Intermedio', 'Ambos', 0.90, 1.10, 'COI', NULL, NULL, NULL),
(136, 157, 10, '>', 'Positivo', 'Ambos', 1.10, NULL, 'COI', NULL, NULL, NULL),
(137, 161, 10, '<', 'Negativo', 'Ambos', NULL, 0.80, 'U/mL', NULL, NULL, NULL),
(138, 161, 10, 'rango', 'Dudoso', 'Ambos', 0.80, 1.20, 'U/mL', NULL, NULL, NULL),
(139, 161, 10, '>', 'Positivo', 'Ambos', 1.20, NULL, 'U/mL', NULL, NULL, NULL),
(140, 86, 10, '>=', 'POSITIVO', 'Ambos', 1.00, NULL, 'COI', NULL, NULL, NULL),
(141, 86, 10, '<', 'NEGATIVO', 'Ambos', NULL, 1.00, 'COI', NULL, NULL, NULL),
(142, 164, 10, '<', 'Negativo', 'Ambos', NULL, 0.90, NULL, NULL, NULL, NULL),
(143, 164, 10, 'rango', 'Indeterminado', 'Ambos', 0.90, 1.00, NULL, NULL, NULL, NULL),
(144, 164, 10, '>', 'Positivo', 'Ambos', 1.00, NULL, NULL, NULL, NULL, NULL),
(145, 163, 10, '<', 'Negativo', 'Ambos', NULL, 0.90, NULL, NULL, NULL, NULL),
(146, 163, 10, 'rango', 'Indeterminado', 'Ambos', 0.90, 1.00, NULL, NULL, NULL, NULL),
(147, 163, 10, '>', 'Positivo', 'Ambos', 1.00, NULL, NULL, NULL, NULL, NULL),
(148, 162, 10, '<', 'Negativo', 'Ambos', NULL, 1.50, 'U', NULL, NULL, NULL),
(149, 162, 10, 'rango', 'Dudoso', 'Ambos', 1.51, 2.50, 'U', NULL, NULL, NULL),
(150, 162, 10, '>', 'Positivo', 'Ambos', 2.50, NULL, 'U', NULL, NULL, NULL),
(151, 99, 10, 'rango', NULL, 'Ambos', 2.70, 24.80, 'uIU/mL', NULL, NULL, NULL),
(152, 184, 10, 'rango', NULL, 'Ambos', 3.50, 5.50, 'g/dl', NULL, NULL, NULL),
(153, 81, 10, 'rango', NULL, 'Ambos', 11.10, 14.30, 'Segundos', NULL, NULL, NULL),
(154, 82, 10, 'rango', NULL, 'Ambos', 80.00, 105.00, '%', NULL, NULL, NULL),
(155, 75, 10, 'rango', NULL, 'Ambos', 20.00, 33.00, 'Segundos', NULL, NULL, NULL),
(156, 70, 10, 'rango', NULL, 'Ambos', 180.00, 380.00, 'mg/dL', NULL, NULL, NULL),
(157, 69, 10, '<', NULL, 'Ambos', NULL, 30.00, 'Segundos', NULL, NULL, NULL),
(158, 74, 10, 'rango', NULL, 'Ambos', 1.00, 5.00, 'Minutos', NULL, NULL, NULL),
(159, 72, 10, 'rango', NULL, 'Ambos', 5.00, 10.00, 'Minutos', NULL, NULL, NULL),
(160, 87, 10, '<', 'Negativo', 'Ambos', NULL, 100.00, 'ng/ml', NULL, NULL, NULL),
(161, 87, 10, '>=', 'Positivo', 'Ambos', 100.00, NULL, 'ng/ml', NULL, NULL, NULL),
(162, 248, 10, '<', 'Insuficiencia', 'Ambos', NULL, 19.00, 'ng/mL', NULL, NULL, NULL),
(163, 248, 10, 'rango', 'Deficiencia', 'Ambos', 20.00, 29.00, 'ng/mL', NULL, NULL, NULL),
(164, 248, 10, '>', 'Optimo', 'Ambos', 30.00, NULL, 'ng/mL', NULL, NULL, NULL),
(165, 248, 10, '>', 'Toxicidad', 'Ambos', 150.00, NULL, 'ng/mL', NULL, NULL, NULL),
(166, 249, 10, 'rango', NULL, 'Ambos', 0.00, 0.30, 'ng/mL', NULL, NULL, NULL),
(167, 173, 10, '<', 'Negativo', 'Ambos', NULL, 0.80, 'COI', NULL, NULL, NULL),
(168, 173, 10, 'rango', 'Indeterminado', 'Ambos', 0.80, 1.00, 'COI', NULL, NULL, NULL),
(169, 173, 10, '>', 'Positivo', 'Ambos', 1.00, NULL, 'COI', NULL, NULL, NULL),
(170, 250, 10, '<', 'Negativo', 'Ambos', NULL, 1.00, 'UI/ml', NULL, NULL, NULL),
(171, 250, 10, 'rango', 'Indeterminado', 'Ambos', 1.00, 3.00, 'UI/ml', NULL, NULL, NULL),
(172, 250, 10, '>', 'Positivo', 'Ambos', 3.00, NULL, 'UI/ml', NULL, NULL, NULL),
(173, 220, 10, 'rango', NULL, 'Ambos', 6.50, 8.30, 'g/dL', NULL, NULL, NULL),
(174, 221, 10, 'rango', NULL, 'Ambos', 3.50, 5.50, 'g/dl', NULL, NULL, NULL),
(175, 222, 10, 'rango', NULL, 'Ambos', 2.00, 3.50, 'g/dL', NULL, NULL, NULL),
(176, 223, 10, 'rango', NULL, 'Ambos', 1.00, 2.00, 'g/dL', NULL, NULL, NULL),
(177, 191, 10, 'rango', 'MUJER ', 'Femenino', 26.00, 140.00, 'U/L', NULL, NULL, NULL),
(178, 191, 10, 'rango', 'HOMBRE', 'Masculino', 38.00, 140.00, 'U/L', NULL, NULL, NULL),
(179, 192, 10, '<', NULL, 'Ambos', NULL, 25.00, 'U/L', NULL, NULL, NULL),
(180, 185, 10, '<', NULL, 'Ambos', NULL, 125.00, 'U/L', NULL, NULL, NULL),
(181, 217, 10, 'rango', NULL, 'Ambos', 12.00, 70.00, 'U/L', NULL, NULL, NULL),
(182, 195, 10, 'rango', NULL, 'Masculino', 24.00, 425.00, 'ng/ml', NULL, NULL, NULL),
(183, 195, 10, 'rango', NULL, 'Femenino', 13.00, 150.00, 'ng/ml', NULL, NULL, NULL),
(184, 228, 10, '<', NULL, 'Masculino', NULL, 40.00, 'U/L', NULL, NULL, NULL),
(185, 228, 10, '<', NULL, 'Femenino', NULL, 32.00, 'U/L', NULL, NULL, NULL),
(186, 218, 10, 'rango', NULL, 'Ambos', 10.00, 50.00, 'mg/dL', NULL, NULL, NULL),
(187, 219, 10, 'rango', NULL, 'Ambos', 7.00, 24.00, 'mg/dL', NULL, NULL, NULL),
(188, 180, 10, 'rango', 'NO FUMADORES', 'Ambos', 0.00, 5.00, 'ng/mL', NULL, NULL, NULL),
(189, 180, 10, 'rango', 'FUMADORES', 'Ambos', 0.00, 10.00, 'ng/mL', NULL, NULL, NULL),
(190, 214, 10, '<', 'NO DIABETICO', 'Ambos', NULL, 5.70, '%', NULL, NULL, NULL),
(191, 214, 10, 'rango', 'DIABETICO CONTROLADO', 'Ambos', 5.70, 6.50, '%', NULL, NULL, NULL),
(192, 214, 10, '>', 'DIABETICO MAL CONTROLADO', 'Ambos', 6.50, NULL, '%', NULL, NULL, NULL),
(193, 252, 10, 'rango', 'MUJERES MENORES DE 20a', 'Ambos', 0.47, 9.11, 'ng/mL', NULL, NULL, NULL),
(194, 252, 10, 'rango', 'MUJERES MENORES DE 30a', 'Ambos', 0.49, 8.91, 'ng/mL', NULL, NULL, NULL),
(195, 252, 10, 'rango', 'MUJERES DE 30-39 a', 'Ambos', 0.31, 7.86, 'ng/mL', NULL, NULL, NULL),
(196, 252, 10, '<=', 'MUJERES DE 40-50 a', 'Ambos', NULL, 5.07, 'ng/mL', NULL, NULL, NULL),
(197, 251, 10, 'rango', NULL, 'Ambos', 6.00, 7.00, NULL, 'pH ácido: Un pH menor de 6.0 es evidencia sugestiva de mala absorción de azúcares en niños y algunos adultos. \r\npH alcalino: Un pH mayor de 7.0 indica trastornos digestivos con aumento de la flora proteolítica ', NULL, NULL),
(198, 193, 10, 'rango', 'HOMBRE', 'Masculino', 0.70, 1.30, 'mg/dl', NULL, NULL, NULL),
(199, 193, 10, 'rango', 'MUJER', 'Femenino', 0.60, 1.10, 'mg/dl', NULL, NULL, NULL),
(201, 190, 9, 'rango', NULL, 'Ambos', 0.00, 190.00, 'mg/dL', NULL, NULL, NULL),
(202, 193, 6, 'rango', 'NIÑOS', 'Ambos', 0.50, 1.00, 'mg/dl', NULL, NULL, NULL),
(203, 104, 9, 'rango', NULL, 'Ambos', 2.00, 4.40, 'pg/mL', NULL, NULL, NULL),
(204, 107, 9, 'rango', NULL, 'Ambos', 0.98, 1.71, 'ng/dL', NULL, NULL, NULL),
(205, 109, 10, 'rango', NULL, 'Ambos', 0.30, 4.20, 'uUI/mL', NULL, NULL, NULL),
(206, 258, 10, '<', NULL, 'Ambos', NULL, 15.00, 'mg/L', NULL, NULL, NULL),
(207, 189, 10, '<', NULL, 'Ambos', NULL, 150.00, 'mg/dL', NULL, NULL, NULL),
(208, 105, 9, 'rango', NULL, 'Ambos', 0.80, 2.00, 'ng/mL', NULL, NULL, NULL),
(209, 106, 9, 'rango', NULL, 'Ambos', 5.10, 14.10, 'ug/dL', NULL, NULL, NULL),
(210, 233, 10, 'rango', 'MUJER', 'Femenino', 88.00, 128.00, 'mL/min', NULL, NULL, NULL),
(211, 233, 10, 'rango', 'HOMBRE ', 'Masculino', 97.00, 137.00, 'mL/min', NULL, NULL, NULL),
(212, 100, 10, 'rango', NULL, 'Ambos', 15.00, 180.00, 'uIU/mL', NULL, NULL, NULL),
(213, 108, 10, 'rango', 'Hombres', 'Masculino', 1.71, 7.87, 'ng/mL', NULL, NULL, NULL),
(214, 108, 10, 'rango', 'Mujeres', 'Femenino', 0.06, 0.82, 'ng/mL', NULL, NULL, NULL),
(215, 108, 5, 'rango', 'Niños hasta 1 año', 'Ambos', 0.12, 0.21, 'ng/mL', NULL, NULL, NULL),
(216, 108, 6, 'rango', 'Niños 1 a 6 años', 'Ambos', 0.03, 0.32, 'ng/mL', NULL, NULL, NULL),
(217, 108, 6, 'rango', 'Niños de 7 a 12 años', 'Ambos', 0.03, 0.68, 'ng/mL', NULL, NULL, NULL),
(218, 108, 7, 'rango', 'Niños de 13 a 17 años', 'Ambos', 0.28, 11.10, 'ng/mL', NULL, NULL, NULL),
(219, 103, 10, 'rango', 'Hombres', 'Masculino', 2.52, 13.23, 'ng/mL', NULL, NULL, NULL),
(220, 103, 10, 'rango', 'Mujeres: Pre-menopausea', 'Femenino', 3.27, 26.81, 'ng/mL', NULL, NULL, NULL),
(221, 103, 10, 'rango', 'Mujeres: Post-menopausea', 'Femenino', 2.68, 19.72, 'ng/mL', NULL, NULL, NULL),
(222, 264, 10, '<=', 'Normal', 'Ambos', NULL, 30.00, 'mg/g', NULL, NULL, NULL),
(223, 264, 10, 'rango', 'Microalbuminuria', 'Ambos', 30.00, 300.00, 'mg/g', NULL, NULL, NULL),
(224, 264, 10, '>', 'Macroalbuminuria', 'Ambos', 300.00, NULL, 'mg/g', NULL, NULL, NULL),
(225, 265, 10, 'rango', NULL, 'Masculino', 2.00, 6.00, 'mL', NULL, NULL, NULL),
(226, 269, 10, 'rango', NULL, 'Masculino', 7.20, 7.80, NULL, NULL, NULL, NULL),
(227, 94, 10, '<', 'Mujer no embarazada', 'Femenino', 0.00, 5.00, 'mIU/mL', NULL, NULL, NULL),
(228, 94, 10, 'rango', '3 - 7 dias', 'Femenino', 5.00, 50.00, 'mIU/mL', NULL, NULL, NULL),
(229, 94, 10, 'rango', '1 - 2 semanas', 'Femenino', 10.00, 472.00, 'mIU/mL', NULL, NULL, NULL),
(230, 94, 10, 'rango', '2 - 3 semanas', 'Femenino', 90.00, 4590.00, 'mIU/mL', NULL, NULL, NULL),
(231, 94, 10, 'rango', '3 - 4 semanas', 'Femenino', 462.00, 10940.00, 'mIU/mL', NULL, NULL, NULL),
(232, 94, 10, 'rango', '4 - 5 semanas', 'Femenino', 1065.00, 68248.00, 'mIU/mL', NULL, NULL, NULL),
(233, 94, 10, 'rango', '5 - 6 semanas', 'Femenino', 7458.00, 118515.00, 'mIU/mL', NULL, NULL, NULL),
(234, 94, 10, 'rango', '6 - 7 semanas', 'Femenino', 14423.00, 175638.00, 'mIU/mL', NULL, NULL, NULL),
(235, 94, 10, 'rango', '7 - 8 semanas', 'Femenino', 31510.00, 184628.00, 'mIU/mL', NULL, NULL, NULL),
(236, 94, 10, 'rango', '8 - 12 semanas', 'Femenino', 28639.00, 224919.00, 'mIU/mL', NULL, NULL, NULL),
(237, 94, 10, 'rango', '12 - 16 semanas', 'Femenino', 9870.00, 106917.00, 'mIU/mL', NULL, NULL, NULL),
(238, 94, 10, 'rango', '16 - 18 semanas', 'Femenino', 7924.00, 56552.00, 'mIU/mL', NULL, NULL, NULL),
(239, 303, 10, 'rango', NULL, 'Ambos', 90.00, 120.00, 'mL/min', NULL, NULL, NULL),
(240, 304, 8, 'rango', NULL, 'Ambos', 0.20, 1.00, 'mg/dL', NULL, NULL, NULL),
(241, 246, 10, 'rango', '< 75 AÑOS', 'Ambos', 0.00, 300.00, 'pg/mL', NULL, NULL, NULL),
(242, 246, 10, 'rango', '≥ 75 AÑOS', 'Ambos', 0.00, 450.00, 'pg/mL', NULL, NULL, NULL),
(243, 178, 10, '<=', NULL, 'Ambos', NULL, 25.00, 'U/ml', NULL, NULL, NULL),
(244, 198, 8, '<=', 'HOMBRES', 'Masculino', NULL, 55.00, 'U/L', NULL, NULL, NULL),
(245, 198, 8, '<=', 'MUJERES ', 'Femenino', NULL, 38.00, 'U/L', NULL, NULL, NULL),
(246, 182, 10, 'rango', 'Normal o Bajo Riesgo', 'Ambos', 0.00, 4.00, 'ng/mL', NULL, NULL, NULL),
(247, 182, 10, 'rango', 'Mediano Riesgo', 'Ambos', 4.00, 10.00, 'ng/mL', NULL, NULL, NULL),
(248, 182, 10, '>', 'Alto Riesgo', 'Ambos', 10.00, NULL, 'ng/mL', NULL, NULL, NULL),
(249, 309, 10, '<', NULL, 'Ambos', NULL, 15.00, 'mg/dL', NULL, NULL, NULL),
(251, 310, 10, 'rango', NULL, 'Ambos', 45.50, 208.20, 'ng/mL', NULL, NULL, NULL),
(252, 311, 10, 'rango', NULL, 'Ambos', 2.30, 11.90, 'ug/dL', NULL, NULL, NULL),
(253, 312, 10, 'rango', 'Hombres', 'Ambos', 0.70, 12.40, 'mUI/mL', NULL, NULL, NULL),
(254, 312, 10, 'rango', 'Niños 0 a 3 años', 'Ambos', 0.00, 10.00, 'mUI/mL', NULL, NULL, NULL),
(255, 312, 10, 'rango', 'Niños 4 a 9 años', 'Ambos', 0.00, 1.80, 'mUI/mL', NULL, NULL, NULL),
(256, 312, 10, 'rango', 'Fase Folicular', 'Ambos', 3.50, 12.50, 'mUI/mL', NULL, NULL, NULL),
(257, 312, 10, 'rango', 'Fase Folicular dia 2-3', 'Ambos', 3.00, 14.40, 'mUI/mL', NULL, NULL, NULL),
(258, 312, 10, 'rango', 'Ovulación +- 3 dias', 'Ambos', 4.70, 21.50, 'mUI/mL', NULL, NULL, NULL),
(259, 312, 10, 'rango', 'Fase Lutea', 'Ambos', 1.70, 7.70, 'mUI/mL', NULL, NULL, NULL),
(260, 312, 10, 'rango', 'Post Menopausia', 'Ambos', 25.80, 134.80, 'mUI/mL', NULL, NULL, NULL),
(261, 312, 10, 'rango', 'Anticonceptivos Orales', 'Ambos', 0.00, 4.90, 'mUI/mL', NULL, NULL, NULL),
(262, 101, 10, 'rango', 'Hombres', 'Ambos', 0.50, 12.43, 'mIU/mL', NULL, NULL, NULL),
(263, 101, 10, 'rango', 'Niños 0 - 9 años', 'Ambos', 0.00, 3.70, 'mIU/mL', NULL, NULL, NULL),
(264, 101, 10, 'rango', 'Fase Folicular', 'Ambos', 1.60, 12.18, 'mIU/mL', NULL, NULL, NULL),
(265, 101, 10, 'rango', 'Ovulación + - 3 dias', 'Ambos', 14.00, 95.60, 'mIU/mL', NULL, NULL, NULL),
(266, 101, 10, 'rango', 'Fase Lútea', 'Ambos', 0.50, 15.10, 'mIU/mL', NULL, NULL, NULL),
(267, 101, 10, 'rango', 'Perimenstrual + - 8 dias', 'Ambos', 0.00, 12.00, 'mIU/mL', NULL, NULL, NULL),
(269, 101, 10, 'rango', 'Post Menopausia', 'Ambos', 5.04, 63.11, 'mIU/mL', NULL, NULL, NULL),
(270, 101, 10, 'rango', 'Anticonceptivos Orales', 'Ambos', 0.00, 8.00, 'mIU/mL', NULL, NULL, NULL),
(271, 302, 10, 'rango', 'Hombres', 'Ambos', 7.63, 42.60, 'pg/mL', NULL, NULL, NULL),
(272, 302, 10, 'rango', 'Fase Folicular', 'Ambos', 12.50, 166.00, 'pg/mL', NULL, NULL, NULL),
(273, 302, 10, 'rango', 'Ovulación +- 3 dias', 'Ambos', 85.80, 498.00, 'pg/mL', NULL, NULL, NULL),
(274, 302, 10, 'rango', 'Fase Lútea', 'Ambos', 43.80, 211.00, 'pg/mL', NULL, NULL, NULL),
(275, 302, 10, 'rango', 'Postmenopausia', 'Ambos', 0.00, 54.70, 'pg/mL', NULL, NULL, NULL),
(276, 302, 10, 'rango', 'Gestación 1er trimestre', 'Ambos', 215.00, 4300.00, 'pg/mL', NULL, NULL, NULL),
(277, 302, 10, 'rango', 'Niños 1 a 10 años', 'Ambos', 5.00, 27.00, 'pg/mL', NULL, NULL, NULL),
(278, 314, 10, 'rango', NULL, 'Ambos', 7.20, 63.30, 'pg/mL', NULL, NULL, NULL),
(279, 234, 10, 'rango', 'MUJER', 'Femenino', 11.00, 20.00, 'mg/Kg/24h', NULL, NULL, NULL),
(280, 234, 10, 'rango', 'HOMBRE', 'Masculino', 21.00, 26.00, 'mg/Kg/24h', NULL, NULL, NULL),
(281, 316, 10, '<', NULL, 'Ambos', NULL, 1.50, 'mg/dL', NULL, NULL, NULL),
(282, 304, 4, 'rango', NULL, 'Ambos', 0.10, 1.00, 'mg/dL', NULL, NULL, NULL),
(283, 113, 6, 'rango', NULL, 'Ambos', 0.00, 20.00, 'mm/Hora', NULL, NULL, NULL),
(284, 109, 10, 'rango', NULL, 'Ambos', 0.30, 4.20, 'uUI/mL', NULL, NULL, NULL),
(285, 109, 7, 'rango', NULL, 'Ambos', 0.40, 4.00, 'uUI/mL', NULL, NULL, NULL),
(286, 109, 5, 'rango', NULL, 'Ambos', 0.80, 8.20, 'uUI/mL', NULL, NULL, NULL),
(287, 109, 6, 'rango', NULL, 'Ambos', 0.60, 6.00, 'uUI/mL', NULL, NULL, NULL),
(288, 109, 8, 'rango', NULL, 'Ambos', 0.30, 4.20, 'uUI/mL', NULL, NULL, NULL),
(289, 182, 10, 'rango', 'NORMAL O BAJO RIESGO', 'Ambos', 0.00, 4.00, 'ng/mL', NULL, NULL, NULL),
(290, 182, 10, 'rango', 'MEDIANO RIESGO', 'Ambos', 4.00, 10.00, 'ng/mL', NULL, NULL, NULL),
(291, 182, 10, '>', 'ALTO RIESGO', 'Ambos', 10.00, NULL, 'ng/mL', NULL, NULL, NULL),
(292, 243, 10, 'rango', 'NORMAL O BAJO RIESGO', 'Ambos', 0.00, 4.00, 'ng/mL', NULL, NULL, NULL),
(293, 243, 10, 'rango', 'MEDIANO RIESGO', 'Ambos', 4.00, 10.00, 'ng/mL', NULL, NULL, NULL),
(294, 243, 10, '>', 'ALTO RIESGO', 'Ambos', 10.00, NULL, 'ng/mL', NULL, NULL, NULL),
(295, 246, 10, 'rango', 'MENORES A 75 AÑOS', 'Ambos', 0.00, 300.00, 'pg/mL', NULL, NULL, NULL),
(296, 246, 10, 'rango', 'MAYOR O IGUAL A 75 AÑOS', 'Ambos', 0.00, 450.00, 'pg/mL', NULL, NULL, NULL),
(297, 69, 10, '<', NULL, 'Ambos', NULL, 30.00, 'Segundos', NULL, NULL, NULL),
(298, 182, 10, 'rango', 'NORMAL O BAJO RIESGO', 'Ambos', 0.00, 4.00, 'ng/mL', NULL, NULL, NULL),
(299, 182, 10, 'rango', 'MEDIANO RIESGO', 'Ambos', 4.00, 10.00, 'ng/mL', NULL, NULL, NULL),
(300, 182, 10, '>', 'ALTO RIESGO', 'Ambos', 10.00, NULL, 'ng/mL', NULL, NULL, NULL),
(301, 243, 10, 'rango', 'NORMAL O BAJO RIESGO', 'Ambos', 0.00, 4.00, 'ng/mL', NULL, NULL, NULL),
(302, 243, 10, 'rango', 'MEDIANO RIESGO', 'Ambos', 4.00, 10.00, 'ng/mL', NULL, NULL, NULL),
(303, 243, 10, '>', 'ALTO RIESGO', 'Ambos', 10.00, NULL, 'ng/mL', NULL, NULL, NULL),
(304, 98, 10, 'rango', NULL, 'Ambos', 15.00, 65.00, 'pg/mL', NULL, NULL, NULL),
(305, 176, 10, '<=', NULL, 'Ambos', NULL, 10.00, 'ng/mL', NULL, NULL, NULL),
(306, 193, 7, 'rango', NULL, 'Ambos', 0.50, 1.00, NULL, NULL, NULL, NULL),
(307, 323, 10, '<', 'No Reactivo', 'Ambos', NULL, 0.90, 'RLU', NULL, NULL, NULL),
(308, 323, 10, 'rango', 'Indeterminado', 'Ambos', 0.90, 1.10, 'RLU', NULL, NULL, NULL),
(309, 323, 10, '>=', 'Reactivo', 'Ambos', 1.10, NULL, 'RLU', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject` (`subject_type`,`subject_id`),
  ADD KEY `causer` (`causer_type`,`causer_id`),
  ADD KEY `activity_log_log_name_index` (`log_name`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clientes_numeroexp_unique` (`NumeroExp`),
  ADD UNIQUE KEY `clientes_dui_unique` (`dui`);

--
-- Indexes for table `codigos`
--
ALTER TABLE `codigos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigos_codigo_unique` (`codigo`);

--
-- Indexes for table `detalle_orden`
--
ALTER TABLE `detalle_orden`
  ADD PRIMARY KEY (`id`),
  ADD KEY `detalle_orden_orden_id_foreign` (`orden_id`),
  ADD KEY `detalle_orden_examen_id_foreign` (`examen_id`),
  ADD KEY `detalle_orden_perfil_id_foreign` (`perfil_id`);

--
-- Indexes for table `detalle_orden_perfils`
--
ALTER TABLE `detalle_orden_perfils`
  ADD PRIMARY KEY (`id`),
  ADD KEY `detalle_orden_perfils_orden_id_foreign` (`orden_id`),
  ADD KEY `detalle_orden_perfils_perfil_id_foreign` (`perfil_id`);

--
-- Indexes for table `detalle_perfil`
--
ALTER TABLE `detalle_perfil`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `detalle_perfil_perfil_id_examen_id_unique` (`perfil_id`,`examen_id`),
  ADD KEY `detalle_perfil_examen_id_foreign` (`examen_id`);

--
-- Indexes for table `examens`
--
ALTER TABLE `examens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `examens_tipo_examen_id_foreign` (`tipo_examen_id`);

--
-- Indexes for table `examen_muestra`
--
ALTER TABLE `examen_muestra`
  ADD PRIMARY KEY (`examen_id`,`muestra_id`),
  ADD KEY `examen_muestra_muestra_id_foreign` (`muestra_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `grupos_etarios`
--
ALTER TABLE `grupos_etarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grupos_etarios_nombre_unique` (`nombre`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medicos`
--
ALTER TABLE `medicos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `muestras`
--
ALTER TABLE `muestras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `muestras_nombre_unique` (`nombre`);

--
-- Indexes for table `ordens`
--
ALTER TABLE `ordens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ordens_cliente_id_foreign` (`cliente_id`),
  ADD KEY `ordens_toma_muestra_user_id_foreign` (`toma_muestra_user_id`),
  ADD KEY `ordens_codigo_id_foreign` (`codigo_id`),
  ADD KEY `ordens_medico_id_foreign` (`medico_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `perfil`
--
ALTER TABLE `perfil`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `pruebas`
--
ALTER TABLE `pruebas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pruebas_examen_id_foreign` (`examen_id`),
  ADD KEY `pruebas_tipo_prueba_id_foreign` (`tipo_prueba_id`);

--
-- Indexes for table `resultados`
--
ALTER TABLE `resultados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resultados_detalle_orden_id_foreign` (`detalle_orden_id`),
  ADD KEY `resultados_prueba_id_foreign` (`prueba_id`),
  ADD KEY `resultados_user_id_foreign` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `tipos_pruebas`
--
ALTER TABLE `tipos_pruebas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipos_pruebas_nombre_unique` (`nombre`);

--
-- Indexes for table `tipo_examens`
--
ALTER TABLE `tipo_examens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_nickname_unique` (`nickname`);

--
-- Indexes for table `valor_referencias`
--
ALTER TABLE `valor_referencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `valor_referencias_grupo_etario_id_foreign` (`grupo_etario_id`),
  ADD KEY `valor_referencias_prueba_id_foreign` (`prueba_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `codigos`
--
ALTER TABLE `codigos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detalle_orden`
--
ALTER TABLE `detalle_orden`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `detalle_orden_perfils`
--
ALTER TABLE `detalle_orden_perfils`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detalle_perfil`
--
ALTER TABLE `detalle_perfil`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `examens`
--
ALTER TABLE `examens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grupos_etarios`
--
ALTER TABLE `grupos_etarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicos`
--
ALTER TABLE `medicos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `muestras`
--
ALTER TABLE `muestras`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `ordens`
--
ALTER TABLE `ordens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `perfil`
--
ALTER TABLE `perfil`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT for table `pruebas`
--
ALTER TABLE `pruebas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=329;

--
-- AUTO_INCREMENT for table `resultados`
--
ALTER TABLE `resultados`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tipos_pruebas`
--
ALTER TABLE `tipos_pruebas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tipo_examens`
--
ALTER TABLE `tipo_examens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `valor_referencias`
--
ALTER TABLE `valor_referencias`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=310;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detalle_orden`
--
ALTER TABLE `detalle_orden`
  ADD CONSTRAINT `detalle_orden_examen_id_foreign` FOREIGN KEY (`examen_id`) REFERENCES `examens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_orden_orden_id_foreign` FOREIGN KEY (`orden_id`) REFERENCES `ordens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_orden_perfil_id_foreign` FOREIGN KEY (`perfil_id`) REFERENCES `perfil` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `detalle_orden_perfils`
--
ALTER TABLE `detalle_orden_perfils`
  ADD CONSTRAINT `detalle_orden_perfils_orden_id_foreign` FOREIGN KEY (`orden_id`) REFERENCES `ordens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_orden_perfils_perfil_id_foreign` FOREIGN KEY (`perfil_id`) REFERENCES `perfil` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `detalle_perfil`
--
ALTER TABLE `detalle_perfil`
  ADD CONSTRAINT `detalle_perfil_examen_id_foreign` FOREIGN KEY (`examen_id`) REFERENCES `examens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_perfil_perfil_id_foreign` FOREIGN KEY (`perfil_id`) REFERENCES `perfil` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `examens`
--
ALTER TABLE `examens`
  ADD CONSTRAINT `examens_tipo_examen_id_foreign` FOREIGN KEY (`tipo_examen_id`) REFERENCES `tipo_examens` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `examen_muestra`
--
ALTER TABLE `examen_muestra`
  ADD CONSTRAINT `examen_muestra_examen_id_foreign` FOREIGN KEY (`examen_id`) REFERENCES `examens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `examen_muestra_muestra_id_foreign` FOREIGN KEY (`muestra_id`) REFERENCES `muestras` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ordens`
--
ALTER TABLE `ordens`
  ADD CONSTRAINT `ordens_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ordens_codigo_id_foreign` FOREIGN KEY (`codigo_id`) REFERENCES `codigos` (`id`),
  ADD CONSTRAINT `ordens_medico_id_foreign` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ordens_toma_muestra_user_id_foreign` FOREIGN KEY (`toma_muestra_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pruebas`
--
ALTER TABLE `pruebas`
  ADD CONSTRAINT `pruebas_examen_id_foreign` FOREIGN KEY (`examen_id`) REFERENCES `examens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pruebas_tipo_prueba_id_foreign` FOREIGN KEY (`tipo_prueba_id`) REFERENCES `tipos_pruebas` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `resultados`
--
ALTER TABLE `resultados`
  ADD CONSTRAINT `resultados_detalle_orden_id_foreign` FOREIGN KEY (`detalle_orden_id`) REFERENCES `detalle_orden` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resultados_prueba_id_foreign` FOREIGN KEY (`prueba_id`) REFERENCES `pruebas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resultados_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `valor_referencias`
--
ALTER TABLE `valor_referencias`
  ADD CONSTRAINT `valor_referencias_grupo_etario_id_foreign` FOREIGN KEY (`grupo_etario_id`) REFERENCES `grupos_etarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `valor_referencias_prueba_id_foreign` FOREIGN KEY (`prueba_id`) REFERENCES `pruebas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
