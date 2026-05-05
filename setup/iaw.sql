-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db:3306
-- Generation Time: May 05, 2026 at 06:58 AM
-- Server version: 12.2.2-MariaDB-ubu2404
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `iaw`
--

-- --------------------------------------------------------

--
-- Table structure for table `Alumnes`
--

CREATE TABLE `Alumnes` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `cognom1` varchar(50) DEFAULT NULL,
  `cognom2` varchar(50) DEFAULT NULL,
  `correu` varchar(50) DEFAULT NULL,
  `grupClasse` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Assignacions`
--

CREATE TABLE `Assignacions` (
  `id` int(11) NOT NULL,
  `idMaterial` int(11) DEFAULT NULL,
  `idAlumne` int(11) DEFAULT NULL,
  `dataInici` date DEFAULT NULL,
  `dataFinal` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Estats`
--

CREATE TABLE `Estats` (
  `id` int(11) NOT NULL,
  `estat` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Incidencies`
--

CREATE TABLE `Incidencies` (
  `id` int(11) NOT NULL,
  `informacio` varchar(5000) DEFAULT NULL,
  `dataOberta` date DEFAULT NULL,
  `dataTancada` date DEFAULT NULL,
  `idAlumne` int(11) DEFAULT NULL,
  `idDispositiu` int(11) DEFAULT NULL,
  `idEstat` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Material`
--

CREATE TABLE `Material` (
  `id` int(11) NOT NULL,
  `idTipus` int(11) DEFAULT NULL,
  `idInventari` varchar(10) DEFAULT NULL,
  `etiquetaDepInf` varchar(50) DEFAULT NULL,
  `numSerie` varchar(50) DEFAULT NULL,
  `macEthernet` varchar(50) DEFAULT NULL,
  `macWifi` varchar(50) DEFAULT NULL,
  `SACE` varchar(50) DEFAULT NULL,
  `dataAdquisicio` date DEFAULT NULL,
  `idUbicacio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TipusMaterial`
--

CREATE TABLE `TipusMaterial` (
  `id` int(11) NOT NULL,
  `tipus` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `origen` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Ubicacions`
--

CREATE TABLE `Ubicacions` (
  `id` int(11) NOT NULL,
  `nom` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Usuaris`
--

CREATE TABLE `Usuaris` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `cognom1` varchar(50) NOT NULL,
  `cognom2` varchar(50) DEFAULT '',
  `correu` varchar(100) NOT NULL,
  `contrasenya_hash` varchar(255) NOT NULL,
  `rol` enum('professor','alumne') NOT NULL DEFAULT 'alumne',
  `grupClasse` varchar(10) DEFAULT NULL,
  `actiu` tinyint(1) NOT NULL DEFAULT 1,
  `creatEl` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `Usuaris`
--

INSERT INTO `Usuaris` (`id`, `nom`, `cognom1`, `cognom2`, `correu`, `contrasenya_hash`, `rol`, `grupClasse`, `actiu`, `creatEl`) VALUES
(1, 'Professor', 'Proves', '', 'professor@iesmontsia.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'professor', NULL, 1, '2026-05-05 02:13:16'),
(2, 'Alumne', 'Proves', '', 'alumne@iesmontsia.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumne', 'ASIX1', 1, '2026-05-05 02:13:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Alumnes`
--
ALTER TABLE `Alumnes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `Assignacions`
--
ALTER TABLE `Assignacions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idMaterial` (`idMaterial`),
  ADD KEY `idAlumne` (`idAlumne`);

--
-- Indexes for table `Estats`
--
ALTER TABLE `Estats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `Incidencies`
--
ALTER TABLE `Incidencies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idAlumne` (`idAlumne`),
  ADD KEY `idDispositiu` (`idDispositiu`),
  ADD KEY `idEstat` (`idEstat`);

--
-- Indexes for table `Material`
--
ALTER TABLE `Material`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idTipus` (`idTipus`),
  ADD KEY `idUbicacio` (`idUbicacio`);

--
-- Indexes for table `TipusMaterial`
--
ALTER TABLE `TipusMaterial`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `Ubicacions`
--
ALTER TABLE `Ubicacions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `Usuaris`
--
ALTER TABLE `Usuaris`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correu` (`correu`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Usuaris`
--
ALTER TABLE `Usuaris`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Assignacions`
--
ALTER TABLE `Assignacions`
  ADD CONSTRAINT `1` FOREIGN KEY (`idMaterial`) REFERENCES `Material` (`id`),
  ADD CONSTRAINT `2` FOREIGN KEY (`idAlumne`) REFERENCES `Alumnes` (`id`);

--
-- Constraints for table `Incidencies`
--
ALTER TABLE `Incidencies`
  ADD CONSTRAINT `1` FOREIGN KEY (`idAlumne`) REFERENCES `Alumnes` (`id`),
  ADD CONSTRAINT `2` FOREIGN KEY (`idDispositiu`) REFERENCES `Material` (`id`),
  ADD CONSTRAINT `3` FOREIGN KEY (`idEstat`) REFERENCES `Estats` (`id`);

--
-- Constraints for table `Material`
--
ALTER TABLE `Material`
  ADD CONSTRAINT `1` FOREIGN KEY (`idTipus`) REFERENCES `TipusMaterial` (`id`),
  ADD CONSTRAINT `2` FOREIGN KEY (`idUbicacio`) REFERENCES `Ubicacions` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
