UPDATE `Account` a INNER JOIN `Persona` p ON a.`id` = p.`account_id`
   SET a.`email` = '[PREF_MAIL]',
       p.`email` = '[PREF_MAIL]',
       a.`display_name` = '[PREF_NAME]',
       p.`display_name` = '[PREF_NAME]',
       a.`language_code` = '[PREF_LANG]',
       a.`timezone` = '[PREF_TIME]',
       p.`updated_at` = Now(),
       a.`updated_at` = Now()
 WHERE a.`is_deleted` = 'N' and p.`is_deleted` = 'N'
   and a.`id` = [ACCOUNT_ID] and p.`guid` = '[PERSONA_GUID]';