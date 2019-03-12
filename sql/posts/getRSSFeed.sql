SELECT po.`persona_id`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`name` as `handle`,
       CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', tp.`site_url`, '/avatars/', pa.`avatar_img`) as `avatar_url`,
       po.`id` as `post_id`,
       IFNULL(po.`title`, (SELECT z.`value` FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`is_private` = 'N' and z.`key` = 'source_title' and z.`post_id` = po.`id` LIMIT 1)) as `post_title`,
       CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', tp.`site_url`, po.`canonical_url`) as `post_url`,
       (SELECT z.`value` FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`is_private` = 'N' and z.`key` = 'source_url' and z.`post_id` = po.`id` LIMIT 1) as `source_url`,
       po.`type` as `post_type`, po.`guid` as `post_guid`, po.`hash`, po.`value` as `post_text`,
       'N' as `has_audio`,
       DATE_FORMAT(po.`publish_at`, '%Y-%m-%dT%H:%i:%sZ') as `publish_at`,
       DATE_FORMAT(po.`updated_at`, '%Y-%m-%dT%H:%i:%sZ') as `updated_at`
  FROM `Site` si INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                 INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                 INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                 INNER JOIN (SELECT su.`site_id`,
                                    (SELECT z.`url` FROM `SiteUrl` z
                                      WHERE z.`is_deleted` = 'N' and z.`site_id` = su.`site_id`
                                      ORDER BY z.`is_active` DESC, z.`id` DESC LIMIT 1) as `site_url`,
                                    base.`type`, IFNULL(IFNULL(pref.`value`, sm.`value`), base.`is_default`) as `is_default`
                               FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                                                 INNER JOIN (SELECT 'post.article' as `type`, 'N' as `is_default` UNION ALL
                                                             SELECT 'post.bookmark' as `type`, 'N' as `is_default` UNION ALL
                                                             SELECT 'post.quotation' as `type`, 'N' as `is_default` UNION ALL
                                                             SELECT 'post.note' as `type`, 'N' as `is_default`) base ON base.`is_default` = 'N'
                                            LEFT OUTER JOIN `SiteMeta` sm ON sm.`is_deleted` = 'N' and base.`type` = REPLACE(sm.`key`, 'show_', 'post.') and sm.`site_id` = si.`id`
                                            LEFT OUTER JOIN (SELECT 'post.article' as `type`, CASE WHEN '[SHOW_ARTICLE]' IN ('Y', 'N') THEN '[SHOW_ARTICLE]' ELSE NULL END as `value` UNION ALL
                                                             SELECT 'post.bookmark' as `type`, CASE WHEN '[SHOW_BOOKMARK]' IN ('Y', 'N') THEN '[SHOW_BOOKMARK]' ELSE NULL END as `value` UNION ALL
                                                             SELECT 'post.quotation' as `type`, CASE WHEN '[SHOW_QUOTATION]' IN ('Y', 'N') THEN '[SHOW_QUOTATION]' ELSE NULL END as `value` UNION ALL
                                                             SELECT 'post.note' as `type`, CASE WHEN '[SHOW_NOTE]' IN ('Y', 'N') THEN '[SHOW_NOTE]' ELSE NULL END as `value`) pref ON base.`type` = pref.`type`
                              WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and su.`url` = '[SITE_URL]') tp ON si.`id` = tp.`site_id` AND po.`type` = tp.`type`
 WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and tp.`is_default` = 'Y'
   and ch.`privacy_type` = 'visibility.public' and ch.`type` = 'channel.site' and po.`privacy_type` = 'visibility.public'
   and Now() BETWEEN po.`publish_at` AND IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 SECOND))
 ORDER BY po.`publish_at` DESC
 LIMIT [COUNT];

