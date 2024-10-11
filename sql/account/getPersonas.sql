SELECT p.`id`, p.`name`, p.`last_name`, p.`first_name`, p.`display_name`, p.`avatar_img`, p.`email`, p.`guid`, p.`is_active`,
       CASE WHEN p.`account_id` = [ACCOUNT_ID] THEN 'Y' ELSE 'N' END as `is_you`,
       p.`created_at`, ROUND(UNIX_TIMESTAMP(p.`created_at`)) as `created_unix`,
       p.`updated_at`, ROUND(UNIX_TIMESTAMP(p.`updated_at`)) as `updated_unix`
  FROM `Persona` p
 WHERE p.`is_deleted` = 'N' and p.`account_id` = [LOOKUP_ID]
 ORDER BY p.`is_active` DESC, p.`name`;