INSERT INTO `Site` (`user_id`, `url`, `name`, `description`, `theme`, `created_at`)
SELECT u.`id`, '[SITE_URL]', 'A Lovely 10C Site!', 'Eternally Yours on 10Centuries', '[THEME]', Now()
  FROM `User` u
 WHERE u.`is_deleted` = 'N' and u.`id` = [USER_ID]
 LIMIT 1;