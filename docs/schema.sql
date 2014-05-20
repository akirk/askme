SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `question` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `answer` text,
  `asking_user` int(11) unsigned DEFAULT NULL,
  `asked_user` int(11) unsigned NOT NULL,
  `hide` enum('0','1') NOT NULL,
  `private_answer` enum('0','1') NOT NULL,
  `created` datetime NOT NULL,
  `answered` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asking_user` (`asking_user`),
  KEY `asked_user` (`asked_user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `last_access` datetime DEFAULT NULL,
  `last_emailed` datetime DEFAULT NULL,
  `last_emailer` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
