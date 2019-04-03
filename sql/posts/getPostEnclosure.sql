SELECT fi.`public_name`, fi.`bytes`, fi.`type`, CONCAT(fi.`location`, fi.`local_name`) as `url`
  FROM `PostFile` pf INNER JOIN `File` fi ON pf.`file_id` = fi.`id`
 WHERE fi.`is_deleted` = 'N' and IFNULL(fi.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE)) > Now()
   and pf.`is_deleted` = 'N' and pf.`post_id` = [POST_ID]
 ORDER BY fi.`bytes` DESC
 LIMIT [COUNT];