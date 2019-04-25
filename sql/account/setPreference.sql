INSERT INTO `AccountMeta` (`account_id`, `key`, `value`, `is_deleted`)
SELECT a.`id` as `account_id`, LOWER('preference.[TYPE_KEY]') as `key`, '[VALUE]' as `value`,
       CASE WHEN '[VALUE]' IN ('N', '') THEN 'Y' ELSE 'N' END as `is_deleted`
  FROM `Account` a
 WHERE a.`is_deleted` = 'N' and a.`id` = [ACCOUNT_ID]
    ON DUPLICATE KEY UPDATE `value` = '[VALUE]',
                            `is_deleted` = 'N',
                            `updated_at` = Now();