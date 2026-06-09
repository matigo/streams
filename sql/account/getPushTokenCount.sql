SELECT acct.`guid` as `account_guid`, COUNT(pa.`id`) as `persona_count`, COUNT(apn.`id`) as `token_count`
  FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                 LEFT OUTER JOIN `AccountPushTokens` apn ON acct.`id` = apn.`account_id` AND apn.`is_deleted` = 'N'
 WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
   and pa.`is_active` = 'Y' and acct.`id` = [ACCOUNT_ID]
 GROUP BY acct.`guid`;