SELECT pm.`post_id`, pm.`persona_id`, pa.`name`, pa.`guid`, CASE WHEN pa.`account_id` = [ACCOUNT_ID] THEN 'Y' ELSE 'N' END as `is_you`
  FROM `PostMention` pm INNER JOIN `Persona` pa ON pm.`persona_id` = pa.`id`
                        INNER JOIN `Account` a ON pa.`account_id` = a.`id`
 WHERE a.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and pm.`is_deleted` = 'N' and pm.`post_id` IN ([POST_IDS])
 ORDER BY pm.`post_id`, pm.`persona_id`;