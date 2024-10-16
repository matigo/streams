SELECT CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `url`,
       prv.`canonical_url` as `prev_url`, prv.`title` as `prev_title`, prv.`publish_unix` as `prev_unix`, prv.`type` as `prev_type`, prv.`guid` as `prev_guid`,
       rnd.`canonical_url` as `rand_url`, rnd.`title` as `rand_title`, ROUND(UNIX_TIMESTAMP(rnd.`publish_at`)) as `rand_unix`, rnd.`type` as `rand_type`, rnd.`guid` as `rand_guid`,
       fwd.`canonical_url` as `next_url`, fwd.`title` as `next_title`, fwd.`publish_unix` as `next_unix`, fwd.`type` as `next_type`, fwd.`guid` as `next_guid`
  FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                    INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                    INNER JOIN `Post` p ON ch.`id` = p.`channel_id`
               LEFT OUTER JOIN `Post` rnd ON p.`channel_id` = rnd.`channel_id` AND p.`type` = rnd.`type` AND p.`id` <> rnd.`id` AND rnd.`privacy_type` = 'visibility.public' AND rnd.`is_deleted` = 'N' 
               LEFT OUTER JOIN (SELECT z.`channel_id`, z.`canonical_url`, z.`title`, ROUND(UNIX_TIMESTAMP(z.`publish_at`)) as `publish_unix`, z.`type`, z.`guid`
                                  FROM `Post` p LEFT OUTER JOIN `Post` z ON p.`channel_id` = z.`channel_id` AND p.`type` = z.`type` AND p.`publish_at` >= z.`publish_at` 
                                                                        AND p.`id` <> z.`id` AND z.`privacy_type` = 'visibility.public' AND z.`is_deleted` = 'N' 
                                 WHERE p.`is_deleted` = 'N' and p.`guid` = '[POST_GUID]'
                                 ORDER BY z.`publish_at` DESC, z.`id`
                                 LIMIT 1) prv ON p.`channel_id` = prv.`channel_id`
               LEFT OUTER JOIN (SELECT z.`channel_id`, z.`canonical_url`, z.`title`, ROUND(UNIX_TIMESTAMP(z.`publish_at`)) as `publish_unix`, z.`type`, z.`guid`
                                  FROM `Post` p LEFT OUTER JOIN `Post` z ON p.`channel_id` = z.`channel_id` AND p.`type` = z.`type` AND p.`publish_at` <= z.`publish_at` 
                                                                        AND p.`id` <> z.`id` AND z.`privacy_type` = 'visibility.public' AND z.`is_deleted` = 'N'
                                 WHERE p.`is_deleted` = 'N' and p.`guid` = '[POST_GUID]'
                                 ORDER BY z.`publish_at`, z.`id`
                                 LIMIT 1) fwd ON p.`channel_id` = fwd.`channel_id`
 WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and p.`is_deleted` = 'N' and p.`guid` = '[POST_GUID]'
 ORDER BY su.`is_active` DESC, RAND(), RAND(), RAND()
 LIMIT 1;