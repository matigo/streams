INSERT INTO `Tokens` (`guid`, `account_id`, `client_id`)
SELECT CONCAT(uuid(), '-', LEFT(md5(a.`email`), 4), '-', LEFT(md5(count(p.`id`) + a.`id`), 8)) as `guid`, a.`id` as `account_id`, c.`id` as `client_id`
  FROM `Account` a INNER JOIN `Persona` p ON a.`id` = p.`account_id`
                   INNER JOIN `Client` c
 WHERE p.`is_deleted` = 'N' and c.`is_deleted` = 'N' and a.`is_deleted` = 'N'
   and a.`type` IN ('account.admin', 'account.normal') and c.`guid` = '[CLIENT_GUID]'
   and a.`id` = [ACCOUNT_ID]
 GROUP BY a.`email`, a.`id`, c.`id`
 LIMIT 1;