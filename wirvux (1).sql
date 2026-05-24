-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 24-05-2026 a las 16:18:53
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `wirvux`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id` int(11) NOT NULL,
  `id_emisor` int(11) NOT NULL,
  `id_receptor` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `id_respuesta` int(11) DEFAULT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha_envio` datetime DEFAULT current_timestamp(),
  `editado` tinyint(1) DEFAULT 0,
  `eliminado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mensajes`
--

INSERT INTO `mensajes` (`id`, `id_emisor`, `id_receptor`, `mensaje`, `id_respuesta`, `leido`, `fecha_envio`, `editado`, `eliminado`) VALUES
(5, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-02-11 15:18:40', 0, 1),
(6, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-02-13 15:18:53', 1, 1),
(7, 4, 3, 'Mensaje eliminado', 6, 0, '2026-02-13 15:19:05', 1, 1),
(8, 4, 3, 'Mensaje eliminado', 6, 0, '2026-02-13 21:16:32', 0, 1),
(12, 3, 4, 'vale ahora creo si', NULL, 0, '2026-05-01 11:15:40', 0, 0),
(13, 3, 4, 'prueba2', NULL, 0, '2026-05-01 11:15:45', 0, 0),
(14, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-02 13:48:52', 1, 1),
(15, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-02 14:20:15', 0, 1),
(16, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-02 14:20:20', 1, 1),
(17, 4, 3, 'sfsd', 16, 0, '2026-05-02 14:23:09', 0, 0),
(18, 3, 4, 'aaaa', 16, 0, '2026-05-02 14:27:22', 1, 0),
(19, 4, 3, 'ssa', NULL, 0, '2026-05-02 14:27:36', 0, 0),
(20, 4, 3, 's', NULL, 0, '2026-05-02 14:27:38', 0, 0),
(21, 4, 3, 's', NULL, 0, '2026-05-02 14:27:40', 0, 0),
(22, 4, 3, 'safgd', NULL, 0, '2026-05-02 14:27:41', 1, 0),
(23, 4, 3, 'as', NULL, 0, '2026-05-02 14:27:43', 0, 0),
(24, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-02 14:27:45', 1, 1),
(25, 4, 3, 's', NULL, 0, '2026-05-02 14:27:47', 0, 0),
(26, 4, 3, 'd', NULL, 0, '2026-05-02 14:27:50', 0, 0),
(27, 4, 3, 'd', NULL, 0, '2026-05-02 14:27:51', 0, 0),
(28, 4, 3, 'd', NULL, 0, '2026-05-02 14:27:54', 0, 0),
(29, 4, 3, 'd', NULL, 0, '2026-05-02 14:27:55', 0, 0),
(30, 3, 4, 'Mensaje eliminado', 16, 0, '2026-05-02 14:28:51', 0, 1),
(31, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-02 16:37:18', 0, 1),
(32, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-02 16:39:32', 0, 1),
(33, 3, 4, 'ds', 16, 0, '2026-05-02 16:46:33', 0, 0),
(34, 3, 4, 'Mensaje eliminado', NULL, 0, '2026-05-02 16:46:37', 0, 1),
(35, 3, 4, 'ddsaad', 32, 0, '2026-05-02 17:41:31', 1, 0),
(36, 3, 4, 'ds', 19, 0, '2026-05-02 17:41:42', 0, 0),
(37, 3, 4, 'Mensaje eliminado', 32, 0, '2026-05-02 17:43:05', 1, 1),
(38, 3, 4, 'Mensaje eliminado', 32, 0, '2026-05-02 17:49:48', 1, 1),
(39, 3, 4, 'fdgd', 32, 0, '2026-05-02 17:53:16', 0, 0),
(40, 4, 3, 'Mensaje eliminado', 33, 0, '2026-05-02 18:07:21', 1, 1),
(41, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-02 19:39:10', 0, 1),
(42, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-02 19:54:45', 1, 1),
(43, 4, 3, 'y7', NULL, 0, '2026-05-03 18:05:15', 1, 0),
(44, 4, 3, 'dsf', 39, 0, '2026-05-03 18:29:21', 0, 0),
(45, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-03 18:45:58', 0, 1),
(46, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-03 19:01:22', 0, 1),
(47, 3, 4, 'Mensaje eliminado', NULL, 0, '2026-05-03 19:15:48', 1, 1),
(48, 3, 4, 'f', 47, 0, '2026-05-03 19:25:57', 0, 0),
(49, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-03 20:16:45', 0, 1),
(50, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-03 20:19:02', 0, 1),
(51, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-03 20:21:31', 0, 1),
(52, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-03 20:25:51', 0, 1),
(53, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-03 20:27:52', 0, 1),
(54, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-03 20:33:56', 0, 1),
(55, 3, 4, 'Mensaje eliminado', 44, 0, '2026-05-03 20:49:32', 1, 1),
(56, 3, 4, 'h', 29, 0, '2026-05-03 20:49:56', 0, 0),
(57, 3, 4, 'hh', 54, 0, '2026-05-03 20:50:26', 0, 0),
(58, 3, 4, 'dgfdgf', NULL, 0, '2026-05-03 20:55:01', 0, 0),
(59, 4, 3, 'dfggdf', NULL, 0, '2026-05-03 20:57:58', 0, 0),
(60, 4, 3, 'Mensaje eliminado', 58, 0, '2026-05-03 20:58:01', 1, 1),
(61, 3, 4, 'Mensaje eliminado', NULL, 0, '2026-05-03 21:09:41', 1, 1),
(62, 3, 4, 'dfsdsf', 59, 0, '2026-05-03 21:09:59', 0, 0),
(63, 3, 4, 'Hola buenas me interesa el trabajo', NULL, 0, '2026-05-03 21:10:31', 0, 0),
(64, 4, 3, 'dfsd', 63, 0, '2026-05-03 21:12:49', 0, 0),
(65, 4, 3, 'Mensaje eliminado', NULL, 0, '2026-05-03 21:13:07', 0, 1),
(66, 4, 3, '--- NUEVA PROPUESTA DE PROYECTO ---\nProyecto: prueba1\nDescripción: Web plana con titulo y un parrafo\n-----------------------------------\nMENSAJE DEL CLIENTE:\nHola, me gustaría proponerte este proyecto porque encaja perfectamente con tu perfil. Quedo a la espera de tu respuesta para discutir los detalles.\n[ID_PROYECTO: 14]', NULL, 0, '2026-05-16 11:25:42', 0, 0),
(67, 4, 3, '--- NUEVA PROPUESTA DE PROYECTO ---\nProyecto: pruebas\nDescripción: titulo y parrafo en una web estatica\n-----------------------------------\nMENSAJE DEL CLIENTE:\nHola, me gustaría proponerte este proyecto porque encaja perfectamente con tu perfil. Quedo a la espera de tu respuesta para discutir los detalles.\n[ID_PROYECTO: 15]', NULL, 0, '2026-05-16 12:45:51', 0, 0),
(68, 4, 3, '--- NUEVA PROPUESTA DE PROYECTO ---\nProyecto: prueba2\nDescripción: sfdsfgs\n-----------------------------------\nMENSAJE DEL CLIENTE:\nHola, me gustaría proponerte este proyecto porque encaja perfectamente con tu perfil. Quedo a la espera de tu respuesta para discutir los detalles.\n[ID_PROYECTO: 16]', NULL, 0, '2026-05-16 12:49:48', 0, 0),
(69, 4, 3, '--- NUEVA PROPUESTA DE PROYECTO ---\nProyecto: sdsaf\nDescripción: fsdfdsfs\n-----------------------------------\nMENSAJE DEL CLIENTE:\nHola, me gustaría proponerte este proyecto porque encaja perfectamente con tu perfil. Quedo a la espera de tu respuesta para discutir los detalles.\n[ID_PROYECTO: 17]', NULL, 0, '2026-05-16 14:55:53', 0, 0),
(70, 4, 3, '--- NUEVA PROPUESTA DE PROYECTO ---\nProyecto: prueba1 real!!!\nDescripción: prueba1 real!\n-----------------------------------\nMENSAJE DEL CLIENTE:\nHola, me gustaría proponerte este proyecto porque encaja perfectamente con tu perfil. Quedo a la espera de tu respuesta para discutir los detalles.\n[ID_PROYECTO: 18]', NULL, 0, '2026-05-16 16:46:05', 0, 0),
(71, 4, 3, '--- NUEVA PROPUESTA DE PROYECTO ---\nProyecto: prueba 2 real\nDescripción: Esta prueba es para llegar al minimo de retiro y el autonomo realmente pueda retirar algo y se compruebe que funciona todo correctamente\n-----------------------------------\nMENSAJE DEL CLIENTE:\nHola, me gustaría proponerte este proyecto porque encaja perfectamente con tu perfil. Quedo a la espera de tu respuesta para discutir los detalles.\n[ID_PROYECTO: 19]', NULL, 0, '2026-05-17 12:04:42', 0, 0),
(72, 4, 3, '--- NUEVA PROPUESTA DE PROYECTO ---\nProyecto: prueba de gmail\nDescripción: gmail prueba\n-----------------------------------\nMENSAJE DEL CLIENTE:\nHola, me gustaría proponerte este proyecto porque encaja perfectamente con tu perfil. Quedo a la espera de tu respuesta para discutir los detalles.\n[ID_PROYECTO: 20]', NULL, 0, '2026-05-23 13:22:43', 0, 0),
(73, 4, 3, '--- NUEVA PROPUESTA DE PROYECTO ---\nProyecto: prueba gmail\nDescripción: prueba gmail\r\n\n-----------------------------------\nMENSAJE DEL CLIENTE:\nHola, me gustaría proponerte este proyecto porque encaja perfectamente con tu perfil. Quedo a la espera de tu respuesta para discutir los detalles.\n[ID_PROYECTO: 21]', NULL, 0, '2026-05-23 17:38:12', 0, 0),
(74, 4, 3, '--- NUEVA PROPUESTA DE PROYECTO ---\nProyecto: prueba final del pago y retiro\nDescripción: prueba definitiva de pago y retiro\n-----------------------------------\nMENSAJE DEL CLIENTE:\nHola, me gustaría proponerte este proyecto porque encaja perfectamente con tu perfil. Quedo a la espera de tu respuesta para discutir los detalles.\n[ID_PROYECTO: 22]', NULL, 0, '2026-05-24 03:02:36', 0, 0),
(75, 4, 3, '--- NUEVA PROPUESTA DE PROYECTO ---\nProyecto: prueba ramdon pagos\nDescripción: prueba ramdon pagos\n-----------------------------------\nMENSAJE DEL CLIENTE:\nHola, me gustaría proponerte este proyecto porque encaja perfectamente con tu perfil. Quedo a la espera de tu respuesta para discutir los detalles.\n[ID_PROYECTO: 23]', NULL, 0, '2026-05-24 03:34:21', 0, 0),
(76, 4, 3, '--- NUEVA PROPUESTA DE PROYECTO ---\nProyecto: prueba borrable\nDescripción: prueba borrable\n-----------------------------------\nMENSAJE DEL CLIENTE:\nHola, me gustaría proponerte este proyecto porque encaja perfectamente con tu perfil. Quedo a la espera de tu respuesta para discutir los detalles.\n[ID_PROYECTO: 24]', NULL, 0, '2026-05-24 15:08:32', 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `id_trabajo` int(11) NOT NULL,
  `id_autonomo` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `comision_stripe` decimal(10,2) NOT NULL,
  `comision_wirvux` decimal(10,2) NOT NULL,
  `neto_autonomo` decimal(10,2) NOT NULL,
  `estado_pago` varchar(20) DEFAULT 'en_balance',
  `fecha_pago` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `id_trabajo`, `id_autonomo`, `monto_total`, `comision_stripe`, `comision_wirvux`, `neto_autonomo`, `estado_pago`, `fecha_pago`) VALUES
(8, 18, 3, 1.00, 0.27, 0.00, 0.80, 'en_balance', '2026-05-16 15:31:05'),
(9, 19, 3, 1.00, 0.27, 0.00, 0.80, 'en_balance', '2026-05-17 10:06:07'),
(10, 20, 3, 34.00, 0.76, 6.04, 27.20, 'en_balance', '2026-05-23 11:25:35'),
(11, 20, 3, 34.00, 0.76, 6.04, 27.20, 'en_balance', '2026-05-23 11:29:13'),
(12, 20, 3, 34.00, 0.76, 6.04, 27.20, 'en_balance', '2026-05-23 15:34:06'),
(13, 20, 3, 34.00, 0.76, 6.04, 27.20, 'en_balance', '2026-05-23 15:35:01'),
(14, 21, 3, 34.00, 0.76, 6.04, 27.20, 'en_balance', '2026-05-23 15:39:30'),
(15, 21, 3, 34.00, 0.76, 6.04, 27.20, 'en_balance', '2026-05-23 15:42:58'),
(16, 23, 3, 1.00, 0.27, 0.00, 0.74, 'en_balance', '2026-05-24 01:35:52'),
(17, 24, 3, 446.00, 6.94, 73.72, 287.94, 'en_balance', '2026-05-24 13:10:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propuestas`
--

CREATE TABLE `propuestas` (
  `id` int(11) NOT NULL,
  `id_trabajo` int(11) DEFAULT NULL,
  `id_autonomo` int(11) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `presupuesto_ofrecido` decimal(10,2) DEFAULT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_postulacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propuestas_proyecto`
--

CREATE TABLE `propuestas_proyecto` (
  `id` int(11) NOT NULL,
  `trabajo_id` int(11) NOT NULL,
  `autonomo_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `estado` enum('pendiente','aceptado','rechazado') DEFAULT 'pendiente',
  `fecha_propuesta` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resenas`
--

CREATE TABLE `resenas` (
  `id` int(11) NOT NULL,
  `id_autonomo` int(11) DEFAULT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `estrellas` int(11) DEFAULT NULL CHECK (`estrellas` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

CREATE TABLE `solicitudes` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trabajos`
--

CREATE TABLE `trabajos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `presupuesto` decimal(10,2) DEFAULT NULL,
  `divisa` varchar(3) DEFAULT 'EUR',
  `id_cliente` int(11) DEFAULT NULL,
  `id_autonomo` int(11) DEFAULT NULL,
  `estado` enum('abierto','en_progreso','completado') DEFAULT 'abierto',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `trabajos`
--

INSERT INTO `trabajos` (`id`, `titulo`, `descripcion`, `categoria`, `presupuesto`, `divisa`, `id_cliente`, `id_autonomo`, `estado`, `fecha_creacion`) VALUES
(18, 'prueba1 real!!!', 'prueba1 real!', 'Tecnología', 1.00, 'EUR', 4, 3, 'completado', '2026-05-16 14:44:00'),
(19, 'prueba 2 real', 'Esta prueba es para llegar al minimo de retiro y el autonomo realmente pueda retirar algo y se compruebe que funciona todo correctamente', 'Desarrollo Web', 1.00, 'EUR', 4, 3, 'completado', '2026-05-17 10:04:31'),
(22, 'prueba final del pago y retiro', 'prueba definitiva de pago y retiro', 'Desarrollo Web', 2.00, 'EUR', 4, 3, 'completado', '2026-05-24 01:02:29'),
(23, 'prueba ramdon pagos', 'prueba ramdon pagos', 'Desarrollo Web', 1.00, 'EUR', 4, 3, 'completado', '2026-05-24 01:34:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(30) DEFAULT NULL,
  `tipo` enum('cliente','autonomo') NOT NULL DEFAULT 'cliente',
  `foto` varchar(255) DEFAULT 'img/default-avatar.png',
  `apellidos` varchar(40) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `tipo_usuario` enum('cliente','autonomo') DEFAULT NULL,
  `categoria_principal` varchar(50) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `hitos` text DEFAULT NULL,
  `iban` varchar(34) DEFAULT NULL,
  `titular_cuenta` varchar(255) DEFAULT NULL,
  `saldo_disponible` decimal(10,2) DEFAULT 0.00,
  `stripe_connect_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `tipo`, `foto`, `apellidos`, `email`, `password`, `tipo_usuario`, `categoria_principal`, `especialidad`, `descripcion`, `hitos`, `iban`, `titular_cuenta`, `saldo_disponible`, `stripe_connect_id`) VALUES
(3, 'gabriel', 'cliente', 'img/perfiles/3_cesped.png', 'poveda', 'gpovher1507@gmail.com', '$2y$10$fmGH8Lap5FEYUx/L3xk/8OQq49Vnq0jKT7h8XHQvVF2g8DaGpKenq', 'autonomo', 'Tecnología', 'Desarrollo web', 'Hola', NULL, NULL, NULL, 2.46, 'acct_1TaaghHZwk1bwblU'),
(4, 'gabriel', 'cliente', 'img/default-avatar.png', 'poveda', 'povedagabriel666@gmail.com', '$2y$10$lpmG79Ok8L.A8DDTzAjyweyZ.6zVfJorJAUqQLuukRHEeR72/uDd2', 'cliente', '', '', NULL, NULL, NULL, NULL, 0.00, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_emisor` (`id_emisor`),
  ADD KEY `id_receptor` (`id_receptor`),
  ADD KEY `id_respuesta` (`id_respuesta`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `propuestas`
--
ALTER TABLE `propuestas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_trabajo` (`id_trabajo`),
  ADD KEY `id_autonomo` (`id_autonomo`);

--
-- Indices de la tabla `propuestas_proyecto`
--
ALTER TABLE `propuestas_proyecto`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_autonomo` (`id_autonomo`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `trabajos`
--
ALTER TABLE `trabajos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `idx_reporte` (`id_autonomo`,`estado`,`fecha_creacion`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `propuestas`
--
ALTER TABLE `propuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `propuestas_proyecto`
--
ALTER TABLE `propuestas_proyecto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `resenas`
--
ALTER TABLE `resenas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `trabajos`
--
ALTER TABLE `trabajos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD CONSTRAINT `mensajes_ibfk_1` FOREIGN KEY (`id_emisor`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `mensajes_ibfk_2` FOREIGN KEY (`id_receptor`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `mensajes_ibfk_3` FOREIGN KEY (`id_respuesta`) REFERENCES `mensajes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `propuestas`
--
ALTER TABLE `propuestas`
  ADD CONSTRAINT `propuestas_ibfk_1` FOREIGN KEY (`id_trabajo`) REFERENCES `trabajos` (`id`),
  ADD CONSTRAINT `propuestas_ibfk_2` FOREIGN KEY (`id_autonomo`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD CONSTRAINT `resenas_ibfk_1` FOREIGN KEY (`id_autonomo`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `resenas_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD CONSTRAINT `solicitudes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `trabajos`
--
ALTER TABLE `trabajos`
  ADD CONSTRAINT `trabajos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `trabajos_ibfk_2` FOREIGN KEY (`id_autonomo`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
