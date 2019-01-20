UPDATE `Tokens`
   SET `is_deleted` = 'Y',
       `updated_at` = Now()
 WHERE `is_deleted` = 'N' and `id` = [TOKEN_ID]
   and `guid` = '[TOKEN_GUID]';