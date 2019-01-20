SELECT pg.`post_id`, pg.`publish_at`
  FROM (SELECT CASE WHEN ch.`privacy_type` <> 'visibility.public' THEN 'N'
                    WHEN po.`privacy_type` = 'visibility.none' AND pa.`account_id` <> [ACCOUNT_ID] THEN 'N'
                    WHEN po.`privacy_type` <> 'visibility.public' THEN IFNULL(tmp.`can_read`, 'N')
                    ELSE 'Y' END as `is_visible`,
               CASE WHEN po.`type` = 'post.note' THEN vis.`show_note`
                    WHEN po.`type` = 'post.article' THEN vis.`show_article`
                    WHEN po.`type` = 'post.bookmark' THEN vis.`show_bookmark`
                    WHEN po.`type` = 'post.quotation' THEN vis.`show_quotation`
                    ELSE 'N' END as `aux_visible`,
               po.`id` as `post_id`, po.`publish_at`
          FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                         INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                         INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                         INNER JOIN (SELECT sm.`site_id`, 0 as `sort_id`,
                                            CASE WHEN MAX(CASE WHEN sm.`key` = 'show_note' THEN sm.`value` ELSE '-' END) <> '-'
                                                 THEN MAX(CASE WHEN sm.`key` = 'show_note' THEN sm.`value` ELSE '-' END) ELSE 'Y' END as `show_note`,
                                            CASE WHEN MAX(CASE WHEN sm.`key` = 'show_article' THEN sm.`value` ELSE '-' END) <> '-'
                                                 THEN MAX(CASE WHEN sm.`key` = 'show_article' THEN sm.`value` ELSE '-' END) ELSE 'Y' END as `show_article`,
                                            CASE WHEN MAX(CASE WHEN sm.`key` = 'show_bookmark' THEN sm.`value` ELSE '-' END) <> '-'
                                                 THEN MAX(CASE WHEN sm.`key` = 'show_bookmark' THEN sm.`value` ELSE '-' END) ELSE 'Y' END as `show_bookmark`,
                                            CASE WHEN MAX(CASE WHEN sm.`key` = 'show_quotation' THEN sm.`value` ELSE '-' END) <> '-'
                                                 THEN MAX(CASE WHEN sm.`key` = 'show_quotation' THEN sm.`value` ELSE '-' END) ELSE 'Y' END as `show_quotation`
                                       FROM `SiteMeta` sm INNER JOIN `Site` z ON sm.`site_id` = z.`id`
                                      WHERE sm.`is_deleted` = 'N' and z.`is_deleted` = 'N' and z.`guid` = '[SITE_GUID]'
                                      GROUP BY sm.`site_id`
                                      UNION ALL
                                     SELECT z.`id` as `site_id`, 1 as `sort_id`, 'Y' as `show_note`, 'Y' as `show_article`, 'Y' as `show_bookmark`, 'Y' as `show_quotation`
                                       FROM `Site` z
                                      WHERE z.`is_deleted` = 'N' and z.`guid` = '[SITE_GUID]'
                                      ORDER BY `sort_id`
                                      LIMIT 1) vis ON si.`id` = vis.`site_id`
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
           and 'Y' = CASE WHEN '[CANON_URL]' = '' THEN 'Y'
                          WHEN po.`canonical_url` = '[CANON_URL]' THEN 'Y'
                          ELSE 'N' END
         ORDER BY po.`publish_at` DESC) pg
 WHERE pg.`is_visible` = 'Y' and pg.`aux_visible` = 'Y'
 ORDER BY pg.`publish_at` DESC
 LIMIT [PAGE], [COUNT];