SELECT COUNT(f.`id`) as `files`, 5 * 1024 * 1024 * 1024 as `size`, SUM(f.`bytes`) as `used`
  FROM `File` f
 WHERE f.`is_deleted` = 'N' and f.`account_id` = [ACCOUNT_ID]
   and IFNULL(f.`expires_at`, DATE_ADD(Now(), INTERVAL 5 SECOND)) >= Now()