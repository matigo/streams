SELECT ch.`id` as `channel_id`, ch.`type` as `channel_type`, ch.`privacy_type`,
       MAX(CASE WHEN ch.`type` = 'channel.site' AND ch.`privacy_type` = 'visibility.public' THEN 'Y'
                WHEN ch.`privacy_type` = 'visibility.password' AND '[SITE_TOKEN]' <> ''
                     THEN CASE WHEN '[SITE_TOKEN]' IN (SELECT SHA2(CONCAT(si.`guid`, '.', UNIX_TIMESTAMP(zsu.`updated_at`), '.', DATE_FORMAT(DATE_SUB(Now(), INTERVAL cnt.`num` HOUR), '%Y-%m-%d %H:00:00')), 256) as `hash`
                                                         FROM `SiteUrl` zsu INNER JOIN (SELECT 0 as `num` UNION ALL SELECT  1 as `num` UNION ALL SELECT  2 as `num`) cnt ON `num` >= 0
                                                        WHERE zsu.`is_deleted` = 'N' and zsu.`is_active` = 'Y' and zsu.`site_id` = si.`id`) THEN 'Y' ELSE 'N' END
                WHEN ch.`privacy_type` <> 'visibility.public' THEN IFNULL(ca.`can_read`, 'N')
                ELSE 'N' END) as `can_access`
  FROM `Channel` ch LEFT OUTER JOIN `ChannelAuthor` ca ON ch.`id` = ca.`channel_id` AND ca.`is_deleted` = 'N'
                    LEFT OUTER JOIN `Persona` pa ON ca.`persona_id` = pa.`id` AND pa.`is_deleted` = 'N' and pa.`account_id` = [ACCOUNT_ID]
 WHERE ch.`is_deleted` = 'N' and ch.`site_id` = [SITE_ID]
 GROUP BY ch.`id`, ch.`type`, ch.`privacy_type`;
