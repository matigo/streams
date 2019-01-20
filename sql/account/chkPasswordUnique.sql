SELECT COUNT(ap.`id`) as `records`
  FROM `Account` a INNER JOIN `AccountPass` ap ON a.`id` = ap.`account_id`
 WHERE ap.`is_deleted` = 'N' and a.`is_deleted` = 'N' and a.`id` = [ACCOUNT_ID]
   and ap.`password` = sha2(CONCAT('[SHA_SALT]', '[USERPASS]'), 512);