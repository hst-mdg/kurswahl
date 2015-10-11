-- phpMyAdmin SQL Dump
-- version 4.2.6deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 11. Okt 2015 um 18:29
-- Server Version: 5.5.44-0ubuntu0.14.04.1
-- PHP-Version: 5.5.12-2ubuntu4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `kurswahl`
--

CREATE DATABASE IF NOT EXISTS kurswahl;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kurse`
--

CREATE TABLE IF NOT EXISTS `kurse` (
`id` int(11) NOT NULL,
  `kuerzel` char(3) NOT NULL,
  `block` int(11) NOT NULL,
  `beschr_id` int(11) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Enthält zB. Kürzel. Jahrganszuordnungen in anderer Tabelle';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kurs_beschreibungen`
--

CREATE TABLE IF NOT EXISTS `kurs_beschreibungen` (
`id` int(11) NOT NULL,
  `wahl_id` int(11) NOT NULL,
  `titel` varchar(100) NOT NULL,
  `beschreibung` varchar(1000) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `wahl_einstellungen`
--

CREATE TABLE IF NOT EXISTS `wahl_einstellungen` (
`id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'z.B. "Projektwoche 20xx"',
  `bloecke` int(11) NOT NULL DEFAULT '1' COMMENT 'z.B. 4 (wenn 4 Quartale)',
  `startdatum` datetime NOT NULL COMMENT 'Zeitraum für Wahlen',
  `enddatum` datetime NOT NULL COMMENT 'Zeitraum für Wahlen'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Allgemeine Festlegungen zu einer Kurswahl';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kurse`
--
ALTER TABLE `kurse`
 ADD PRIMARY KEY (`id`,`kuerzel`,`beschr_id`), ADD UNIQUE KEY `wahl_id` (`kuerzel`), ADD KEY `beschr_id` (`beschr_id`);

--
-- Indexes for table `kurs_beschreibungen`
--
ALTER TABLE `kurs_beschreibungen`
 ADD PRIMARY KEY (`id`,`wahl_id`), ADD KEY `wahl_id` (`wahl_id`);

--
-- Indexes for table `wahl_einstellungen`
--
ALTER TABLE `wahl_einstellungen`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kurse`
--
ALTER TABLE `kurse`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `kurs_beschreibungen`
--
ALTER TABLE `kurs_beschreibungen`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `wahl_einstellungen`
--
ALTER TABLE `wahl_einstellungen`
MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;
--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `kurse`
--
ALTER TABLE `kurse`
ADD CONSTRAINT `kurse_ibfk_1` FOREIGN KEY (`beschr_id`) REFERENCES `kurs_beschreibungen` (`id`) ON UPDATE CASCADE;

--
-- Constraints der Tabelle `kurs_beschreibungen`
--
ALTER TABLE `kurs_beschreibungen`
ADD CONSTRAINT `kurs_beschreibungen_ibfk_1` FOREIGN KEY (`wahl_id`) REFERENCES `wahl_einstellungen` (`id`) ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;