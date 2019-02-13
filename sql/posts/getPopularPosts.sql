SELECT tmp.`hits`, tmp.`last_at`, po.`title`, po.`canonical_url`, po.`publish_at`, po.`privacy_type`
  FROM `Post` po INNER JOIN (SELECT us.`request_uri`, ch.`id` as `channel_id`, COUNT(us.`id`) as `hits`, MAX(us.`event_at`) as `last_at`
                               FROM `UsageStats` us INNER JOIN `Channel` ch ON us.`site_id` = ch.`site_id`
                                                    INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                              WHERE ch.`is_deleted` = 'N' and si.`is_deleted` = 'N' and si.`version` <> ''
                                and us.`event_on` >= DATE_FORMAT(DATE_SUB(Now(), INTERVAL 2 WEEK), '%Y-%m-%d') and us.`site_id` = [SITE_ID]
                              GROUP BY us.`request_uri`, ch.`id`
                              ORDER BY `hits` DESC LIMIT 250) tmp ON po.`channel_id` = tmp.`channel_id` AND po.`canonical_url` = tmp.`request_uri`
 WHERE po.`is_deleted` = 'N' and po.`privacy_type` = 'visibility.public' and po.`type` NOT IN ('post.note')
 ORDER BY tmp.`hits` DESC, tmp.`last_at`
 LIMIT [COUNT];