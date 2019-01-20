SELECT su.`site_id`, su.`url` as `orig_url`, st.`url` as `live_url`
  FROM `Site` si INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
                 INNER JOIN `SiteUrl` st ON si.`id` = st.`site_id`
 WHERE st.`is_deleted` = 'N' and su.`is_deleted` = 'N' and si.`is_deleted` = 'N'
   and st.`is_active` = 'Y'
 ORDER BY su.`url`;