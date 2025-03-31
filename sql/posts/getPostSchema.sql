SELECT po.`id` as `post_id`, po.`guid` as `post_guid`, ROUND(UNIX_TIMESTAMP(po.`updated_at`)) as `version`,
       LOWER(CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, po.`canonical_url`)) as `url`, su.`url` as `domain`, po.`type`,
       po.`title`, mm.`value` as `summary`, pa.`display_name` as `author_name`,
       DATE_FORMAT(po.`publish_at`, '%Y-%m-%d') as `publish_ymd`, DATE_FORMAT(GREATEST(po.`publish_at`, po.`updated_at`), '%Y-%m-%d') as `updated_ymd`
  FROM `Site` si INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                 INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                 INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
            LEFT OUTER JOIN `PostMeta` mm ON po.`id` = mm.`post_id` AND mm.`key` = 'post_summary' AND mm.`is_deleted` = 'N'
            LEFT OUTER JOIN `SiteUrl` su ON si.`id` = su.`site_id` AND su.`is_active` = 'Y' and su.`is_deleted` = 'N'
 WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and ch.`site_id` = [SITE_ID]
   and po.`is_deleted` = 'N' and po.`publish_at` <= CURRENT_TIMESTAMP and IFNULL(po.`expires_at`, CURRENT_TIMESTAMP) >= CURRENT_TIMESTAMP
   and po.`type` = 'post.article' and po.`guid` = '[POST_GUID]'
 ORDER BY po.`id`
 LIMIT 1;