SELECT u.`id` as `user_id`, u.`type`, u.`display_name`,
       (SELECT c.`guid` FROM `Client` c
         WHERE c.`is_deleted` = 'N' and c.`guid` = '[CLI_GUID]') as `client_guid`
  FROM `User` u
 WHERE u.`is_deleted` = 'N' and u.`type` IN ('user.admin', 'user.premium', 'user.normal')
   and u.`id` = [ACCOUNT_ID] and u.`created_at` >= DATE_SUB(Now(), INTERVAL 5 MINUTE)
 ORDER BY u.`id`
 LIMIT 1;