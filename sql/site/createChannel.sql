INSERT INTO `Channel` (`owner_id`, `name`, `type`, `privacy_type`, `site_id`, `guid`, `created_at`)
SELECT u.`id`, CONCAT('site_', s.`id`), '[CHAN_TYPE]', '[VISIBLE]', s.`id`, uuid(), Now()
  FROM `Site` s, `User` u
 WHERE s.`is_deleted` = 'N' and s.`user_id` = u.`id`
   and u.`is_deleted` = 'N' and u.`id` = [USER_ID] and s.`id` = [SITE_ID]
 LIMIT 1;