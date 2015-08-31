CREATE TABLE IF NOT EXISTS `%s` (
  `identifier` varchar(255) NOT NULL DEFAULT '',
  `frequency` varchar(128) NOT NULL DEFAULT '',
  `status` int(11) NOT NULL DEFAULT '0',
  `queue` tinyint(4) NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `state` text,
  `modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `completed_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  UNIQUE KEY `name` (`identifier`)
) DEFAULT CHARSET=utf8;