INSERT INTO `PostMeta` (`post_id`, `key`, `value`)
SELECT [POST_ID] as `post_id`, LOWER('[KEY]') as `key`, '[VALUE]' as `value`
    ON DUPLICATE KEY UPDATE `value` = '[VALUE]',
                            `is_deleted` = 'N',
                            `updated_at` = Now();