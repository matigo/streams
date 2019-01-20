INSERT INTO `PostMention` (`post_id`, `persona_id`)
VALUES [VALUE_LIST]
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                            `updated_at` = Now();