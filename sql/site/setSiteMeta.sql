INSERT INTO `SiteMeta` (`site_id`, `key`, `value`, `is_deleted`)
SELECT [SITE_ID], '[METAKEY]', '[METAVAL]', CASE WHEN '[METAVAL]' = '' THEN 'Y' ELSE 'N' END
    ON DUPLICATE KEY UPDATE `value` = '[METAVAL]',
                            `is_deleted` = CASE WHEN '[METAVAL]' = '' THEN 'Y' ELSE 'N' END,
                            `updated_at` = Now();