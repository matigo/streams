SELECT pa.`id`, pa.`name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`email`, pa.`guid`, pa.`is_active`,
       mm.`value` as `bio`,
       CASE WHEN pa.`account_id` = [ACCOUNT_ID] THEN 'Y' ELSE 'N' END as `is_you`,
       pa.`created_at`, ROUND(UNIX_TIMESTAMP(pa.`created_at`)) as `created_unix`,
       pa.`updated_at`, ROUND(UNIX_TIMESTAMP(pa.`updated_at`)) as `updated_unix`
  FROM `Persona` pa LEFT OUTER JOIN `PersonaMeta` mm ON pa.`id` = mm.`persona_id` AND mm.`key` = 'persona.bio'
 WHERE pa.`is_deleted` = 'N' and pa.`account_id` = [LOOKUP_ID]
 ORDER BY pa.`is_active` DESC, pa.`name`;