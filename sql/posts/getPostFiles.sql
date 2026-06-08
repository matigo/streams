SELECT fi.`id` as `file_id`, fi.`guid` as `file_guid`, fi.`name`, fi.`type`, fi.`public_name`, fi.`bytes`, fi.`hash`,
       CONCAT(fi.`location`, fi.`local_name`) as `local_file`, 
       UNIX_TIMESTAMP(fi.`expires_at`) as `expires_unix`, UNIX_TIMESTAMP(fi.`created_at`) as `created_unix`, UNIX_TIMESTAMP(fi.`updated_at`) as `updated_unix`
  FROM `PostFile` pf INNER JOIN `File` fi ON pf.`file_id` = fi.`id`
 WHERE pf.`is_deleted` = 'N' and fi.`is_deleted` = 'N'
   and IFNULL(fi.`expires_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR)) > CURRENT_TIMESTAMP
   and pf.`post_id` = [POST_ID]
 ORDER BY fi.`id`;