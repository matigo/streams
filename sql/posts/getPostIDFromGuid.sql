SELECT po.`id` as `post_id`, IFNULL(tmp.`can_write`, 'N') as `can_write`,
       CASE WHEN ch.`privacy_type` = 'visibility.public' THEN 'Y' 
            WHEN IFNULL(tmp.`can_write`, 'N') = 'Y' THEN 'Y'
            ELSE IFNULL(tmp.`can_read`, 'N') END as `can_read`
  FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
            LEFT OUTER JOIN (SELECT ca.`persona_id`, ca.`channel_id`, ca.`can_read`, ca.`can_write`
                               FROM `Account` a INNER JOIN `Persona` pa ON a.`id` = pa.`account_id`
                                                INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                              WHERE ca.`is_deleted` = 'N' and pa.`is_deleted` = 'N' 
                                and a.`id` = [ACCOUNT_ID]) tmp ON po.`persona_id` = tmp.`persona_id` AND ch.`id` = tmp.`channel_id`
 WHERE ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and po.`guid` = '[POST_GUID]'
 LIMIT 1;