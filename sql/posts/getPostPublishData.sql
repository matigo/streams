SELECT po.`id` as `post_id`, po.`value` as `post_text`,
       CASE WHEN po.`type` = 'post.note' THEN 'Y' ELSE 'N' END as `is_note`,
       CONCAT(CASE WHEN si.`https` THEN 'https' ELSE 'http' END, '://', su.`url`, po.`canonical_url`) as `post_url`
  FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                 INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                 INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
 WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N'
   and po.`publish_at` <= Now() and IFNULL(po.`expires_at`, Now()) >= Now() and su.`is_active` = 'Y'
   and po.`privacy_type` = 'visibility.public' and po.`type` NOT IN ('post.draft')
   and po.`id` = [POST_ID]
 ORDER BY po.`id` DESC
 LIMIT 1;