SELECT s.`id` as `site_id`, s.`url`, s.`custom_url`, s.`name`, s.`description`, s.`keywords`, s.`https`, s.`theme`, s.`created_at`,
       s.`user_id`, u.`display_name`, u.`avatar_url`, u.`created_at`,
       (SELECT sm.`value` FROM `SiteMeta` sm
         WHERE sm.`is_deleted` = 'N' and sm.`key` = 'rss.mailaddr' and sm.`site_id` = s.`id`) as `site_mail`,
       (SELECT sm.`value` FROM `SiteMeta` sm
         WHERE sm.`is_deleted` = 'N' and sm.`key` = 'site.banner' and sm.`site_id` = s.`id`) as `banner`,
       (SELECT sm.`value` FROM `SiteMeta` sm
         WHERE sm.`is_deleted` = 'N' and sm.`key` = 'rss.license' and sm.`site_id` = s.`id`) as `license`,
       c.`id` as `channel_id`, c.`name` as `channel_name`, c.`type` as `channel_type`, c.`privacy_type` as `channel_privacy`,
       c.`guid` as `channel_guid`, c.`created_at` as `channel_created_at`
  FROM `Channel` c, `Site` s, `User` u
 WHERE c.`is_deleted` = 'N' and c.`site_id` = s.`id`
   and u.`is_deleted` = 'N' and u.`id` = s.`user_id`
   and s.`is_deleted` = 'N' and s.`id` = [SITE_ID]
 LIMIT 1;