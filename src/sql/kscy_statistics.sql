/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 07. 27
 */

CREATE TABLE IF NOT EXISTS `kscy_statistics` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` varchar(1000) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`no`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

INSERT INTO `kscy_statistics` (`no`, `key`, `value`, `timestamp`) VALUES
(1, 'total_students', '0', '2017-07-14 22:22:44'),
(2, 'total_papers', '0', '2017-07-14 22:46:40'),
(3, 'total_plans', '0', '2017-07-14 15:21:01'),
(4, 'total_mentorings', '0', '2017-07-14 16:58:42'),
(5, 'total_camps', '0', '2017-07-13 18:11:24');
