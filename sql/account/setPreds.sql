INSERT INTO `AccountPred` (`account_id`, `type`, `value`)
SELECT a.`id` as `account_id`, '[TYPE]' as `type`, '[VALUE]' as `value`
  FROM `Account` a
 WHERE a.`is_deleted` = 'N' and a.`id` = [ACCOUNT_ID]
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                            `updated_at` = Now();