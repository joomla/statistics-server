insert into `jos_jstats_counter_server_os`
SELECT  server_os , COUNT(*) as count
FROM jos_jstats 
GROUP BY server_os;

insert into `jos_jstats_counter_php_version`
SELECT  php_version , COUNT(*) as count
FROM jos_jstats 
GROUP BY php_version;

insert into `jos_jstats_counter_db_version`
SELECT  db_version , COUNT(*) as count
FROM jos_jstats
GROUP BY db_version;

insert into `jos_jstats_counter_db_type`
SELECT  db_type , COUNT(*) as count
FROM jos_jstats
GROUP BY db_type;

insert into `jos_jstats_counter_cms_version`
SELECT  cms_version , COUNT(*) as count
FROM jos_jstats
GROUP BY cms_version;
