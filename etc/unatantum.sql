INSERT INTO `#__jstats_counter_server_os`
SELECT  server_os , COUNT(*) as count
FROM `#__jstats`
GROUP BY server_os;

INSERT INTO `#__jstats_counter_php_version`
SELECT  php_version , COUNT(*) as count
FROM `#__jstats`
GROUP BY php_version;

INSERT INTO `#__jstats_counter_db_version`
SELECT  db_version , COUNT(*) as count
FROM `#__jstats`
GROUP BY db_version;

INSERT INTO `#__jstats_counter_db_type`
SELECT  db_type , COUNT(*) as count
FROM `#__jstats`
GROUP BY db_type;

INSERT INTO `#__jstats_counter_cms_version`
SELECT  cms_version , COUNT(*) as count
FROM `#__jstats`
GROUP BY cms_version;

INSERT INTO `#__jstats_counter_cms_php_version`
SELECT  cms_version, php_version , COUNT(*) as count
FROM `#__jstats`
GROUP BY cms_version, php_version;

INSERT INTO `#__jstats_counter_db_type_version`
SELECT  db_type, db_version , COUNT(*) as count
FROM `#__jstats`
GROUP BY db_type, db_version;
