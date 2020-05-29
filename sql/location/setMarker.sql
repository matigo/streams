INSERT INTO `PostMarker` (`post_id`, `seq_id`, `marked_at`, `longitude`, `latitude`, `altitude`, `value`)
SELECT po.`id` as `post_id`, IFNULL(MAX(pm.`seq_id`), 0) + 1 as `seq_id`,
       CASE WHEN FROM_UNIXTIME(UNIX_TIMESTAMP('[EVENT_AT]')) < DATE_SUB(LEAST(po.`publish_at`, po.`created_at`), INTERVAL 1 YEAR) THEN LEAST(po.`publish_at`, po.`created_at`)
            ELSE FROM_UNIXTIME(UNIX_TIMESTAMP('[EVENT_AT]')) END as `marked_at`,
       [LONGITUDE] as `longitude`, [LATITUDE] as `latitude`, [ALTITUDE] as `altitude`, '[NOTE]' as `value`
  FROM `Persona` pa INNER JOIN `Post` po ON pa.`id` = po.`persona_id`
               LEFT OUTER JOIN `PostMarker` pm ON po.`id` = pm.`post_id`
 WHERE po.`is_deleted` = 'N' and po.`id` = [POST_ID]
   and pa.`is_deleted` = 'N' and pa.`id` = [ACCOUNT_ID]
 GROUP BY po.`id` LIMIT 1;