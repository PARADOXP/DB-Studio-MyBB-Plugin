CREATE TABLE IF NOT EXISTS `{PREFIX}dbstudio_sessions` (
  `sid` varchar(64) NOT NULL,
  `uid` int unsigned NOT NULL DEFAULT 0,
  `ip` varchar(45) NOT NULL DEFAULT '',
  `autologin` tinyint(1) NOT NULL DEFAULT 0,
  `created` bigint NOT NULL DEFAULT 0,
  `lastactive` bigint NOT NULL DEFAULT 0,
  PRIMARY KEY (`sid`),
  KEY `uid` (`uid`),
  KEY `lastactive` (`lastactive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{PREFIX}dbstudio_log` (
  `lid` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` int unsigned NOT NULL DEFAULT 0,
  `action` varchar(32) NOT NULL DEFAULT '',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `dateline` bigint NOT NULL DEFAULT 0,
  PRIMARY KEY (`lid`),
  KEY `uid` (`uid`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
