-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 16, 2010 at 10:24 PM
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
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `scrabble_games`
--


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
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `scrabble_placed_tiles`
--


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
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `scrabble_players`
--


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
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `scrabble_users`
--


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
