UPDATE `Account` a
   SET a.`password` = sha2(CONCAT('[SHA_SALT]', '[USERPASS]'), 512),
       a.`updated_at` = Now()
 WHERE a.`is_deleted` = 'N' and a.`person_id` = [PERSON_ID] and a.`id` = [ACCOUNT_ID]
 LIMIT 1;