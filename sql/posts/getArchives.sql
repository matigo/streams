SELECT po.`id` as `post_id`, IFNULL(po.`title`, pm.`value`) as `title`, po.`canonical_url`, po.`type`, po.`guid`, po.`privacy_type`, po.`publish_at`,
       GROUP_CONCAT(pt.`value`) AS `tag_list`
  FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                 INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                 INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
            LEFT OUTER JOIN `PostTags` pt ON po.`id` = pt.`post_id` AND pt.`is_deleted` = 'N' and pt.`is_deleted` = 'N'
            LEFT OUTER JOIN `PostMeta` pm ON po.`id` = pm.`post_id` AND pm.`is_deleted` = 'N' and pm.`key` = 'source_title'
            LEFT OUTER JOIN (SELECT ca.`persona_id`, ca.`channel_id`, ca.`can_read`, ca.`can_write`
                               FROM `Account` a INNER JOIN `Persona` pa ON a.`id` = pa.`account_id`
                                                INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                              WHERE ca.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
                                and a.`id` = [ACCOUNT_ID]) tmp ON po.`persona_id` = tmp.`persona_id` AND ch.`id` = tmp.`channel_id`
 WHERE po.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and si.`is_deleted` = 'N'
   and po.`type` IN ('post.article', 'post.bookmark', 'post.quotation') and po.`publish_at` <= Now()
   and IFNULL(po.`expires_at`, Now()) >= Now() and si.`guid` = '[SITE_GUID]'
   and 'Y' = CASE WHEN ch.`privacy_type` <> 'visibility.public' THEN 'N'
                  WHEN po.`privacy_type` = 'visibility.none' AND pa.`account_id` <> [ACCOUNT_ID] THEN 'N'
                  WHEN po.`privacy_type` <> 'visibility.public' THEN IFNULL(tmp.`can_read`, 'N')
                  WHEN po.`publish_at` > Now() AND pa.`account_id` <> [ACCOUNT_ID] THEN 'N'
                  ELSE 'Y' END
 GROUP BY po.`id`, po.`title`, po.`canonical_url`, po.`type`, po.`guid`, po.`privacy_type`, po.`publish_at`
 ORDER BY po.`publish_at`;