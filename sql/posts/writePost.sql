INSERT INTO `Post` (`id`, `persona_id`, `client_id`, `thread_id`, `parent_id`, 
                    `title`, `value`,
                    `canonical_url`, `reply_to`, `channel_id`,
                    `slug`, `type`, `privacy_type`,
                    `publish_at`, `expires_at`, `created_by`, `updated_by`)
SELECT CASE WHEN [POST_ID] > 0 THEN [POST_ID] ELSE NULL END as `id`,  p.`id` as `persona_id`, t.`client_id`,
       CASE WHEN [THREAD_ID] > 0 THEN [THREAD_ID] ELSE NULL END as `thread_id`,
       CASE WHEN [PARENT_ID] > 0 THEN [PARENT_ID] ELSE NULL END as `parent_id`,
       CASE WHEN '[TITLE]' <> '' THEN LEFT('[TITLE]', 512) ELSE NULL END as `title`,
       '[VALUE]' as `value`, 
       CASE WHEN '[CANON_URL]' <> '' THEN LEFT('[CANON_URL]', 512) ELSE NULL END as `canonical_url`, 
       CASE WHEN '[REPLY_TO]'  <> '' THEN LEFT('[REPLY_TO]', 512) ELSE NULL END as `reply_to`, ca.`channel_id`,
       CASE WHEN '[POST_SLUG]' <> '' THEN LEFT('[POST_SLUG]', 255) ELSE NULL END as `slug`,
       '[POST_TYPE]' as `type`, CASE WHEN '[PRIVACY]' <> '' THEN '[PRIVACY]' ELSE NULL END as `privacy_type`,
       CASE WHEN '[PUBLISH_AT]' <> '' THEN DATE_FORMAT('[PUBLISH_AT]', '%Y-%m-%d %H:%i:%s') ELSE Now() END as `publish_at`,
       CASE WHEN '[EXPIRES_AT]' > DATE_FORMAT(Now(), '%Y-%m-%d %H:%i:%s') THEN '[EXPIRES_AT]' ELSE NULL END as `expires_at`,
       t.`account_id` as `created_by`, t.`account_id` as `updated_by`
  FROM `Tokens` t INNER JOIN `Account` a ON t.`account_id` = a.`id`
                  INNER JOIN `Persona` p ON a.`id` = p.`account_id`
                  INNER JOIN `ChannelAuthor` ca ON p.`id` = ca.`persona_id`
                  INNER JOIN `Channel` c ON ca.`channel_id` = c.`id`
 WHERE t.`is_deleted` = 'N' and p.`is_deleted` = 'N' and a.`is_deleted` = 'N'
   and ca.`is_deleted` = 'N' and c.`is_deleted` = 'N' and ca.`can_write` = 'Y'
   and t.`updated_at` >= DATE_SUB(Now(), INTERVAL 30 DAY) and c.`guid` = '[CHANNEL_GUID]'
   and p.`guid` = '[PERSONA_GUID]' and t.`guid` = '[TOKEN_GUID]' and t.`id` = [TOKEN_ID]
    ON DUPLICATE KEY UPDATE `persona_id` = p.`id`,
                            `thread_id` = CASE WHEN [THREAD_ID] > 0 THEN [THREAD_ID] ELSE NULL END,
                            `parent_id` = CASE WHEN [PARENT_ID] > 0 THEN [PARENT_ID] ELSE NULL END,
                            `channel_id` = ca.`channel_id`,
                            `title` = CASE WHEN '[TITLE]' <> '' THEN LEFT('[TITLE]', 512) ELSE NULL END,
                            `value` = '[VALUE]',
                            `canonical_url` = CASE WHEN '' <> '' THEN LEFT('', 512) ELSE Post.`canonical_url` END,
                            `reply_to` = CASE WHEN '[REPLY_TO]' <> '' THEN LEFT('[REPLY_TO]', 512) ELSE NULL END,
                            `slug` = CASE WHEN '' <> '' THEN LEFT('', 255) ELSE Post.`slug` END,
                            `type` = '[POST_TYPE]',
                            `privacy_type` = CASE WHEN '[PRIVACY]' <> '' THEN '[PRIVACY]' ELSE NULL END,
                            `publish_at` = CASE WHEN '[PUBLISH_AT]' <> '' THEN DATE_FORMAT('[PUBLISH_AT]', '%Y-%m-%d %H:%i:%s') ELSE Post.`publish_at` END,
                            `expires_at` = CASE WHEN '[EXPIRES_AT]' > DATE_FORMAT(Now(), '%Y-%m-%d %H:%i:%s') THEN '[EXPIRES_AT]' ELSE Post.`expires_at` END,
                            `updated_by` = t.`account_id`,
                            `updated_at` = Now();