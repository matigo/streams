SELECT pg.`post_id`, pg.`publish_at`
  FROM (SELECT CASE WHEN ch.`privacy_type` <> 'visibility.public' AND 0 <= 0 THEN 'N'
                    WHEN po.`privacy_type` <> 'visibility.public' THEN IFNULL(tmp.`can_read`, 'N')
                    ELSE 'Y' END as `is_visible`,
               po.`id` as `post_id`, po.`publish_at`
          FROM `PostTags` pt INNER JOIN `Post` po ON pt.`post_id` = po.`id`
                             INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                             INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                             INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                        LEFT OUTER JOIN (SELECT ca.`persona_id`, ca.`channel_id`, ca.`can_read`, ca.`can_write`
                                           FROM `Account` a INNER JOIN `Persona` pa ON a.`id` = pa.`account_id`
                                                            INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                                          WHERE ca.`is_deleted` = 'N' and pa.`is_deleted` = 'N' 
                                            and a.`id` = [ACCOUNT_ID]) tmp ON po.`persona_id` = tmp.`persona_id` AND ch.`id` = tmp.`channel_id`
         WHERE po.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and si.`is_deleted` = 'N'
           and IFNULL(po.`expires_at`, Now()) >= Now() and si.`guid` = '[SITE_GUID]'
           and 'Y' = CASE WHEN po.`privacy_type` = 'visibility.public' THEN 'Y'
                          WHEN pa.`account_id` = [ACCOUNT_ID] THEN 'Y'
                          ELSE 'N' END
           and pt.`key` = '[TAG_KEY]'
         ORDER BY po.`publish_at` DESC) pg
 WHERE pg.`is_visible` = 'Y'
 ORDER BY pg.`publish_at` DESC
 LIMIT [PAGE], [COUNT];