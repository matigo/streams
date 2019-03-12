SELECT su.`site_id`, si.`guid` as `site_guid`, su.`id` as `url_id`, su.`url` as `site_url`,
       si.`name` as `site_name`, si.`description`, si.`keywords`, si.`https`, si.`theme`, si.`is_default`,
       IFNULL((SELECT z.`value` FROM `SiteMeta` z
                WHERE z.`is_deleted` = 'N' and z.`key` = 'summary' and z.`site_id` = si.`id`), '') as `summary`,
       IFNULL((SELECT IFNULL(z.`title`, z.`value`) FROM `Post` z
                WHERE z.`is_deleted` = 'N' and z.`channel_id` = ch.`id` and z.`canonical_url` = '[REQ_URI]'
                ORDER BY z.`id` LIMIT 1), 'website') as `page_title`,
       IFNULL((SELECT z.`type` FROM `Post` z
                WHERE z.`is_deleted` = 'N' and z.`channel_id` = ch.`id` and z.`canonical_url` = '[REQ_URI]'
                ORDER BY z.`id` LIMIT 1), 'website') as `page_type`,
       ch.`name` as `channel_name`, ch.`guid` as `channel_guid`, ch.`id` as `channel_id`, ch.`privacy_type` as `channel_privacy`,
       (SELECT z.`guid` FROM `Client` z
         WHERE z.`is_deleted` = 'N' and z.`is_active` = 'Y' and z.`name` = 'Default Client') as `client_guid`,
       IFNULL((SELECT z.`value` FROM `SiteMeta` z
                WHERE z.`is_deleted` = 'N' and z.`key` = 'show_geo' and z.`site_id` = si.`id`), 'N') as `show_geo`,
       IFNULL((SELECT z.`value` FROM `SiteMeta` z
                WHERE z.`is_deleted` = 'N' and z.`key` = 'show_note' and z.`site_id` = si.`id`), 'Y') as `show_note`,
       IFNULL((SELECT z.`value` FROM `SiteMeta` z
                WHERE z.`is_deleted` = 'N' and z.`key` = 'show_article' and z.`site_id` = si.`id`), 'Y') as `show_article`,
       IFNULL((SELECT z.`value` FROM `SiteMeta` z
                WHERE z.`is_deleted` = 'N' and z.`key` = 'show_bookmark' and z.`site_id` = si.`id`), 'Y') as `show_bookmark`,
       IFNULL((SELECT z.`value` FROM `SiteMeta` z
                WHERE z.`is_deleted` = 'N' and z.`key` = 'show_quotation' and z.`site_id` = si.`id`), 'Y') as `show_quotation`,
       si.`version` as `site_version`, si.`updated_at` as `site_updated_at`,
       CASE WHEN si.`account_id` = [ACCOUNT_ID] THEN 'Y' ELSE 'N' END as `can_edit`,
       CASE WHEN lu.`id` <> su.`id` THEN 'Y' ELSE 'N' END as `do_redirect`, 0 as `sort_order`
  FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                    INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
                    INNER JOIN `SiteUrl` lu ON su.`site_id` = lu.`site_id`
 WHERE si.`is_deleted` = 'N' and su.`is_deleted` = 'N' and su.`is_active` = 'Y' and lu.`url` = '[SITE_URL]'
 UNION ALL
SELECT su.`site_id`, si.`guid` as `site_guid`, su.`id` as `url_id`, su.`url` as `site_url`,
       si.`name` as `site_name`, si.`description`, si.`keywords`, si.`https`, si.`theme`, si.`is_default`,
       '' as `summary`,
       '' as `page_title`, 'website' as `page_type`,
       ch.`name` as `channel_name`, ch.`guid` as `channel_guid`, ch.`id` as `channel_id`, ch.`privacy_type` as `channel_privacy`,
       '' as `client_guid`, 'N' as `show_geo`, 'N' as `show_note`, 'Y' as `show_article`, 'N' as `show_bookmark`, 'N' as `show_quotation`,
       '0' as `site_version`, Now() as `site_updated_at`,
       'N' as `can_edit`, 'Y' as `do_redirect`, 1 as `sort_order`
  FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                    INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
 WHERE si.`is_deleted` = 'N' and su.`is_deleted` = 'N' and si.`is_default` = 'Y' and su.`is_active` = 'Y'
 ORDER BY `sort_order`, `url_id` DESC
 LIMIT 1;