INSERT INTO `#__jstats_counter_cms_php_version`
SELECT  cms_version, php_version , COUNT(*) as count
FROM #__jstats 
GROUP BY cms_version, php_version;
