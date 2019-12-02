SELECT acct.`id` as `account_id`, acct.`email`, acct.`last_name`, acct.`first_name`, acct.`display_name`, acct.`language_code`, acct.`timezone`, acct.`created_at`
  FROM `Account` acct
 WHERE acct.`is_deleted` = 'N' and acct.`type` IN ('account.admin', 'account.normal') and acct.`id` = [ACCOUNT_ID]
 ORDER BY acct.`id`
 LIMIT 1;