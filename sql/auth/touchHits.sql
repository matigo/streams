INSERT INTO `ApiHits` (`from_ip`, `token_id`, `period`, `hits`, `created_at`)
SELECT '[VISIT_IP]:touch', 0, DATE_FORMAT(Now(), '%Y-%m-%d %H:00:00'), 1, Now()
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                            `updated_at` = Now();