INSERT INTO `SiteContact` (`site_id`, `name`, `subject`, `mail`, `message`)
SELECT tmp.`site_id`, tmp.`name`, tmp.`subject`, tmp.`mail`, tmp.`message`
  FROM (SELECT [SITE_ID] as `site_id`, LEFT('[NAME]', 80) as `name`,
               CASE WHEN '[SUBJECT]' <> '' THEN LEFT('[SUBJECT]', 160) ELSE NULL END as `subject`, LEFT('[MAIL]', 160) as `mail`, '[MESSAGE]' as `message`,
               (SELECT COUNT(z.`id`) FROM `SiteContact` z WHERE z.`is_deleted` = 'N' and z.`site_id` = [SITE_ID] and z.`hash` = SHA1('[MESSAGE]')) as `exists`) tmp
 WHERE tmp.`exists` = 0
 LIMIT 1;