SELECT po.`id` as `post_id`, po.`parent_id`, po.`guid` as `post_guid`, po.`type` as `post_type`, po.`privacy_type`,
       (SELECT z.`guid` FROM `Post` z WHERE z.`is_deleted` = 'N' and z.`id` = IFNULL(po.`thread_id`, po.`id`)) as `thread_guid`,
       (SELECT COUNT(z.`id`) + 1  FROM `Post` z WHERE z.`is_deleted` = 'N' and z.`thread_id` = IFNULL(po.`thread_id`, po.`id`)) as `thread_posts`,
       po.`persona_id`, pa.`name` as `persona_name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`email`,
       pa.`guid` as `persona_guid`, pa.`is_active` as `persona_active`, pa.`created_at` as `persona_created_at`, pa.`updated_at` as `persona_updated_at`,
       po.`title`, po.`value`,
       (SELECT CASE WHEN COUNT(z.`key`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id` LIMIT 1) as `has_meta`,
       (SELECT GROUP_CONCAT(z.`value`) as `value` FROM `PostTags` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`) as `post_tags`,
       CASE WHEN po.`type` IN ('post.location')
            THEN (SELECT CASE WHEN COUNT(DISTINCT z.`seq_id`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMarker` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id` LIMIT 1)
            ELSE 'N' END as `has_markers`,
       (SELECT CASE WHEN COUNT(z.`persona_id`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMention` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`) as `has_mentions`,
       (SELECT GROUP_CONCAT('{"guid": "', zpa.`guid`, '", "as": "@', zpa.`name`, '", "is_you": "', CASE WHEN zpa.`account_id` = [ACCOUNT_ID] THEN 'Y' ELSE 'N' END, '"}') as `mentions`
          FROM `Persona` zpa INNER JOIN `PostMention` zpm ON zpa.`id` = zpm.`persona_id`
         WHERE zpa.`is_deleted` = 'N' and zpm.`is_deleted` = 'N' and zpm.`post_id` = po.`id`) as `mentions`,
       IFNULL(act.`pin_type`, 'pin.none') as `pin_type`, IFNULL(act.`is_starred`, 'N') as `is_starred`,
       IFNULL(act.`is_muted`, 'N') as `is_muted`, IFNULL(act.`points`, 0) as `points`,
       (SELECT SUM(pts.`points`) as `total` FROM `PostAction` pts
             WHERE pts.`is_deleted` = 'N' and pts.`points` <> 0 and pts.`post_id` = po.`id`) as `total_points`,
       po.`canonical_url`, po.`slug`, po.`reply_to`,
       po.`channel_id`, ch.`name` as `channel_name`, ch.`type` as `channel_type`, ch.`privacy_type` as `channel_privacy_type`, ch.`guid` as `channel_guid`,
       ch.`created_at` as `channel_created_at`, ch.`updated_at` as `channel_updated_at`,
       ch.`site_id`, (SELECT z.`url` FROM `SiteUrl` z WHERE z.`is_deleted` = 'N' and z.`site_id` = ch.`site_id` ORDER BY z.`is_active` DESC, z.`id` DESC LIMIT 1) as `site_url`, si.`https`,
       si.`name` as `site_name`, si.`description` as `site_description`, si.`keywords` as `site_keywords`, si.`theme` as `site_theme`,
       si.`guid` as `site_guid`, si.`created_at` as `site_created_at`, si.`updated_at` as `site_updated_at`,
       po.`client_id`, cl.`name` as `client_name`, cl.`logo_img` as `client_logo_img`, cl.`guid` as `client_guid`,
       po.`publish_at`, po.`expires_at`,
       po.`created_at`, po.`created_by`, po.`updated_at`
  FROM `Site` si INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                 INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                 INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
            LEFT OUTER JOIN `Client` cl ON po.`client_id` = cl.`id` AND cl.`is_deleted` = 'N'
            LEFT OUTER JOIN (SELECT pp.`post_id`, pp.`pin_type`, pp.`is_starred`, pp.`is_muted`, pp.`points`
                               FROM `PostAction` pp INNER JOIN `Persona` pz ON pp.`persona_id` = pz.`id`
                              WHERE pp.`is_deleted` = 'N' and pp.`post_id` IN ([POST_IDS])
                                and pz.`is_deleted` = 'N' and pz.`guid` = '[PERSONA_GUID]') act ON po.`id` = act.`post_id`
            LEFT OUTER JOIN (SELECT pr.`persona_id`, MAX(pr.`follows`) as `follows`
                              FROM `PersonaRelation` pr INNER JOIN `Persona` pz ON pr.`related_id` = pz.`id`
                             WHERE pr.`is_deleted` = 'N' and pz.`is_deleted` = 'N' and pz.`account_id`= [ACCOUNT_ID]
                             GROUP BY pr.`persona_id`) ff ON po.`persona_id` = ff.`persona_id`
 WHERE po.`is_deleted` = 'N' and po.`id` IN ([POST_IDS])
   and 'Y' = CASE WHEN pa.`account_id` = [ACCOUNT_ID] THEN 'Y'
                  WHEN po.`privacy_type` = 'visibility.public' THEN 'Y'
                  WHEN po.`privacy_type` = 'visibility.private' THEN IFNULL(ff.`follows`, 'N')
                  WHEN ch.`privacy_type` = 'visibility.password' AND '[SITE_TOKEN]' <> ''
                         THEN CASE WHEN '[SITE_TOKEN]' IN (SELECT SHA2(CONCAT(si.`guid`, '.', UNIX_TIMESTAMP(zsu.`updated_at`), '.', DATE_FORMAT(DATE_SUB(Now(), INTERVAL cnt.`num` HOUR), '%Y-%m-%d %H:00:00')), 256) as `hash`
                                                             FROM `SiteUrl` zsu INNER JOIN (SELECT 0 as `num` UNION ALL SELECT  1 as `num` UNION ALL SELECT  2 as `num`) cnt ON `num` >= 0
                                                            WHERE zsu.`is_deleted` = 'N' and zsu.`is_active` = 'Y' and zsu.`site_id` = si.`id`) THEN 'Y' ELSE 'N' END
                  ELSE 'N' END
 ORDER BY po.`publish_at` DESC, po.`id` DESC;