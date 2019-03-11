INSERT INTO `SiteMeta` (`site_id`, `key`, `value`)
SELECT ch.`site_id`, REPLACE(po.`type`, 'post.', 'has_') as `key`, 'Y' as `value`
  FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
 WHERE po.`is_deleted` = 'N' and po.`id` = [POST_ID]
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N';