SELECT ps.`word`,
       COUNT(DISTINCT po.`id`) as `instances`,
       COUNT(DISTINCT CASE WHEN pa.`account_id` = [ACCOUNT_ID] THEN po.`id` ELSE NULL END) as `yours`,
       MIN(GREATEST(po.`publish_at`, po.`created_at`)) as `first_at`, MAX(GREATEST(po.`publish_at`, po.`created_at`)) as `recent_at`
  FROM `PostSearch` ps INNER JOIN `Post` po ON ps.`post_id` = po.`id`
                       INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
 WHERE ps.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and ps.`word` = '[WORD]'
 GROUP BY ps.`word`;