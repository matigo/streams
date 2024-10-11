SELECT acct.`id` as `account_id`, acct.`last_name`, acct.`first_name`, acct.`display_name`, acct.`language_code`, acct.`timezone`,
       acct.`type`, acct.`guid`,
       CASE WHEN acct.`id` = [ACCOUNT_ID] THEN 'Y' ELSE 'N' END as `is_you`,
       acct.`created_at`, ROUND(UNIX_TIMESTAMP(acct.`created_at`)) as `created_unix`,
       acct.`updated_at`, ROUND(UNIX_TIMESTAMP(acct.`updated_at`)) as `updated_unix`
  FROM `Account` acct
 WHERE acct.`is_deleted` = 'N' and acct.`id` = [LOOKUP_ID]
 LIMIT 1;