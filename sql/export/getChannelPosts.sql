SELECT pa.`id` as `author_id`, pa.`email`, acct.`email` as `account_email`,
       po.`id` as `post_id`, po.`title`, po.`value`, po.`canonical_url`, po.`guid`, po.`privacy_type`, po.`publish_at`, po.`expires_at`,
       po.`type`, po.`hash`, po.`created_at`, po.`updated_at`,
       (SELECT z.`value` FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`key` = 'post_summary' and z.`post_id` = po.`id` LIMIT 1) as `excerpt`,
       (SELECT GROUP_CONCAT(CONCAT('{"key": "', z.`key`, '","name": "', z.`value`, '"}')) as `jout`
          FROM `PostTags` z
         WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`
         ORDER BY z.`key`) as `post_tags`
  FROM `Persona` pp INNER JOIN `ChannelAuthor` ca ON pp.`id` = ca.`persona_id`
                    INNER JOIN `Channel` ch ON ca.`channel_id` = ch.`id`
                    INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                    INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                    INNER JOIN `Account` acct ON pa.`account_id` = acct.`id`
 WHERE acct.`is_deleted` = 'N' and ca.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and pp.`is_deleted` = 'N'
   and IFNULL(po.`expires_at`, Now()) >= Now() and po.`type` IN ([POST_TYPES])
   and ca.`can_write` = 'Y' and ch.`guid` = '[CHANNEL_GUID]'
   and pp.`account_id` = [ACCOUNT_ID]
 ORDER BY po.`publish_at`
 LIMIT [START_POS], 5000;