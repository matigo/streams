/* Set the Public Bio for the Persona */
INSERT INTO `PersonaMeta` (`persona_id`, `key`, `value`, `is_deleted`)
SELECT pa.`id` as `persona_id`, 'persona.bio' as `key`, LEFT('[PERSONA_BIO]', 2048) as `value`,
       CASE WHEN '[PERSONA_BIO]' = '' THEN 'Y' ELSE 'N' END as `is_deleted`
  FROM `Account` a INNER JOIN `Persona` pa ON a.`id` = pa.`account_id`
 WHERE pa.`is_deleted` = 'N' and a.`is_deleted` = 'N' and pa.`guid` = '[PERSONA_GUID]' and a.`id` = [ACCOUNT_ID]
    ON DUPLICATE KEY UPDATE `value` = LEFT('[PERSONA_BIO]', 2048),
                            `is_deleted` = CASE WHEN '[PERSONA_BIO]' = '' THEN 'Y' ELSE 'N' END,
                            `updated_at` = Now();