SELECT CASE WHEN s.`custom_url` != '' THEN s.`custom_url` ELSE s.`url` END as `site_url`,
       CASE WHEN p.`slug` = '' THEN REPLACE(p.`canonical_url`, '{id}', p.`id`) ELSE REPLACE(p.`slug`, '{id}', p.`id`) END as `post_url`,
       'N' as `is_default`
  FROM `Post` p, `Channel` c, `Site` s
 WHERE p.`is_deleted` = 'N' and p.`channel_id` = c.`id`
   and c.`is_deleted` = 'N' and c.`site_id` = s.`id`
   and s.`is_deleted` = 'N' and '[SITE_NAME]' IN (s.`url`, s.`custom_url`)
   and c.`privacy_type` IN ('visibility.public') and p.`privacy_type` IN ('visibility.public')
   and Now() BETWEEN p.`publish_at` and IFNULL(p.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE))
   and CASE WHEN p.`slug` = '' THEN REPLACE(p.`canonical_url`, '{id}', p.`id`) ELSE REPLACE(p.`slug`, '{id}', p.`id`) END LIKE '%[DOCUMENT]%'
 UNION ALL
SELECT CASE WHEN s.`custom_url` != '' THEN s.`custom_url` ELSE s.`url` END as `site_url`, '/' as `post_url`, 'Y' as `is_default`
  FROM `Channel` c, `Site` s
 WHERE c.`is_deleted` = 'N' and c.`site_id` = s.`id`
   and s.`is_deleted` = 'N' and '[SITE_NAME]' IN (s.`url`, s.`custom_url`)
   and c.`privacy_type` IN ('visibility.public')
 ORDER BY `is_default`