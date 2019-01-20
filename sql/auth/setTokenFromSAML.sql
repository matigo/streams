INSERT INTO `Tokens` (`guid`, `account_id`, `saml_identity`, `saml_session`, `saml_guid`, `created_at`)
SELECT sha1(CONCAT(Now(), a.`id`, GROUP_CONCAT(r.`scope`))), a.`id`, '[SAML_IDENTITY]', '[SAML_SESSION]', '[SAML_GUID]', Now()
  FROM `Account` a, `AccountRoles` r
 WHERE r.`is_deleted` = 'N' and r.`account_id` = a.`id`
   and a.`is_deleted` = 'N' and a.`type` IN ('account.admin', 'account.normal', 'account.student', 'account.guest')
   and IFNULL(r.`expires_at`, DATE_ADD(Now(), INTERVAL 5 MINUTE)) > Now()
   and (a.`email` = '[ACCOUNT_NAME]' OR a.`saml_guid` = '[SAML_GUID]')
 GROUP BY a.`id`
 ORDER BY a.`id`
 LIMIT 1;