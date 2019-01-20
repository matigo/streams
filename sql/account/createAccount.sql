INSERT INTO `Account` (`login`, `email`, `password`, `person_id`, `display_name`, `avatar_url`, `saml_guid`, `type`)
SELECT '[USERNAME]', '[MAILADDR]', sha2(CONCAT('[SHA_SALT]', '[USERPASS]'), 512), p.`id`, '[DISPNAME]', 'default.png', '[SAMLGUID]', 'account.normal'
  FROM `Person` p
 WHERE p.`is_deleted` = 'N' and p.`id` = [PERSONID]
   and 0 = (SELECT COUNT(a.`id`) FROM `Account` a
              WHERE a.`is_deleted` = 'N' and (a.`login` = '[USERNAME]' OR a.`saml_guid` = '[SAML_CHK]' ))
 LIMIT 1;