/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 07. 27
 */

CREATE TABLE IF NOT EXISTS `kscy_students` (
  `no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `school` varchar(50) NOT NULL,
  `grade` int(11) NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `email` varchar(300) NOT NULL,
  `password` varchar(300) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `guardian_name` varchar(50) NOT NULL,
  `guardian_phone_number` varchar(50) NOT NULL,
  `survey` varchar(50) NOT NULL,
  `auto_switch` int(4) NOT NULL,
  `applications` varchar(500) NOT NULL,
  `deposit_status` int(11) NOT NULL,
  `level` int(11) NOT NULL DEFAULT '0',
  `tag` varchar(10) NOT NULL,
  `memo` varchar(500) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`no`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;
