SELECT t.`id` as `token_id`, t.`guid`, t.`account_id` FROM `Tokens` t
 WHERE t.`is_deleted` = 'N' and t.`updated_at` >= DATE_SUB(Now(), INTERVAL [LIFESPAN] DAY)
   and t.`id` = [TOKEN_ID]
 LIMIT 1;