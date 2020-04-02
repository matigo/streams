SELECT si.`id` as `site_id`,
       MAX(CASE WHEN sm.`key` = 'site.cover-img' THEN sm.`value` ELSE NULL END) as `cover`,
       MAX(CASE WHEN sm.`key` = 'site.explicit' THEN sm.`value` ELSE NULL END) as `explicit`,
       IFNULL(MAX(CASE WHEN sm.`key` = 'site.rss-summary' THEN sm.`value` ELSE NULL END), si.`description`) as `summary`,
       IFNULL(MAX(CASE WHEN sm.`key` = 'site.rss-license' THEN sm.`value` ELSE NULL END),
              MAX(CASE WHEN sm.`key` = 'site.license' THEN sm.`value` ELSE NULL END)) as `license`,
       MAX(CASE WHEN sm.`key` = 'site.rss-author' THEN sm.`value` ELSE NULL END) as `author`,
       MAX(CASE WHEN sm.`key` = 'site.rss-category1' THEN sm.`value` ELSE NULL END) as `category1`,
       MAX(CASE WHEN sm.`key` = 'site.rss-category1sub' THEN sm.`value` ELSE NULL END) as `category1sub`,
       MAX(CASE WHEN sm.`key` = 'site.rss-category2' THEN sm.`value` ELSE NULL END) as `category2`,
       MAX(CASE WHEN sm.`key` = 'site.rss-category2sub' THEN sm.`value` ELSE NULL END) as `category2sub`,
       MAX(CASE WHEN sm.`key` = 'site.rss-category3' THEN sm.`value` ELSE NULL END) as `category3`,
       MAX(CASE WHEN sm.`key` = 'site.rss-category3sub' THEN sm.`value` ELSE NULL END) as `category3sub`,
       MAX(CASE WHEN sm.`key` = 'site.rss-items' THEN sm.`value` ELSE NULL END) as `rss-items`
  FROM `Site` si LEFT OUTER JOIN `SiteMeta` sm ON si.`id` = sm.`site_id` AND sm.`is_deleted` = 'N'
 WHERE si.`is_deleted` = 'N' and si.`id` = [SITE_ID]
 GROUP BY si.`id`;