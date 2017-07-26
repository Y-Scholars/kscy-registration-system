/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 07. 27
 */

CREATE TABLE IF NOT EXISTS `kscy_papers` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(1000) NOT NULL,
  `file` varchar(500) NOT NULL,
  `research_field` varchar(100) NOT NULL,
  `desired_session` varchar(100) NOT NULL,
  `team_leader` varchar(100) NOT NULL,
  `team_members` varchar(100) NOT NULL,
  `approved` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`no`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;
