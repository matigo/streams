SELECT f.`localname` as `filename`, f.`hash` as `filehash`, f.`location` as `filepath`, f.`bytes`, u.`id` as `url_id`, u.`single_use`
  FROM `File` f INNER JOIN `FileURL` u ON f.`id` = u.`file_id`
 WHERE f.`is_deleted` = 'N' and u.`is_deleted` = 'N'
   and IFNULL(f.`expires_at`, DATE_ADD(Now(), INTERVAL 5 MINUTE)) >= Now() and DATE_ADD(u.`created_at`, INTERVAL 5 MINUTE) >= Now()
   and u.`hash` = '[FILE_HASH]' and f.`type` = '[FILE_TYPE]'
 UNION ALL
SELECT f.`localname` as `filename`, f.`hash` as `filehash`, f.`location` as `filepath`, f.`bytes`, NULL as `url_id`, 'N' as `single_use`
  FROM `File` f 
 WHERE f.`is_deleted` = 'N' and IFNULL(f.`expires_at`, DATE_ADD(Now(), INTERVAL 5 MINUTE)) >= Now()
   and f.`localname` = REPLACE('[FILE_NAME]', CONCAT('_', LOWER('[RES_TYPE]')), '') and f.`type` = '[FILE_TYPE]'
   and 'Y' = CASE WHEN '[RES_TYPE]' IN ('thumb', 'medium') THEN 'Y' ELSE 'N' END
 LIMIT 1;