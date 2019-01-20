SELECT s.`id` as `site_id`, c.`id` as `channel_id`
  FROM `Channel` c, `Site` s
 WHERE c.`is_deleted` = 'N' and c.`site_id` = s.`id` and s.`is_deleted` = 'N'
   and c.`privacy_type` = 'visibility.public' and c.`type` = 'channel.website'
   and CASE WHEN s.`custom_url` = '' THEN s.`url` ELSE s.`custom_url` END = '[SITE_URL]';