SELECT CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `default_url`
  FROM `Site` si INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
 WHERE si.`is_deleted` = 'N' and su.`is_deleted` = 'N' and su.`is_active` = 'Y'
   and si.`is_default` = 'Y'
 ORDER BY si.`id`
 LIMIT 1;