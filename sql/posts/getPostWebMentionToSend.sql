SELECT pm.`post_id`,
       CONCAT(CASE WHEN si.`https` THEN 'https' ELSE 'http' END, '://', su.`url`, po.`canonical_url`) as `source_url`,
       pm.`value` as `target_url`
  FROM `PostMeta` pm INNER JOIN `Post` po  ON pm.`post_id` = po.`id`
                     INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                     INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                     INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
 WHERE pm.`is_deleted` = 'N' and po.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and si.`is_deleted` = 'N' and su.`is_deleted` = 'N'
   and su.`is_active` = 'Y' and po.`privacy_type` = 'visibility.public' and ch.`privacy_type` = 'visibility.public'
   and pm.`key` = 'source_url' and po.`publish_at` BETWEEN DATE_SUB(Now(), INTERVAL 7 DAY) AND Now()
   and po.`id` = [POST_ID]
 LIMIT 1;