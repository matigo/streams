UPDATE `Account` a INNER JOIN `Employee` e ON a.`person_id` = e.`person_id`
   SET a.`saml_guid` = '[SAML_GUID]',
       a.`updated_at` = Now()
 WHERE a.`is_deleted` = 'N' and e.`is_deleted` = 'N' and IFNULL(a.`saml_guid`, '') = ''
   and UPPER(e.`cosmos_id`) = UPPER(REPLACE(UPPER('[ACCOUNT_NAME]'), 'U', ''));