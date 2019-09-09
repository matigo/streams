SELECT tmp.`url`, CONCAT(CASE WHEN tmp.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', tmp.`url`) as `site_url`,
       tmp.`account_mail`, tmp.`account_name`,
       tmp.`name`, tmp.`mail`, tmp.`subject`, tmp.`message`, tmp.`guid`,
       tmp.`created_at`, tmp.`updated_at`
  FROM (
    SELECT si.`https`, su.`url`, si.`name` as `site_name`,
           sc.`name`, sc.`mail`, sc.`subject`, sc.`message`, sc.`guid`,
           acct.`email` as `account_mail`, acct.`display_name` as `account_name`,
           IFNULL(am.`value`, 'N') as `can_mail`, sc.`is_read`, sc.`is_mailed`,
           CASE WHEN sc.`mail` IN (SELECT DISTINCT `email` FROM `Account` UNION ALL
                                   SELECT DISTINCT `email` FROM `Persona`) THEN 'N'
                WHEN sc.`message` LIKE '%viagra%' THEN 'Y'
                WHEN sc.`message` LIKE '%cialis%' THEN 'Y'
                WHEN sc.`message` LIKE '%brands%' THEN 'Y'
                WHEN sc.`message` LIKE '%agency%' THEN 'Y'
                WHEN sc.`message` LIKE '%afiliate%' THEN 'Y'
                WHEN sc.`message` LIKE '%about.me%' THEN 'Y'
                WHEN sc.`message` LIKE '%украине%' THEN 'Y'
                WHEN sc.`message` LIKE '%сайт%' THEN 'Y'
                WHEN sc.`message` LIKE '%.ru%' THEN 'Y'
                WHEN sc.`message` LIKE '%.tk/%' THEN 'Y'
                WHEN sc.`message` LIKE '%http%' THEN 'Y'
                WHEN sc.`message` LIKE '%f r e e%' THEN 'Y'
                WHEN sc.`message` LIKE '%voip%' THEN 'Y'
                WHEN sc.`message` LIKE '%to advertise%' THEN 'Y'
                WHEN sc.`message` LIKE '%you have been hacked%' THEN 'Y'
                WHEN sc.`message` LIKE '%?asturbat?on%' THEN 'Y'
                WHEN sc.`message` LIKE '%investment%' THEN 'Y'
                WHEN sc.`message` LIKE '%v?deo%' THEN 'Y'
                WHEN sc.`message` lIKE '%кино%' THEN 'Y'
                WHEN sc.`message` LIKE '%BTC%' THEN 'Y'
                WHEN sc.`message` LIKE '%SEO%' THEN 'Y'
                WHEN sc.`message` LIKE '%рублях%' THEN 'Y'
                WHEN sc.`message` LIKE '%원%' THEN 'Y'
                WHEN sc.`mail` IN ('plan.b.fundingoptions@gmail.com', 'melody_fan@gmail.com') THEN 'Y'
                WHEN sc.`mail` LIKE '%investment%' THEN 'Y'
                WHEN sc.`mail` LIKE '%@alliedconsults.com' THEN 'Y'
                WHEN sc.`mail` LIKE '%@alychidesigns.com' THEN 'Y'
                WHEN sc.`mail` LIKE 'raphae%@gmail.com' THEN 'Y'
                WHEN sc.`mail` LIKE '%@qmails.pro' THEN 'Y'
                WHEN sc.`mail` LIKE '%@qmails.co' THEN 'Y'
                WHEN sc.`mail` LIKE '%@yandex.com' THEN 'Y'
                WHEN sc.`mail` LIKE '%@bigmir.net' THEN 'Y'
                WHEN sc.`mail` LIKE '%.email' THEN 'Y'
                WHEN sc.`mail` LIKE '%.ru' THEN 'Y'
                WHEN tmp.`hits` > 1 THEN 'Y'
                ELSE 'N' END as `is_spam`,
           sc.`created_at`, sc.`updated_at`
      FROM `SiteContact` sc INNER JOIN `SiteUrl` su ON sc.`site_id` = su.`site_id`
                            INNER JOIN `Site` si ON su.`site_id` = si.`id`
                            INNER JOIN `Account` acct ON si.`account_id` = acct.`id`
                       LEFT OUTER JOIN `AccountMeta` am ON acct.`id` = am.`account_id` AND am.`is_deleted` = 'N' AND am.`key` = 'preference.contact.mail'
                       LEFT OUTER JOIN (SELECT zc.`hash`, COUNT(zc.`id`) as `hits`
                                           FROM `SiteContact` zc
                                          GROUP BY zc.`hash` ORDER BY `hits` DESC) tmp ON sc.`hash` = tmp.`hash`
     WHERE sc.`is_deleted` = 'N' and su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and acct.`is_deleted` = 'N'
       and su.`is_active` = 'Y' and si.`id` = [SITE_ID]) tmp
 WHERE tmp.`is_read` = 'N' and tmp.`is_spam` = 'N' and tmp.`is_mailed` = 'N' and tmp.`can_mail` = 'Y'
ORDER BY tmp.`created_at`
LIMIT 5;