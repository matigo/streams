SELECT su.`site_id` FROM `SiteUrl` su
 WHERE su.`is_deleted` = 'N' and su.`is_active` = 'Y' and su.`url` = '[SITE_URL]'
 LIMIT 1;