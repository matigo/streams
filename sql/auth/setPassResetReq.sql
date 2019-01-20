INSERT INTO `AccountMeta` (`account_id`, `type`, `value`)
SELECT a.`id` as `account_id`, CONCAT('password.request-', UNIX_TIMESTAMP(Now())) as `type`, 'N' as `value`
  FROM `Account` a
 WHERE a.`is_deleted` = 'N'
   and 'Y' = CASE WHEN a.`login` = '[ACCOUNT_FILTER]' THEN 'Y'
                  WHEN a.`email` = '[ACCOUNT_FILTER]' THEN 'Y'
                  ELSE 'N' END
 LIMIT 1
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                            `updated_at` = Now();