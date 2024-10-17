SELECT CASE WHEN pa.`account_id` = [ACCOUNT_ID] THEN 'Y'
            WHEN po.`privacy_type` = 'visibility.public' AND
                 DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d %H:%i:00') BETWEEN DATE_FORMAT(po.`publish_at`, '%Y-%m-%d %H:%i:00') 
                                                                         AND DATE_FORMAT(IFNULL(po.`expires_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR)), '%Y-%m-%d %H:%i:00')
                 THEN 'Y'
            ELSE 'N' END as `is_visible`,
       po.`id` as `post_id`, po.`guid` as `post_guid`, po.`type` as `post_type`, po.`privacy_type`,
       pa.`id` as `persona_id`, pa.`guid` as `persona_guid`,
       pa.`display_name` as `author_name`, pa.`first_name`, pa.`last_name`, pa.`avatar_img`,
       po.`title`, po.`value` as `content_text`, ROUND(UNIX_TIMESTAMP(po.`publish_at`)) as `publish_unix`, ROUND(UNIX_TIMESTAMP(po.`expires_at`)) as `expires_unix`,
       po.`canonical_url`, po.`slug`, po.`hash`,
       (SELECT GROUP_CONCAT(CONCAT(LOWER(`key`), '|', `value`)) as `tag` FROM `PostTags` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`) as `post_tags`,
       (SELECT CASE WHEN COUNT(z.`key`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`) as `has_meta`,
       ROUND(UNIX_TIMESTAMP(po.`created_at`)) as `created_unix`, ROUND(UNIX_TIMESTAMP(po.`updated_at`)) as `updated_unix`
  FROM `Persona` pa INNER JOIN `Post` po ON pa.`id` = po.`persona_id`
 WHERE pa.`is_deleted` = 'N' and po.`is_deleted` = 'N' and po.`guid` = '[POST_GUID]'
 ORDER BY po.`id` LIMIT 1;