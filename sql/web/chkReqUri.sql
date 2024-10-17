SELECT CASE WHEN LOWER(po.`canonical_url`) = LOWER('[REQ_URI]') THEN 'Y'
            WHEN LOWER('[REQ_URI]') IN ('/', '') THEN 'Y'
            WHEN po.`guid` = LOWER('[REQ_URI]') THEN 'Y'
            ELSE 'N' END as `is_match`,
       CASE WHEN LOWER(po.`canonical_url`) = LOWER('[REQ_URI]') THEN 2
            WHEN po.`guid` = LOWER('[REQ_URI]') THEN 1
            ELSE 99 END as `sort_order`,
       CASE WHEN po.`type` IN ('post.article', 'post.location', 'post.note') THEN REPLACE(po.`type`, 'post.', '')
            ELSE 'main' END as `template`,
       CASE WHEN LOWER('[REQ_URI]') IN ('/', '')
            THEN (SELECT z.`guid` FROM `Post` z
                   WHERE z.`is_deleted` = 'N' and z.`channel_id` = ch.`id`
                     and CURRENT_TIMESTAMP BETWEEN z.`publish_at` AND IFNULL(z.`expires_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR))
                     and z.`privacy_type` = 'visibility.public' and z.`type` = 'post.article'
                   ORDER BY z.`publish_at` DESC LIMIT 1)
            ELSE po.`guid` END as `guid`
  FROM `Site` si INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                 INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
 WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N'
   and po.`type` = 'post.article' and po.`privacy_type` = 'visibility.public'
   and si.`id` = [SITE_ID]
 ORDER BY `is_match` DESC, `sort_order` LIMIT 1;