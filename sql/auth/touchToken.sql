UPDATE `Tokens` t
   SET t.`updated_at` = Now()
 WHERE t.`is_deleted` = 'N' and t.`updated_at` >= DATE_SUB(Now(), INTERVAL [LIFESPAN] DAY)
   and t.`id` = [TOKEN_ID];