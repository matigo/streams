INSERT INTO `AccountMeta` (`account_id`, `type`, `value`)
SELECT a.`id`, 'preference.[TYPE_KEY]', '[VALUE]'
  FROM `Account` a
 WHERE a.`is_deleted` = 'N' and a.`id` = [ACCOUNT_ID]
    ON DUPLICATE KEY UPDATE `value` = '[VALUE]',
                            `is_deleted` = 'N',
                            `updated_at` = Now();