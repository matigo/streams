SELECT po.`id` as `post_id`, po.`parent_id`, po.`guid` as `post_guid`, po.`type` as `post_type`, po.`privacy_type`,
       (SELECT z.`guid` FROM `Post` z WHERE z.`is_deleted` = 'N' and z.`id` = IFNULL(po.`thread_id`, po.`id`)) as `thread_guid`,
       (SELECT COUNT(z.`id`) + 1  FROM `Post` z WHERE z.`is_deleted` = 'N' and z.`thread_id` = IFNULL(po.`thread_id`, po.`id`)) as `thread_posts`,
       po.`persona_id`, pa.`name` as `persona_name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`account_id`,
       pa.`guid` as `persona_guid`, pa.`is_active` as `persona_active`, pa.`created_at` as `persona_created_at`, pa.`updated_at` as `persona_updated_at`,
       po.`title`, po.`value` as `text`, '' as `html`,
       (SELECT CASE WHEN COUNT(z.`key`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id` LIMIT 1) as `has_meta`,
       (SELECT GROUP_CONCAT(z.`value`) as `value` FROM `PostTags` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`) as `post_tags`,
       CASE WHEN po.`type` IN ('post.location')
            THEN (SELECT CASE WHEN COUNT(DISTINCT z.`seq_id`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMarker` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id` LIMIT 1)
            ELSE 'N' END as `has_markers`,
       po.`canonical_url`, po.`slug`, po.`reply_to`,
       ch.`site_id`, (SELECT z.`url` FROM `SiteUrl` z WHERE z.`is_deleted` = 'N' and z.`site_id` = ch.`site_id` ORDER BY z.`is_active` DESC, z.`id` DESC LIMIT 1) as `site_url`, si.`https`,
       po.`client_id`, cl.`name` as `client_name`, cl.`logo_img` as `client_logo_img`, cl.`guid` as `client_guid`,
       po.`publish_at`, UNIX_TIMESTAMP(po.`publish_at`) as `publish_unix`, po.`expires_at`, UNIX_TIMESTAMP(po.`expires_at`) as `expires_unix`,
       po.`created_at`, UNIX_TIMESTAMP(po.`created_at`) as `created_unix`, po.`created_by`, po.`updated_at`, UNIX_TIMESTAMP(po.`updated_at`) as `updated_unix`,
       ROUND(UNIX_TIMESTAMP(GREATEST(si.`updated_at`, ch.`updated_at`, po.`updated_at`, pa.`updated_at`))) as `post_version`
  FROM `Site` si INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                 INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                 INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                 INNER JOIN `Client` cl ON po.`client_id` = cl.`id`
 WHERE po.`is_deleted` = 'N' and po.`id` = [POST_ID]
 ORDER BY po.`publish_at` DESC, po.`id` DESC;