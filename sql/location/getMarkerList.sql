SELECT po.`id` as `post_id`,
       pm.`seq_id`, pm.`marked_at`, pm.`longitude`, pm.`latitude`, pm.`altitude`, pm.`value`,
       CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, '/api/geocode/staticmap/', ROUND(pm.`latitude`, 6), '/', ROUND(pm.`longitude`, 6)) as `map_url`
  FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                    INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                    INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                    INNER JOIN `PostMarker` pm ON po.`id` = pm.`post_id`
 WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` =  'N' and po.`is_deleted` = 'N' and pm.`is_deleted` = 'N'
   and su.`is_active` = 'Y' and po.`guid` = '[POST_GUID]'
 ORDER BY pm.`marked_at`, pm.`seq_id`;