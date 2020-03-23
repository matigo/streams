SELECT tmp.`hits`, tmp.`last_at`, IFNULL(pm.`value`, po.`title`) as `title`, po.`canonical_url`, po.`publish_at`, po.`privacy_type`, po.`type`
  FROM `Post` po INNER JOIN (SELECT us.`request_uri`, ch.`id` as `channel_id`, COUNT(us.`id`) as `hits`, MAX(us.`event_at`) as `last_at`
                               FROM `UsageStats` us INNER JOIN `Channel` ch ON us.`site_id` = ch.`site_id`
                                                    INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                              WHERE ch.`is_deleted` = 'N' and si.`is_deleted` = 'N' and si.`version` <> ''
                                and us.`event_on` >= DATE_FORMAT(DATE_SUB(Now(), INTERVAL 2 WEEK), '%Y-%m-%d') and us.`site_id` = [SITE_ID]
                                and us.`http_code` BETWEEN 100 AND 299
                              GROUP BY us.`request_uri`, ch.`id`
                              ORDER BY `hits` DESC LIMIT 250) tmp ON po.`channel_id` = tmp.`channel_id` AND po.`canonical_url` = tmp.`request_uri`
            LEFT OUTER JOIN `PostMeta` pm ON po.`id` = pm.`post_id` AND pm.`is_deleted` = 'N' AND pm.`key` = 'source_title'
 WHERE po.`is_deleted` = 'N' and po.`privacy_type` = 'visibility.public' and po.`type` IN ('post.article', 'post.bookmark', 'post.quotation')
 ORDER BY tmp.`hits` DESC, tmp.`last_at`
 LIMIT [COUNT];