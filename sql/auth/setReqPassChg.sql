INSERT INTO `AccountMeta` (`account_id`, `type`, `value`)
SELECT a.`id`, 'system.password.reqchange', 'Y'
  FROM `Account` a
 WHERE a.`type` NOT IN ('account.guest', 'account.admin') and a.`id` = [ACCOUNT_ID]
    ON DUPLICATE KEY UPDATE `value` = 'Y',
                            `updated_at` = Now();