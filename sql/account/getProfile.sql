SELECT a.`id` as `account_id`, a.`last_name`, a.`first_name`, a.`display_name`, a.`language_code`, a.`timezone`,
       a.`type`, a.`guid`, a.`created_at`, a.`updated_at`
  FROM `Account` a
 WHERE a.`is_deleted` = 'N' and a.`id` = [ACCOUNT_ID]
 LIMIT 1;