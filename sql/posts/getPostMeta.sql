SELECT pm.`key`,
       CASE WHEN pm.`key` IN ('geo_altitude', 'geo_latitude', 'geo_longitude') THEN ROUND(pm.`value`, 5) ELSE pm.`value` END as `value`,
       CASE WHEN pm.`is_private` = 'Y' AND [ACCOUNT_ID] NOT IN (po.`created_by`, po.`updated_by`) THEN 'N' ELSE 'Y' END as `is_visible`
  FROM `Post` po INNER JOIN `PostMeta` pm ON po.`id` = pm.`post_id`
 WHERE pm.`is_deleted` = 'N' and po.`is_deleted` = 'N' and po.`guid` = '[POST_GUID]'
 ORDER BY pm.`key`;