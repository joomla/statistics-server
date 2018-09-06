CREATE TABLE IF NOT EXISTS `#__jstats` (
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

CREATE TABLE `#__migrations` (
  `version` varchar(25) NOT NULL COMMENT 'Applied migration versions',
  KEY `version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;

INSERT INTO `#__migrations` (`version`) VALUES
('20160618001'),
('20180413001');

CREATE TABLE IF NOT EXISTS `#__jstats_counter_php_version` (
  `php_version` varchar(15) NOT NULL,
  `count` INT NOT NULL,
  PRIMARY KEY (`php_version`),
  KEY `idx_version_count` (`php_version`, `count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jstats_counter_db_version` (
  `db_version` varchar(50) NOT NULL,
  `count` INT NOT NULL,
  PRIMARY KEY (`db_version`),
  KEY `idx_version_count` (`db_version`, `count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jstats_counter_db_type` (
  `db_type` varchar(15) NOT NULL,
  `count` INT NOT NULL,
  PRIMARY KEY (`db_type`),
  KEY `idx_version_count` (`db_type`, `count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jstats_counter_cms_version` (
  `cms_version` varchar(15) NOT NULL,
  `count` INT NOT NULL,
  PRIMARY KEY (`cms_version`),
  KEY `idx_version_count` (`cms_version`, `count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jstats_counter_server_os` (
  `server_os` varchar(255) NOT NULL,
  `count` INT NOT NULL,
  PRIMARY KEY (`server_os`),
  KEY `idx_version_count` (`server_os`, `count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jstats_counter_cms_php_version` (
  `cms_version` varchar(15) NOT NULL,
  `php_version` varchar(15) NOT NULL,
  `count` INT NOT NULL,
  PRIMARY KEY (`cms_version`,`php_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;

DELIMITER $$

CREATE
TRIGGER `stat_insert`
  AFTER INSERT
  ON `#__jstats`
  FOR EACH ROW
  BEGIN
    IF NEW.php_version NOT IN (
      SELECT counter.php_version
      FROM `#__jstats_counter_php_version` AS counter
      WHERE (NEW.php_version = counter.php_version)
    )
    THEN
      INSERT INTO `#__jstats_counter_php_version` (php_version, count) VALUES (NEW.php_version, 1);
    ELSE
      UPDATE `#__jstats_counter_php_version`
      SET count = count + 1
      WHERE `php_version` = NEW.php_version;
    END IF;

    IF NEW.db_version NOT IN (
      SELECT counter.db_version
      FROM `#__jstats_counter_db_version` AS counter
        WHERE (NEW.db_version = counter.db_version)
    )
    THEN
      INSERT INTO `#__jstats_counter_db_version` (db_version, count) VALUES (NEW.db_version, 1);
    ELSE
      UPDATE `#__jstats_counter_db_version`
      SET count = count + 1
      WHERE `db_version` = NEW.db_version;
    END IF;

    IF NEW.db_type NOT IN (
      SELECT counter.db_type
      FROM `#__jstats_counter_db_type` AS counter
        WHERE (NEW.db_type = counter.db_type)
    )
    THEN
      INSERT INTO `#__jstats_counter_db_type` (db_type, count) VALUES (NEW.db_type, 1);
    ELSE
      UPDATE `#__jstats_counter_db_type`
      SET count = count + 1
      WHERE `db_type` = NEW.db_type;
    END IF;

    IF NEW.cms_version NOT IN (
      SELECT counter.cms_version
      FROM `#__jstats_counter_cms_version` AS counter
        WHERE (NEW.cms_version = counter.cms_version)
    )
    THEN
      INSERT INTO `#__jstats_counter_cms_version` (cms_version, count) VALUES (NEW.cms_version, 1);
    ELSE
      UPDATE `#__jstats_counter_cms_version`
      SET count = count + 1
      WHERE `cms_version` = NEW.cms_version;
    END IF;

    IF NEW.server_os NOT IN (
      SELECT counter.server_os
      FROM `#__jstats_counter_server_os` AS counter
        WHERE (NEW.server_os = counter.server_os)
    )
    THEN
      INSERT INTO `#__jstats_counter_server_os` (server_os, count) VALUES (NEW.server_os, 1);
    ELSE
      UPDATE `#__jstats_counter_server_os`
      SET count = count + 1
      WHERE `server_os` = NEW.server_os;
    END IF;

	IF NOT EXISTS (SELECT 1
      FROM `#__jstats_counter_cms_php_version` AS counter
      WHERE NEW.php_version = counter.php_version
        and NEW.cms_version = counter.cms_version
    )
    THEN
      INSERT INTO `#__jstats_counter_cms_php_version` (cms_version, php_version, count) VALUES (NEW.cms_version, NEW.php_version, 1);
    ELSE
      UPDATE `#__jstats_counter_cms_php_version`
      SET count = count + 1
      WHERE `php_version` = NEW.php_version
	  AND cms_version = NEW.cms_version
    END IF;
  END$$

CREATE
TRIGGER `stat_update`
  AFTER UPDATE
  ON `#__jstats`
  FOR EACH ROW
  BEGIN
    IF OLD.php_version <> NEW.php_version
    THEN
      UPDATE `#__jstats_counter_php_version`
      SET count = count - 1
      WHERE `php_version` = OLD.php_version;
      IF NEW.php_version NOT IN (
        SELECT counter.php_version
        FROM `#__jstats_counter_php_version` AS counter
        WHERE (NEW.php_version = counter.php_version)
      )
      THEN
        INSERT INTO `#__jstats_counter_php_version` (php_version, count) VALUES (NEW.php_version, 1);
      ELSE
        UPDATE `#__jstats_counter_php_version`
        SET count = count + 1
        WHERE `php_version` = NEW.php_version;
      END IF;
    END IF;

    IF OLD.db_version <> NEW.db_version
    THEN
      UPDATE `#__jstats_counter_db_version`
      SET count = count - 1
      WHERE `db_version` = OLD.db_version;
      IF NEW.db_version NOT IN (
        SELECT counter.db_version
        FROM `#__jstats_counter_db_version` AS counter
        WHERE (NEW.db_version = counter.db_version)
      )
      THEN
        INSERT INTO `#__jstats_counter_db_version` (db_version, count) VALUES (NEW.db_version, 1);
      ELSE
        UPDATE `#__jstats_counter_db_version`
        SET count = count + 1
        WHERE `db_version` = NEW.db_version;
      END IF;
    END IF;

    IF OLD.db_type <> NEW.db_type
    THEN
      UPDATE `#__jstats_counter_db_type`
      SET count = count - 1
      WHERE `db_type` = OLD.db_type;
      IF NEW.db_type NOT IN (
        SELECT counter.db_type
        FROM `#__jstats_counter_db_type` AS counter
        WHERE (NEW.db_type = counter.db_type)
      )
      THEN
        INSERT INTO `#__jstats_counter_db_type` (db_type, count) VALUES (NEW.db_type, 1);
      ELSE
        UPDATE `#__jstats_counter_db_type`
        SET count = count + 1
        WHERE `db_type` = NEW.db_type;
      END IF;
    END IF;

    IF OLD.cms_version <> NEW.cms_version
    THEN
      UPDATE `#__jstats_counter_cms_version`
      SET count = count - 1
      WHERE `cms_version` = OLD.cms_version;
      IF NEW.cms_version NOT IN (
        SELECT counter.cms_version
        FROM `#__jstats_counter_cms_version` AS counter
        WHERE (NEW.cms_version = counter.cms_version)
      )
      THEN
        INSERT INTO `#__jstats_counter_cms_version` (cms_version, count) VALUES (NEW.cms_version, 1);
      ELSE
        UPDATE `#__jstats_counter_cms_version`
        SET count = count + 1
        WHERE `cms_version` = NEW.cms_version;
      END IF;
    END IF;

    IF OLD.server_os <> NEW.server_os
    THEN
      UPDATE `#__jstats_counter_server_os`
      SET count = count - 1
      WHERE `server_os` = OLD.server_os;
      IF NEW.server_os NOT IN (
        SELECT counter.server_os
        FROM `#__jstats_counter_server_os` AS counter
        WHERE (NEW.server_os = counter.server_os)
      )
      THEN
        INSERT INTO `#__jstats_counter_server_os` (server_os, count) VALUES (NEW.server_os, 1);
      ELSE
        UPDATE `#__jstats_counter_server_os`
        SET count = count + 1
        WHERE `server_os` = NEW.server_os;
      END IF;
    END IF;
  
  IF (OLD.php_version <> NEW.php_version AND OLD.cms_version <> NEW.cms_version) OR
   (OLD.php_version = NEW.php_version AND OLD.cms_version <> NEW.cms_version) OR
   (OLD.php_version <> NEW.php_version AND OLD.cms_version = NEW.cms_version)
    THEN
      UPDATE `#__jstats_counter_cms_php_version`
      SET count = count - 1
      WHERE `php_version` = OLD.php_version
        AND `cms_version` = OLD.cms_version;

      IF NOT EXISTS (SELECT 1
        FROM `#__jstats_counter_cms_php_version` AS counter
        WHERE NEW.php_version = counter.php_version
          AND NEW.cms_version = counter.cms_version
      )
      THEN
       INSERT INTO `#__jstats_counter_cms_php_version` (cms_version, php_version, count) VALUES (NEW.cms_version, NEW.php_version, 1);
      ELSE
       UPDATE `#__jstats_counter_cms_php_version`
       SET count = count + 1
       WHERE `php_version` = NEW.php_version
	 AND `cms_version` = NEW.cms_version;
      END IF;
 END IF;
END$$

DELIMITER ;
