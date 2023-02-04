SELECT su.`site_id`
  FROM `Site` si INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
 WHERE si.`is_deleted` = 'N' and su.`is_deleted` = 'N' and su.`is_active` = 'Y' and su.`url` = LOWER('[SITE_URL]')
 ORDER BY su.`updated_at` DESC
 LIMIT 1;