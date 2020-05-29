SELECT po.`id` as `post_id`, CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, po.`canonical_url`) as `reply_url`
  FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                    INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                    INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
 WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` =  'N' and po.`is_deleted` = 'N'
   and su.`is_active` = 'Y' and po.`guid` = '[GUID]'
 ORDER BY po.`id` DESC LIMIT 1;