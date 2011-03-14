-- phpMyAdmin SQL Dump
-- version 3.3.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 14, 2011 at 03:19 PM
-- Server version: 5.0.27
-- PHP Version: 5.2.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `scrabble`
--

-- --------------------------------------------------------

--
-- Table structure for table `scrabble_games`
--

CREATE TABLE IF NOT EXISTS `scrabble_games` (
  `id` int(11) NOT NULL auto_increment,
  `status` varchar(50) NOT NULL default 'active' COMMENT '''active'', ''completed''',
  `active_player` int(11) NOT NULL COMMENT 'ID of active player',
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `scrabble_moves`
--

CREATE TABLE IF NOT EXISTS `scrabble_moves` (
  `id` int(11) NOT NULL auto_increment,
  `game_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `time` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `notation` varchar(50) NOT NULL,
  `score` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `scrabble_placed_tiles`
--

CREATE TABLE IF NOT EXISTS `scrabble_placed_tiles` (
  `id` int(11) NOT NULL auto_increment,
  `game_id` int(11) NOT NULL,
  `letter` varchar(1) NOT NULL COMMENT '"_" for blank',
  `blankletter` varchar(1) NOT NULL default '',
  `x` int(2) NOT NULL COMMENT '(0..14)',
  `y` int(2) NOT NULL COMMENT '(0..14)',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `scrabble_players`
--

CREATE TABLE IF NOT EXISTS `scrabble_players` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `next_player_id` int(11) NOT NULL default '-1',
  `game_id` int(11) NOT NULL,
  `rack_tiles` varchar(7) NOT NULL default '',
  `score` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `scrabble_users`
--

CREATE TABLE IF NOT EXISTS `scrabble_users` (
  `id` int(11) NOT NULL auto_increment COMMENT '(cakephp required)',
  `username` varchar(50) NOT NULL,
  `password` varchar(40) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

