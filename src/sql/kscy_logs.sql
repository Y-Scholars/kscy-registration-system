/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 07. 27
 */

CREATE TABLE IF NOT EXISTS `kscy_logs` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `target_user` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `data` varchar(500) NOT NULL,
  `ip` varchar(16) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`no`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;
