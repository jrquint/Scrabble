-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 22, 2010 at 03:14 PM
-- Server version: 5.1.41
-- PHP Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `scrabble`
--

-- --------------------------------------------------------

--
-- Table structure for table `scrabble_games`
--

CREATE TABLE IF NOT EXISTS `scrabble_games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(50) NOT NULL DEFAULT 'active' COMMENT '''active'', ''completed''',
  `active_player` int(11) NOT NULL COMMENT 'ID of active player',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `scrabble_games`
--

INSERT INTO `scrabble_games` (`id`, `status`, `active_player`, `created`) VALUES
(1, 'active', 2, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `scrabble_moves`
--

CREATE TABLE IF NOT EXISTS `scrabble_moves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notation` varchar(50) NOT NULL,
  `score` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `scrabble_moves`
--


-- --------------------------------------------------------

--
-- Table structure for table `scrabble_placed_tiles`
--

CREATE TABLE IF NOT EXISTS `scrabble_placed_tiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `letter` varchar(1) NOT NULL COMMENT '"_" for blank',
  `blankletter` varchar(1) NOT NULL DEFAULT '',
  `x` int(2) NOT NULL COMMENT '(0..14)',
  `y` int(2) NOT NULL COMMENT '(0..14)',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `scrabble_placed_tiles`
--

INSERT INTO `scrabble_placed_tiles` (`id`, `game_id`, `letter`, `blankletter`, `x`, `y`) VALUES
(1, 1, 'I', '', 7, 7),
(2, 1, 'M', '', 7, 8),
(3, 1, 'M', '', 7, 9),
(4, 1, 'O', '', 7, 10);

-- --------------------------------------------------------

--
-- Table structure for table `scrabble_players`
--

CREATE TABLE IF NOT EXISTS `scrabble_players` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `next_player_id` int(11) NOT NULL DEFAULT '-1',
  `game_id` int(11) NOT NULL,
  `rack_tiles` varchar(7) NOT NULL DEFAULT '',
  `score` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `scrabble_players`
--

INSERT INTO `scrabble_players` (`id`, `user_id`, `next_player_id`, `game_id`, `rack_tiles`, `score`) VALUES
(1, 1, 2, 1, 'BEILQRX', 0),
(2, 2, 1, 1, 'ZRINNGN', 0);

-- --------------------------------------------------------

--
-- Table structure for table `scrabble_users`
--

CREATE TABLE IF NOT EXISTS `scrabble_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '(cakephp required)',
  `username` varchar(50) NOT NULL,
  `password` varchar(40) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `scrabble_users`
--

INSERT INTO `scrabble_users` (`id`, `username`, `password`, `nickname`) VALUES
(1, 'leslie', '8da23f246cefdb47494326aa60982c25b6b772d4', 'Leslie'),
(2, 'susan', 'db4be265f73c8f21e9230589a9e27cb6dd3657a9', 'Susan');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
