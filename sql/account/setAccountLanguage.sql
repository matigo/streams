UPDATE `Account` a INNER JOIN `Tokens` t ON a.`id` = t.`account_id`
   SET a.`language_code` = '[LANG_CD]'
 WHERE t.`is_deleted` = 'N' and t.`guid` = '[TOKEN_GUID]' and t.`id` = [TOKEN_ID]
   and a.`id` = [ACCOUNT_ID];