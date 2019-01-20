SELECT po.`id`, IFNULL(po.`thread_id`, po.`id`) as `thread_id`, IFNULL(po.`parent_id`, po.`id`) as `parent_id`,
       CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https://' ELSE 'http://' END, su.`url`, po.`canonical_url`) as `post_url`, su.`updated_at`
  FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                 INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                 INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
 WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' 
   and su.`is_active` = 'Y' and po.`guid` = '[POST_GUID]'
 ORDER BY su.`updated_at` DESC
 LIMIT 1;