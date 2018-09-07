INSERT INTO `#__jstats_counter_db_type_version`
SELECT  db_type, db_version , COUNT(*) as count
FROM #__jstats 
GROUP BY db_type, db_version;
