CREATE TABLE IF NOT EXISTS `jos_jstats` (
  `unique_id` varchar(40) NOT NULL,
  `php_version` varchar(15) NOT NULL,
  `db_type` varchar(15) NOT NULL,
  `db_version` varchar(50) NOT NULL,
  `cms_version` varchar(15) NOT NULL,
  `server_os` varchar(255) NOT NULL,
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`unique_id`),
  KEY `idx_php_version` (`php_version`),
  KEY `idx_db_type` (`db_type`),
  KEY `idx_db_version` (`db_version`),
  KEY `idx_cms_version` (`cms_version`),
  KEY `idx_server_os` (`server_os`),
  KEY `idx_database` (`db_type`, `db_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `jos_jstats_counter_phpversion` (
  `php_version` varchar(40) NOT NULL,
  `count` INT NOT NULL,
  PRIMARY KEY (`php_version`),
  KEY `idx_version_count` (`php_version`, `count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;

DELIMITER $$
CREATE
    TRIGGER `phpversion_insert` AFTER INSERT
    ON `jos_jstats`
    FOR EACH ROW BEGIN
        IF NEW.php_version not in (
            SELECT counter.php_version
            FROM jos_jstats_counter_phpversion AS counter
            WHERE (NEW.php_version = counter.php_version)
        ) THEN
            INSERT INTO `jos_jstats_counter_phpversion` (php_version,count) VALUES(NEW.php_version,1);
          ELSE
            UPDATE `jos_jstats_counter_phpversion` SET count=count+1 WHERE `php_version` = NEW.php_version;
        END IF;
    END$$

CREATE
  TRIGGER `phpversion_update` AFTER UPDATE
  ON `jos_jstats`
  FOR EACH ROW BEGIN
    IF OLD.php_version <> NEW.php_version THEN
      UPDATE `jos_jstats_counter_phpversion` SET count=count-1 WHERE `php_version` = OLD.php_version;
      IF NEW.php_version not in (
        SELECT counter.php_version
        FROM jos_jstats_counter_phpversion AS counter
        WHERE (NEW.php_version = counter.php_version)
      ) THEN
        INSERT INTO `jos_jstats_counter_phpversion` (php_version,count) VALUES(NEW.php_version,1);
      ELSE
        UPDATE `jos_jstats_counter_phpversion` SET count=count+1 WHERE `php_version` = NEW.php_version;
      END IF;
    END IF;
  END$$

DELIMITER ;



SELECT c.count, stats.db_type
FROM `jos_jstats` AS stats
  LEFT JOIN `jos_jstats_counter_phpversion` AS c ON c.php_version = stats.php_version
WHERE stats.db_type = 'mysql'
GROUP BY stats.php_version