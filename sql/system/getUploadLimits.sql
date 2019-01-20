SELECT  IFNULL(a.`id`, 0) as `account_id`, COUNT(DISTINCT r.`scope`) as `scopes`
  FROM `Tokens` t INNER JOIN `Account` a ON t.`account_id` = a.`id`
                  INNER JOIN `AccountRoles` r ON a.`id` = r.`account_id`
                  INNER JOIN `Roles` s ON r.`scope` = s.`scope`
 WHERE t.`is_deleted` = 'N' and s.`is_deleted` = 'N' and r.`is_deleted` = 'N' and a.`is_deleted` = 'N' 
   and a.`type` IN ('account.admin', 'account.normal', 'account.student', 'account.guest')
   and IFNULL(r.`expires_at`, DATE_ADD(Now(), INTERVAL 5 MINUTE)) > Now()
   and TIMEDIFF(Now(), t.`updated_at`) < CAST('04:00:00' AS TIME)
   and s.`level` >= 50 and t.`id` = [TOKEN_ID] and t.`guid` = '[TOKEN_GUID]'
 LIMIT 1;