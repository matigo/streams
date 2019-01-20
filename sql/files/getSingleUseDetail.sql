SELECT u.`id`, u.`file_id`, f.`name`, u.`hash`, f.`type`,
       u.`single_use`, u.`from_ip`, u.`requested_by`,
       u.`created_at`, DATE_ADD(u.`created_at`, INTERVAL 5 MINUTE) as `valid_until`
  FROM `File` f INNER JOIN `FileURL` u ON f.`id` = u.`file_id`
 WHERE f.`is_deleted` = 'N' and u.`is_deleted` = 'N' and u.`id` = [FILE_ID]
 LIMIT 1;