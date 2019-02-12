INSERT INTO `SiteContact` (`site_id`, `name`, `mail`, `message`)
SELECT tmp.`site_id`, tmp.`name`, tmp.`mail`, tmp.`message`
  FROM (SELECT [SITE_ID] as `site_id`, '[NAME]' as `name`, '[MAIL]' as `mail`, '[MESSAGE]' as `message`,
               (SELECT COUNT(z.`id`) FROM `SiteContact` z WHERE z.`is_deleted` = 'N' and z.`site_id` = [SITE_ID] and z.`hash` = SHA1('[MESSAGE]')) as `exists`) tmp
 WHERE tmp.`exists` = 0
 LIMIT 1;