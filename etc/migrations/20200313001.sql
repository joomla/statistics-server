CREATE TABLE IF NOT EXISTS `#__jstats_counter_cms_php_version` (
  `cms_version` varchar(15) NOT NULL,
  `php_version` varchar(15) NOT NULL,
  `count` INT NOT NULL,
  PRIMARY KEY (`cms_version`,`php_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jstats_counter_db_type_version` (
  `db_type` varchar(15) NOT NULL,
  `db_version` varchar(15) NOT NULL,
  `count` INT NOT NULL,
  PRIMARY KEY (`db_type`,`db_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;
