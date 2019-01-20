SELECT a.`id` as `account_id`, a.`type`, a.`display_name`, a.`language_code`,
       IFNULL((SELECT z.`avatar_img` FROM `Persona` z
                WHERE z.`is_deleted` = 'N' and z.`account_id` = a.`id`
                ORDER BY z.`is_active` DESC LIMIT 1), 'default.png') as `avatar_url`,
       a.`created_at`, a.`updated_at`
  FROM `Account` a
 WHERE a.`is_deleted` = 'N' and a.`id` IN ([ACCOUNT_IDS])
 GROUP BY a.`id`, a.`email`, a.`type`, a.`display_name`, a.`language_code`;