SELECT fm.`file_id`, f.`account_id`, CONCAT(f.`location`, f.`local_name`) as `cdn_path`,
       f.`public_name`, f.`local_name`, f.`hash`, f.`bytes`, f.`type`, f.`guid`,
       f.`expires_at`, f.`is_deleted`, f.`created_at`, f.`updated_at`,
       fm.`key`, fm.`value`,
       CASE WHEN fm.`key` LIKE 'geo.%' AND f.`account_id` <> [ACCOUNT_ID] THEN 'N'
            ELSE 'Y' END as `is_visible`
  FROM `File` f INNER JOIN `FileMeta` fm ON f.`id` = fm.`file_id`
 WHERE fm.`is_deleted` = 'N' and f.`is_deleted` = 'N' and f.`id` = [FILE_ID]
   and IFNULL(f.`expires_at`, DATE_ADD(Now(), INTERVAL 5 SECOND)) >= Now()
 ORDER BY fm.`key`, fm.`value`;