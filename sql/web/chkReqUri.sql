SELECT CASE WHEN LOWER(po.`canonical_url`) = LOWER('[REQ_URI]') THEN 'Y'
            WHEN po.`guid` = LOWER('[REQ_URI]') THEN 'Y'
            ELSE 'N' END as `is_match`,
       CASE WHEN LOWER(po.`canonical_url`) = LOWER('[REQ_URI]') THEN 2
            WHEN po.`guid` = LOWER('[REQ_URI]') THEN 1
            ELSE 99 END as `sort_order`,
       CASE WHEN po.`type` = 'post.location' THEN 'map'
            WHEN po.`type` = 'post.note' THEN 'note'
            ELSE 'main' END as `template`,
       po.`guid`
  FROM `Site` si INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                 INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
 WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and si.`id` = [SITE_ID]
 ORDER BY `is_match` DESC, `sort_order` LIMIT 1;