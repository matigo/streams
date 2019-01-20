SELECT t.`account_id`, t.`is_deleted`, t.`created_at`, t.`updated_at`
  FROM `Tokens` t
 WHERE t.`updated_at` >= DATE_SUB(Now(), INTERVAL [LIFESPAN] DAY)
   and t.`id` = [TOKEN_ID] and t.`guid` = '[TOKEN_GUID]'
 LIMIT 1;