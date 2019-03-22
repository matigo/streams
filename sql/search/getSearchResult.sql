SELECT tmp.`score`, tmp.`post_id`, po.`type`, po.`guid`, po.`title`, po.`value`,
       CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, po.`canonical_url`) as `canonical_url`,
       po.`privacy_type`, po.`publish_at`, po.`expires_at`,
       pa.`name`, pa.`display_name`, CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, '/avatars', pa.`avatar_img`) as `avatar_url`
  FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                    INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                    INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                    INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                    INNER JOIN (SELECT DISTINCT po.`id` as `post_id`, po.`publish_at`,
                                       CASE WHEN GREATEST(IFNULL(ca.`can_write`, 'N'), IFNULL(ca.`can_read`, 'N')) = 'Y' THEN 'Y'
                                            WHEN GREATEST(CASE WHEN ch.`privacy_type` = 'visibility.none' THEN 0 WHEN ch.`privacy_type` = 'visibility.private' THEN 1 WHEN ch.`privacy_type` = 'visibility.password' THEN 2 ELSE 9 END,
                                                          CASE WHEN po.`privacy_type` = 'visibility.none' THEN 0 WHEN po.`privacy_type` = 'visibility.private' THEN 1 WHEN po.`privacy_type` = 'visibility.password' THEN 2 ELSE 9 END) = 9 THEN 'Y'
                                            ELSE 'N' END as `is_visible`,
[SCORING]
                                       0 AS `score`
                                  FROM `Channel` ch INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                                                    INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                                               LEFT OUTER JOIN `ChannelAuthor` ca ON ch.`id` = ca.`channel_id` AND ca.`is_deleted` = 'N'
                                               LEFT OUTER JOIN `Persona` cp ON ca.`persona_id` = cp.`id` AND cp.`is_deleted` = 'N' AND cp.`account_id` = [ACCOUNT_ID]
                                               LEFT OUTER JOIN (SELECT pm.`post_id`, pm.`value` FROM `PostMeta` pm WHERE pm.`is_deleted` = 'N' and pm.`key` = 'geo_description') geo ON po.`id` = geo.`post_id`
                                               LEFT OUTER JOIN (SELECT pt.`post_id`, GROUP_CONCAT(pt.`key`) AS `tags` FROM `PostTags` pt WHERE pt.`is_deleted` = 'N' GROUP BY pt.`post_id`) tags ON po.`id` = tags.`post_id`
                                 WHERE ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
                                   and ch.`id` = [CHANNEL_ID] and po.`type` IN ('post.article', 'post.bookmark', 'post.note', 'post.quotation')
                                   and Now() BETWEEN po.`publish_at` AND IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE))
                                 ORDER BY `is_visible` DESC, `score` DESC, po.`publish_at` DESC
                                 LIMIT [COUNT]) tmp ON po.`id` = tmp.`post_id`
 WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
   and su.`is_active` = 'Y' and tmp.`is_visible` = 'Y' and IFNULL(tmp.`score`, 0) > 0
 ORDER BY tmp.`score` DESC, po.`publish_at` DESC;