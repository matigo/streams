SELECT 'site.name' as `key`, s.`name` as `value` FROM `Site` s WHERE s.`is_deleted` = 'N' and s.`id` = [SITE_ID]
 UNION ALL
SELECT 'site.subtitle' as `key`, s.`description` FROM `Site` s WHERE s.`is_deleted` = 'N' and s.`id` = [SITE_ID]
 UNION ALL
SELECT 'site.tags' as `key`, s.`keywords` FROM `Site` s WHERE s.`is_deleted` = 'N' and s.`id` = [SITE_ID]
 UNION ALL
SELECT 'site.author', u.`display_name` FROM `Site` s, `User` u
 WHERE u.`is_deleted` = 'N' and u.`id` = s.`user_id` and s.`is_deleted` = 'N' and s.`id` = [SITE_ID]
 UNION ALL
SELECT 'site.mailaddr', u.`email` FROM `Site` s, `User` u
 WHERE u.`is_deleted` = 'N' and u.`id` = s.`user_id` and s.`is_deleted` = 'N' and s.`id` = [SITE_ID]
 UNION ALL
SELECT 'site.owner', s.`user_id` FROM `Site` s WHERE s.`is_deleted` = 'N' and s.`id` = [SITE_ID]
 UNION ALL
SELECT 'rss.channel_id', c.`id` FROM `Channel` c WHERE c.`is_deleted` = 'N' and c.`site_id` = [SITE_ID]
 UNION ALL
SELECT sm.`key`, sm.`value` FROM `SiteMeta` sm
 WHERE sm.`is_deleted` = 'N' and sm.`site_id` = [SITE_ID]
 ORDER BY CASE WHEN `key` LIKE 'site.%' THEN 0 ELSE 1 END, `key`