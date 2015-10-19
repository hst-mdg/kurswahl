-- phpMyAdmin SQL Dump
-- version 4.2.6deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 19. Okt 2015 um 22:02
-- Server Version: 5.5.44-0ubuntu0.14.04.1
-- PHP-Version: 5.5.12-2ubuntu4

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `kurswahl`
--
CREATE DATABASE IF NOT EXISTS `kurswahl` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `kurswahl`;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kurse`
--

CREATE TABLE IF NOT EXISTS `kurse` (
`id` int(11) NOT NULL,
  `kuerzel` char(3) NOT NULL,
  `block` int(11) NOT NULL,
  `beschr_id` int(11) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Enthält zB. Kürzel. Jahrganszuordnungen in anderer Tabelle' ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kurs_beschreibungen`
--

CREATE TABLE IF NOT EXISTS `kurs_beschreibungen` (
`id` int(11) NOT NULL,
  `wahl_id` int(11) NOT NULL,
  `titel` varchar(100) NOT NULL,
  `beschreibung` varchar(1000) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kurs_jahrgang`
--

CREATE TABLE IF NOT EXISTS `kurs_jahrgang` (
  `kurs_id` int(11) NOT NULL,
  `jahrgang` varchar(3) NOT NULL DEFAULT '*' COMMENT '''*'': keine Einschränkung'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kurs_zusaetze`
--

CREATE TABLE IF NOT EXISTS `kurs_zusaetze` (
  `kurs_id` int(11) NOT NULL,
  `zusatz_wert_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `schueler`
--

CREATE TABLE IF NOT EXISTS `schueler` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `klasse` varchar(3) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `schueler_wahl`
--

CREATE TABLE IF NOT EXISTS `schueler_wahl` (
  `schueler_id` int(11) NOT NULL,
  `kurs_id` int(11) NOT NULL,
  `block` int(11) NOT NULL DEFAULT '1',
  `prioritaet` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Allgemeine Festlegungen zu einer Kurswahl' ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zusatz`
--

CREATE TABLE IF NOT EXISTS `zusatz` (
`id` int(11) NOT NULL,
  `wahl_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `mehrfach` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Definiert Zusatzfelder für eine Wahl (zB K/B; sprachl/natw Bereiche)' ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zusatz_werte`
--

CREATE TABLE IF NOT EXISTS `zusatz_werte` (
`id` int(11) NOT NULL,
  `zusatz_id` int(11) NOT NULL,
  `wert` varchar(100) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

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
-- Indexes for table `kurs_jahrgang`
--
ALTER TABLE `kurs_jahrgang`
 ADD PRIMARY KEY (`kurs_id`,`jahrgang`);

--
-- Indexes for table `kurs_zusaetze`
--
ALTER TABLE `kurs_zusaetze`
 ADD PRIMARY KEY (`kurs_id`,`zusatz_wert_id`), ADD UNIQUE KEY `kurs_id` (`kurs_id`,`zusatz_wert_id`), ADD UNIQUE KEY `kurs_id_2` (`kurs_id`,`zusatz_wert_id`), ADD KEY `zusatz_wert_id` (`zusatz_wert_id`);

--
-- Indexes for table `schueler`
--
ALTER TABLE `schueler`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`,`name`), ADD UNIQUE KEY `id_2` (`id`), ADD UNIQUE KEY `name` (`name`), ADD UNIQUE KEY `name_2` (`name`), ADD UNIQUE KEY `id_3` (`id`);

--
-- Indexes for table `schueler_wahl`
--
ALTER TABLE `schueler_wahl`
 ADD PRIMARY KEY (`schueler_id`,`kurs_id`,`block`,`prioritaet`), ADD KEY `kurs_id` (`kurs_id`);

--
-- Indexes for table `wahl_einstellungen`
--
ALTER TABLE `wahl_einstellungen`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `zusatz`
--
ALTER TABLE `zusatz`
 ADD PRIMARY KEY (`id`,`wahl_id`), ADD UNIQUE KEY `name` (`name`), ADD KEY `wahl_id` (`wahl_id`);

--
-- Indexes for table `zusatz_werte`
--
ALTER TABLE `zusatz_werte`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `wert` (`wert`), ADD UNIQUE KEY `zusatz_id` (`zusatz_id`,`wert`), ADD UNIQUE KEY `zusatz_id_2` (`zusatz_id`,`wert`);

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
-- AUTO_INCREMENT for table `schueler`
--
ALTER TABLE `schueler`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `wahl_einstellungen`
--
ALTER TABLE `wahl_einstellungen`
MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `zusatz`
--
ALTER TABLE `zusatz`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `zusatz_werte`
--
ALTER TABLE `zusatz_werte`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
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

--
-- Constraints der Tabelle `kurs_jahrgang`
--
ALTER TABLE `kurs_jahrgang`
ADD CONSTRAINT `kurs_jahrgang_ibfk_1` FOREIGN KEY (`kurs_id`) REFERENCES `kurse` (`id`) ON UPDATE CASCADE;

--
-- Constraints der Tabelle `kurs_zusaetze`
--
ALTER TABLE `kurs_zusaetze`
ADD CONSTRAINT `kurs_zusaetze_ibfk_3` FOREIGN KEY (`kurs_id`) REFERENCES `kurs_beschreibungen` (`id`) ON UPDATE CASCADE,
ADD CONSTRAINT `kurs_zusaetze_ibfk_2` FOREIGN KEY (`zusatz_wert_id`) REFERENCES `zusatz_werte` (`id`) ON UPDATE CASCADE;

--
-- Constraints der Tabelle `schueler_wahl`
--
ALTER TABLE `schueler_wahl`
ADD CONSTRAINT `schueler_wahl_ibfk_3` FOREIGN KEY (`schueler_id`) REFERENCES `schueler` (`id`) ON UPDATE CASCADE,
ADD CONSTRAINT `schueler_wahl_ibfk_4` FOREIGN KEY (`kurs_id`) REFERENCES `kurs_beschreibungen` (`id`) ON UPDATE CASCADE;

--
-- Constraints der Tabelle `zusatz`
--
ALTER TABLE `zusatz`
ADD CONSTRAINT `zusatz_ibfk_1` FOREIGN KEY (`wahl_id`) REFERENCES `wahl_einstellungen` (`id`) ON UPDATE CASCADE;

--
-- Constraints der Tabelle `zusatz_werte`
--
ALTER TABLE `zusatz_werte`
ADD CONSTRAINT `zusatz_werte_ibfk_1` FOREIGN KEY (`zusatz_id`) REFERENCES `zusatz` (`id`) ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
