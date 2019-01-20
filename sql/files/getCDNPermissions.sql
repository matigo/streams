SELECT ar.`account_id`, a.`type`
  FROM `Roles` r INNER JOIN `AccountRoles` ar ON r.`scope` = ar.`scope`
                 INNER JOIN `Account` a ON ar.`account_id` = a.`id`
                 INNER JOIN `Tokens` t ON a.`id` = t.`account_id`
 WHERE t.`is_deleted` = 'N' and r.`is_deleted` = 'N' and ar.`is_deleted` = 'N' and a.`is_deleted` = 'N'
   and IFNULL(ar.`expires_at`, DATE_ADD(Now(), INTERVAL 5 MINUTE)) >= Now()
   and r.`scope` IN ('admin', 'level 1', 'level 2', 'manager', 'counsellor', 'uploader')
   and a.`type` IN ('account.admin', 'account.normal', 'account.student') 
   and t.`id` = [TOKEN_ID] and t.`guid` = '[TOKEN_GUID]'
 LIMIT 1;