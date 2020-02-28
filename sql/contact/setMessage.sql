INSERT INTO `SiteContact` (`site_id`, `name`, `subject`, `mail`, `message`, `nonce_match`, `nonce`, `agent`, `from_ip`)
SELECT tmp.`site_id`, tmp.`name`, tmp.`subject`, tmp.`mail`, tmp.`message`,
       tmp.`nonce_match`, tmp.`nonce`, tmp.`agent`, tmp.`from_ip`
  FROM (SELECT si.`id` as `site_id`, LEFT('[NAME]', 80) as `name`,
               CASE WHEN '[SUBJECT]' <> '' THEN LEFT('[SUBJECT]', 160) ELSE NULL END as `subject`, LEFT('[MAIL]', 160) as `mail`, '[MESSAGE]' as `message`,
               CASE WHEN '[NONCE_MATCH]' IN ('N','Y') THEN '[NONCE_MATCH]' ELSE 'N' END as `nonce_match`, LEFT('[NONCE]', 64) as `nonce`,
               LEFT('[AGENT]', 2048) as `agent`, LEFT('[FROM_IP]', 64) as `from_ip`,
               (SELECT COUNT(z.`id`) FROM `SiteContact` z WHERE z.`is_deleted` = 'N' and z.`site_id` = si.`id` and z.`hash` = SHA1('[MESSAGE]')) as `exists`
          FROM `Account` acct INNER JOIN `Site` si ON acct.`id` = si.`account_id`
         WHERE acct.`is_deleted` = 'N' and si.`is_deleted` = 'N' and si.`id` = [SITE_ID]
         LIMIT 1) tmp
 WHERE tmp.`exists` = 0
 LIMIT 1;