SELECT pa.`name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`guid` as `persona_guid`,
       (SELECT CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `site_url`
          FROM `ChannelAuthor` ca INNER JOIN `Channel` ch ON ca.`channel_id` = ch.`id`
                                  INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                                  INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
         WHERE si.`is_deleted` = 'N' and ca.`is_deleted` = 'N' and ca.`can_write` = 'Y'
           and ch.`is_deleted` = 'N' and ch.`type` = 'channel.site' and ch.`privacy_type` = 'visibility.public'
           and su.`is_deleted` = 'N' and su.`is_active` = 'Y' and ca.`persona_id` = pa.`id`
         LIMIT 1) as `site_url`,
       (SELECT z.`value` FROM `PersonaMeta` z WHERE z.`is_deleted` = 'N' and z.`key` = 'persona.bio' and z.`persona_id` = pa.`id`) as `persona_bio`,
       a.`timezone`, pa.`created_at`, DATEDIFF(DATE_FORMAT(Now(), '%Y-%m-%d 00:00:00'), DATE_FORMAT(pa.`created_at`, '%Y-%m-%d 00:00:00')) as `days`,
       CASE WHEN a.`id` = [ACCOUNT_ID] THEN 'Y' ELSE 'N' END as `is_you`
  FROM `Account` a INNER JOIN `Persona` pa ON a.`id` = pa.`account_id`
 WHERE pa.`is_deleted` = 'N' and a.`is_deleted` = 'N' and pa.`guid` = '[PERSONA_GUID]'
 LIMIT 1;