SELECT pm.`post_id`,
       MAX(CASE WHEN pm.`key` = 'episode_file' THEN pm.`value` ELSE NULL END) as `episode_file`,
       MAX(CASE WHEN pm.`key` = 'episode_number' THEN pm.`value` ELSE NULL END) as `episode_number`,
       MAX(CASE WHEN pm.`key` = 'episode_summary' THEN pm.`value` ELSE NULL END) as `episode_summary`,
       MAX(CASE WHEN pm.`key` = 'episode_time' THEN pm.`value` ELSE NULL END) as `episode_time`
  FROM `PostMeta` pm
 WHERE pm.`is_deleted` = 'N' and pm.`post_id` = [POST_ID]
 GROUP BY pm.`post_id`;