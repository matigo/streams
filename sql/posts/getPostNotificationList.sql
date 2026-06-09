SELECT pa.`account_id`, pa.`name`,
       CASE WHEN po.`value` LIKE CONCAT('@', pa.`name`, '%') THEN 'Y' ELSE 'N' END as `is_reply`,
       src.`name` as `post_from`, po.`guid` as `post_guid`, po.`value` as `post_text`, apt.`device_token`
  FROM `Post` po INNER JOIN `PostMention` pm ON po.`id` = pm.`post_id`
                 INNER JOIN `Persona` pa ON pm.`persona_id` = pa.`id`
                 INNER JOIN `AccountPushTokens` apt ON pa.`account_id` = apt.`account_id`
                 INNER JOIN `Persona` src ON po.`persona_id` = src.`id`
 WHERE po.`is_deleted` = 'N' and pm.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and apt.`is_deleted` = 'N'
   and po.`publish_at` BETWEEN DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -15 SECOND) AND DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 15 SECOND)
   and po.`privacy_type` IN ('visibility.public', 'visibility.private')
   and LENGTH(apt.`device_token`) = 64
   and po.`id` = [POST_ID]
 ORDER BY pa.`name`, apt.`device_token`;