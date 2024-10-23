SELECT CASE WHEN pa.`account_id` = [ACCOUNT_ID] THEN 'Y'
            WHEN po.`privacy_type` IN ('visibility.public', 'visibility.password') THEN 'Y'
            ELSE IFNULL((SELECT CASE WHEN pr.`is_blocked` = 'N' THEN pr.`follows` ELSE 'N' END as `can_access`
                           FROM `PersonaRelation` pr INNER JOIN `Persona` za ON pr.`related_id` = za.`id`
                          WHERE pr.`persona_id` = pa.`id` and za.`account_id` = [ACCOUNT_ID] ORDER BY `can_access` DESC LIMIT 1), 'N')
       END as `is_visible`,

       ROW_NUMBER() OVER (PARTITION BY po.`channel_id` ORDER BY po.`publish_at`, po.`id`) AS `post_num`,

       pa.`name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`guid` as `persona_guid`, pa.`is_active` as `persona_active`,
       ROUND(UNIX_TIMESTAMP(pa.`created_at`)) as `persona_created_unix`,

       po.`id` as `post_id`, po.`guid` as `post_guid`, po.`title`, url.`site_url`, po.`canonical_url`, 
       ROUND(UNIX_TIMESTAMP(po.`publish_at`)) as `publish_unix`, ROUND(UNIX_TIMESTAMP(po.`expires_at`)) as `expires_unix`,
       po.`slug`, po.`type` as `post_type`, po.`privacy_type`,  po.`hash`,
       ROUND(UNIX_TIMESTAMP(po.`created_at`)) as `created_unix`, ROUND(UNIX_TIMESTAMP(po.`updated_at`)) as `updated_unix`
  FROM `Channel` ch INNER JOIN `Post` po ON ch.`id` = po.`channel_id` AND po.`is_deleted` = 'N'
                    INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id` AND pa.`is_deleted` = 'N'
                    INNER JOIN (SELECT ch.`id` as `channel_id`, CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `site_url`, su.`is_active`
                                  FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id` AND si.`is_deleted` = 'N'
                                                    INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id` AND su.`is_deleted` = 'N'
                                 WHERE ch.`is_deleted` = 'N' and ch.`guid` = '[CHANNEL_GUID]'
                                 ORDER BY su.`is_active` DESC LIMIT 1) url ON ch.`id` = url.`channel_id`
 WHERE ch.`is_deleted` = 'N' and ch.`guid` = '[CHANNEL_GUID]'
   and CURRENT_TIMESTAMP BETWEEN po.`publish_at` AND IFNULL(po.`expires_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR))
   and po.`type` IN ('post.article')
 ORDER BY po.`publish_at` DESC;