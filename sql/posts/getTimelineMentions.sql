SELECT pa.`name` as `persona_name`, pa.`display_name`, pa.`guid` as `persona_guid`,
       CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, '/avatars/', pa.`avatar_img`) as `avatar_url`,
       CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, '/', pa.`guid`, '/profile') as `profile_url`,
       po.`id` as `post_id`, po.`thread_id`, po.`parent_id`, po.`title`, po.`value`,
       (SELECT CASE WHEN COUNT(z.`key`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id` LIMIT 1) as `has_meta`,
       (SELECT GROUP_CONCAT(z.`value`) as `value` FROM `PostTags` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`) as `post_tags`,
       (SELECT CASE WHEN COUNT(z.`persona_id`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMention` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`) as `has_mentions`,
       CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, po.`canonical_url`) as `canonical_url`,
       CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `site_url`,
       po.`reply_to`, po.`type`,
       po.`guid` as `post_guid`, po.`privacy_type`,
       po.`publish_at`, po.`expires_at`, po.`updated_at`,
       CASE WHEN pa.`account_id` = [ACCOUNT_ID] THEN 'Y' ELSE 'N' END as `is_you`,
       CASE WHEN po.`expires_at` IS NULL THEN 'Y'
            WHEN po.`expires_at` < Now() THEN 'N'
            ELSE 'Y' END as `is_visible`
  FROM `Persona` za INNER JOIN `PostMention` pm ON za.`id` = pm.`persona_id`
                    INNER JOIN `Post` po ON pm.`post_id` = po.`id`
                    INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                    INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                    INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                    INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
 WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N' 
   and za.`is_deleted` = 'N' and pm.`is_deleted` = 'N'
   and ch.`privacy_type` = 'visibility.public' and ch.`type` = 'channel.site' and su.`is_active` = 'Y' 
   and po.`type` IN ([POST_TYPES]) and za.`account_id` = [ACCOUNT_ID]
   and 'Y' = CASE WHEN po.`privacy_type` = 'visibility.public' THEN 'Y'
                  WHEN pa.`account_id` = [ACCOUNT_ID] THEN 'Y'
                  ELSE 'N' END
   and po.`publish_at` BETWEEN FROM_UNIXTIME([SINCE_UNIX]) AND 
                               CASE WHEN [UNTIL_UNIX] = 0 THEN Now() ELSE FROM_UNIXTIME([UNTIL_UNIX]) END
 ORDER BY CASE WHEN [SINCE_UNIX] = 0 THEN 1 ELSE po.`publish_at` END, po.`publish_at` DESC
 LIMIT 0, [COUNT];