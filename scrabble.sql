-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 17, 2010 at 01:44 PM
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `scrabble_games`
--

INSERT INTO `scrabble_games` (`id`, `status`, `active_player`) VALUES
(1, 'active', 1),
(2, 'active', 8);

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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `scrabble_placed_tiles`
--

INSERT INTO `scrabble_placed_tiles` (`id`, `game_id`, `letter`, `blankletter`, `x`, `y`) VALUES
(1, 2, 'E', '', 7, 7),
(2, 2, 'N', '', 8, 7),
(3, 2, 'O', '', 9, 7),
(4, 2, 'G', '', 7, 8),
(5, 2, 'A', '', 8, 8),
(6, 2, 'R', '', 9, 8),
(7, 2, 'T', '', 10, 7),
(8, 2, 'U', '', 10, 8);

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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `scrabble_players`
--

INSERT INTO `scrabble_players` (`id`, `user_id`, `next_player_id`, `game_id`, `rack_tiles`, `score`) VALUES
(1, 11, 2, 1, 'loeewhg', 0),
(2, 13, 3, 1, '_ieteer', 0),
(3, 17, 4, 1, 'nvdgetm', 0),
(4, 19, 1, 1, 'leivadi', 0),
(5, 5, 6, 2, 'ODUOESA', 0),
(6, 7, 7, 2, 'TEDZISI', 0),
(7, 11, 8, 2, 'ELDOIHO', 0),
(8, 13, 5, 2, 'ntwrenq', 0);

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
(1, 'leslie', '47729bc833f312ecc3ffbca52cd0e13444b0c0c9', 'Leslie'),
(2, 'susan', 'e35b3719aaf311f5f28d6b6ea77087f3ed860fa3', 'Susan');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
