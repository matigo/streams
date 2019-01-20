INSERT INTO `Tokens` (`guid`, `account_id`, `is_deleted`, `created_at`)
SELECT uuid(), [ACCOUNT_ID], 'Y', Now();