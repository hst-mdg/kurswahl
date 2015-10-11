-- phpMyAdmin SQL Dump
-- version 4.2.6deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 11. Okt 2015 um 19:12
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

--
-- Daten für Tabelle `kurse`
--

INSERT INTO `kurse` (`id`, `kuerzel`, `block`, `beschr_id`) VALUES
(1, '01a', 1, 1),
(2, '01b', 1, 1),
(3, '02', 1, 2),
(4, '03', 1, 3),
(5, '04', 1, 4),
(6, '05', 1, 5);

--
-- Daten für Tabelle `kurs_beschreibungen`
--

INSERT INTO `kurs_beschreibungen` (`id`, `wahl_id`, `titel`, `beschreibung`) VALUES
(1, 1, 'Radtour', '1 Woche Radfahren'),
(2, 1, 'Spieleprogrammierung', 'Computerspiele werden mit Greenfoot erstellt.'),
(3, 1, 'Stricken', 'Stricken und Häkeln.'),
(4, 1, 'Backen', 'Kuchen backen, essen und verkaufen...'),
(5, 1, 'Schulgarten', '');

--
-- Daten für Tabelle `wahl_einstellungen`
--

INSERT INTO `wahl_einstellungen` (`id`, `name`, `bloecke`, `startdatum`, `enddatum`) VALUES
(1, 'Projektwoche 2016', 1, '2015-10-01 00:00:00', '2016-06-30 00:00:00');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
