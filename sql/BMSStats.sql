-- phpMyAdmin SQL Dump
-- version 4.5.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 09. Mai 2016 um 13:46
-- Server-Version: 10.1.10-MariaDB
-- PHP-Version: 5.6.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `BMSStats`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bms_aircrafts`
--

CREATE TABLE `bms_aircrafts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `flights` int(11) NOT NULL DEFAULT '0',
  `flighttime` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bms_flights`
--

CREATE TABLE `bms_flights` (
  `id` int(11) NOT NULL,
  `recordtime` int(11) NOT NULL,
  `pilotid` int(11) NOT NULL,
  `takeofftime` int(11) NOT NULL,
  `landingtime` int(11) NOT NULL,
  `aircraftid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bms_parser_log`
--

CREATE TABLE `bms_parser_log` (
  `id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `durationms` int(11) NOT NULL,
  `events` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bms_pilots`
--

CREATE TABLE `bms_pilots` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `disp_name` varchar(255) NOT NULL,
  `flights` int(11) NOT NULL DEFAULT '0',
  `flighttime` int(11) NOT NULL DEFAULT '0',
  `lastactive` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bms_pilot_aircrafts`
--

CREATE TABLE `bms_pilot_aircrafts` (
  `id` int(11) NOT NULL,
  `pilotid` int(11) NOT NULL,
  `aircraftid` int(11) NOT NULL,
  `flights` int(11) NOT NULL,
  `flighttime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `bms_aircrafts`
--
ALTER TABLE `bms_aircrafts`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `bms_flights`
--
ALTER TABLE `bms_flights`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `bms_parser_log`
--
ALTER TABLE `bms_parser_log`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `bms_pilots`
--
ALTER TABLE `bms_pilots`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `bms_pilot_aircrafts`
--
ALTER TABLE `bms_pilot_aircrafts`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `bms_aircrafts`
--
ALTER TABLE `bms_aircrafts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT für Tabelle `bms_flights`
--
ALTER TABLE `bms_flights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
--
-- AUTO_INCREMENT für Tabelle `bms_parser_log`
--
ALTER TABLE `bms_parser_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `bms_pilots`
--
ALTER TABLE `bms_pilots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT für Tabelle `bms_pilot_aircrafts`
--
ALTER TABLE `bms_pilot_aircrafts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
