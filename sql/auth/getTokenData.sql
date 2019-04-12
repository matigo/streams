SELECT a.`id` as `account_id`, a.`email`, a.`type`, a.`display_name`, a.`language_code`, a.`timezone`,
       IFNULL((SELECT z.`avatar_img` FROM `Persona` z
                WHERE z.`is_deleted` = 'N' and z.`account_id` = a.`id`
                ORDER BY z.`is_active` DESC LIMIT 1), 'default.png') as `avatar_url`,
       (SELECT z.`guid` FROM `Persona` z
         WHERE z.`is_deleted` = 'N' and z.`account_id` = a.`id`
         ORDER BY z.`is_active` DESC, z.`id` LIMIT 1) as `default_persona`,
       (SELECT z.`guid` FROM `Channel` z
         WHERE z.`is_deleted` = 'N' and z.`type` = 'channel.site' and z.`privacy_type` = 'visibility.public'
           and z.`account_id` = a.`id`
         ORDER BY z.`id` LIMIT 1) as `default_channel`,
       (SELECT CASE WHEN MAX(ca.`can_write`) = 'Y' THEN 'write'
                    WHEN MAX(ca.`can_read`) = 'Y' THEN 'read'
                    ELSE 'none' END as `access`
          FROM `SiteUrl` su INNER JOIN `Channel` ch ON su.`site_id` = ch.`site_id`
                            INNER JOIN `ChannelAuthor` ca ON ch.`id` = ca.`channel_id`
                            INNER JOIN `Persona` pa ON ca.`persona_id` = pa.`id`
         WHERE su.`is_deleted` = 'N' and su.`url` = '[HOMEURL]' and pa.`account_id` = t.`account_id`
         GROUP BY pa.`account_id`, ca.`persona_id`, ca.`channel_id`
         UNION ALL
        SELECT 'read' as `access`
          FROM `SiteUrl` su INNER JOIN `Channel` ch ON su.`site_id` = ch.`site_id`
                            INNER JOIN `Persona` pa
         WHERE su.`is_deleted` = 'N' and su.`url` = '[HOMEURL]' and pa.`account_id` = t.`account_id`
         GROUP BY pa.`account_id`, pa.`id`, ch.`id`
         ORDER BY `access` DESC
         LIMIT 1) as `access_level`,
       IFNULL((SELECT m.`value` FROM `AccountMeta` m
                WHERE m.`is_deleted` = 'N' and m.`key` = 'system.password.reqchange'
                  and m.`account_id` = a.`id`
                UNION ALL
               SELECT CASE WHEN max(ap.`created_at`) <= DATE_FORMAT(DATE_SUB(Now(), INTERVAL [PASSWORD_AGE] DAY), '%Y-%m-%d 00:00:00') THEN 'Y' ELSE 'N' END as `limit`
                 FROM `Account` z INNER JOIN `AccountPass` ap ON z.`id` = ap.`account_id`
                WHERE z.`type` NOT IN ('account.admin') and ap.`is_deleted` = 'N' and ap.`account_id` = a.`id`
                ORDER BY `value` DESC
                LIMIT 1), 'N') as `password_change`,
       IFNULL((SELECT m.`value` FROM `AccountMeta` m
                WHERE m.`is_deleted` = 'N' and m.`key` = 'system.welcome.done'
                  and m.`account_id` = a.`id`), 'N') as `welcome_done`
  FROM `Tokens` t INNER JOIN `Account` a ON t.`account_id` = a.`id`
 WHERE a.`is_deleted` = 'N' and t.`is_deleted` = 'N'
   and t.`updated_at` >= DATE_SUB(Now(), INTERVAL [LIFESPAN] DAY)
   and t.`guid` = '[TOKEN_GUID]' and t.`id` = [TOKEN_ID]
 GROUP BY a.`id`, a.`email`, a.`type`, a.`display_name`, a.`language_code`
 LIMIT 1;