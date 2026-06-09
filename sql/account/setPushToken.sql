UPDATE `AccountPushTokens`
   SET `updated_at` = current_timestamp
 WHERE `is_deleted` = 'N' and `device_token` = '[TOKEN]' and `account_id` = [ACCOUNT_ID];
[SQL_SPLITTER]
INSERT INTO `AccountPushTokens` (`account_id`, `device_token`)
SELECT tmp.`account_id`, tmp.`device_token`
  FROM (SELECT acct.`id` as `account_id`, '[TOKEN]' as `device_token`, COUNT(apn.`id`) as `tokens`
          FROM `Account` acct LEFT OUTER JOIN `AccountPushTokens` apn ON acct.`id` = apn.`account_id` AND apn.`is_deleted` = 'N' and apn.`device_token` = '[TOKEN]'
         WHERE acct.`is_deleted` = 'N' and acct.`id` = [ACCOUNT_ID]
         ORDER BY acct.`id` LIMIT 1) tmp
 WHERE tmp.`account_id` IS NOT NULL and tmp.`tokens` = 0
 LIMIT 1;