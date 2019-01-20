INSERT INTO `PostTags` (`post_id`, `key`, `value`)
VALUES [VALUE_LIST]
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                            `updated_at` = Now();