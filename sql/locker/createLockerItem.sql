INSERT INTO `Post` (`persona_id`, `client_id`, `value`, `channel_id`, `type`, `privacy_type`, `publish_at`, `expires_at`, `created_by`, `updated_by`)
SELECT p.`id` as `persona_id`,
       (SELECT z.`id` FROM `Client` z WHERE z.`is_deleted` = 'N' and z.`name` = 'Locker' LIMIT 1) as `client_id`,
       '[CONTENT]' as `value`,
       (SELECT y.`id` FROM `SiteUrl` z INNER JOIN `Channel` y ON z.`site_id` = y.`site_id`
         WHERE y.`is_deleted` = 'N' and z.`is_deleted` = 'N' and z.`url` = '[SITE_URL]' ORDER BY z.`is_active` DESC LIMIT 1) as `channel_id`,
       'post.locker' as `type`,
       'visibility.private' as `privacy_type`,
       Now() as `publish_at`,
       CASE WHEN [EXPY_MINS] > 0 THEN DATE_ADD(Now(), INTERVAL [EXPY_MINS] MINUTE) ELSE NULL END as `expires_at`,
       a.`id` as `created_by`,
       a.`id` as `updated_by`
  FROM `Account` a INNER JOIN `Persona` p ON a.`id` = p.`account_id`
 WHERE p.`is_deleted` = 'N' and a.`is_deleted` = 'N' and a.`email` = 'locker@noaddy.com'
 ORDER BY p.`id` DESC
 LIMIT 1;