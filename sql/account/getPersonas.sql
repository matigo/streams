SELECT p.`id`, p.`name`, p.`last_name`, p.`first_name`, p.`display_name`, p.`avatar_img`, p.`email`, p.`guid`, p.`is_active`, p.`created_at`, p.`updated_at`
  FROM `Persona` p
 WHERE p.`is_deleted` = 'N' and p.`account_id` = [ACCOUNT_ID]
 ORDER BY p.`is_active` DESC, p.`name`;