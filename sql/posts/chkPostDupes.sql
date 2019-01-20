UPDATE `Post` p INNER JOIN (SELECT SHA1(CONCAT(p.`persona_id`, IFNULL(p.`client_id`, 0), IFNULL(p.`thread_id`, 0), IFNULL(p.`parent_id`, 0), p.`value`, 
                                               IFNULL(p.`reply_to`, ''), p.`channel_id`, p.`privacy_type`, p.`created_by`, p.`updated_by`)) as `sha1`,
                                   COUNT(p.`id`) as `posts`, MAX(p.`id`) as `max_id`, GROUP_CONCAT(p.`id`) as `post_ids`
                              FROM `Post` p
                             WHERE p.`is_deleted` = 'N' and p.`created_by` = [ACCOUNT_ID] and p.`created_at` >= DATE_SUB(Now(), INTERVAL 15 MINUTE)
                             GROUP BY `sha1`) tmp ON FIND_IN_SET(p.`id`, tmp.`post_ids`)
   SET p.`is_deleted` = CASE WHEN p.`id` = tmp.`max_id` THEN 'N' ELSE 'Y' END
 WHERE tmp.`posts` > 1;