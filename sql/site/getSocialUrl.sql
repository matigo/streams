SELECT CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `social_url`
  FROM `Site` si INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
 WHERE si.`is_deleted` = 'N' and su.`is_deleted` = 'N' and su.`is_active` = 'Y'
   and si.`theme` = 'social'
 ORDER BY si.`id`
 LIMIT 1;