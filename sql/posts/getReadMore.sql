SELECT CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `url`,
       prv.`canonical_url` as `prev_url`, prv.`title` as `prev_title`, prv.`publish_unix` as `prev_unix`, prv.`type` as `prev_type`, prv.`guid` as `prev_guid`,
       rnd.`canonical_url` as `rand_url`, rnd.`title` as `rand_title`, rnd.`publish_unix` as `rand_unix`, rnd.`type` as `rand_type`, rnd.`guid` as `rand_guid`,
       ynd.`canonical_url` as `yand_url`, ynd.`title` as `yand_title`, ynd.`publish_unix` as `yand_unix`, ynd.`type` as `yand_type`, ynd.`guid` as `yand_guid`,
       znd.`canonical_url` as `zand_url`, znd.`title` as `zand_title`, znd.`publish_unix` as `zand_unix`, znd.`type` as `zand_type`, znd.`guid` as `zand_guid`,
       fwd.`canonical_url` as `next_url`, fwd.`title` as `next_title`, fwd.`publish_unix` as `next_unix`, fwd.`type` as `next_type`, fwd.`guid` as `next_guid`
  FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                    INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                    INNER JOIN `Post` p ON ch.`id` = p.`channel_id`
               LEFT OUTER JOIN (SELECT z.`channel_id`, z.`canonical_url`, z.`title`, ROUND(UNIX_TIMESTAMP(z.`publish_at`)) as `publish_unix`, z.`type`, z.`guid`
                                  FROM `Post` p LEFT OUTER JOIN `Post` z ON p.`channel_id` = z.`channel_id` AND p.`type` = z.`type` AND p.`publish_at` >= z.`publish_at`
                                                                        AND CURRENT_TIMESTAMP BETWEEN z.`publish_at` AND IFNULL(z.`expires_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 MINUTE))
                                                                        AND p.`id` <> z.`id` AND z.`privacy_type` = 'visibility.public' AND z.`is_deleted` = 'N'
                                 WHERE p.`is_deleted` = 'N' and p.`guid` = '[POST_GUID]'
                                 ORDER BY z.`publish_at` DESC, z.`id`
                                 LIMIT 1) prv ON p.`channel_id` = prv.`channel_id`
               LEFT OUTER JOIN (SELECT z.`channel_id`, z.`canonical_url`, z.`title`, ROUND(UNIX_TIMESTAMP(z.`publish_at`)) as `publish_unix`, z.`type`, z.`guid`
                                  FROM `Post` p LEFT OUTER JOIN `Post` z ON p.`channel_id` = z.`channel_id` AND p.`type` = z.`type` AND p.`publish_at` <= z.`publish_at`
                                                                        AND CURRENT_TIMESTAMP BETWEEN z.`publish_at` AND IFNULL(z.`expires_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 MINUTE))
                                                                        AND p.`id` <> z.`id` AND z.`privacy_type` = 'visibility.public' AND z.`is_deleted` = 'N'
                                 WHERE p.`is_deleted` = 'N' and p.`guid` = '[POST_GUID]'
                                 ORDER BY z.`publish_at`, z.`id`
                                 LIMIT 1) fwd ON p.`channel_id` = fwd.`channel_id`
               LEFT OUTER JOIN (SELECT z.`channel_id`, z.`canonical_url`, z.`title`, ROUND(UNIX_TIMESTAMP(z.`publish_at`)) as `publish_unix`, z.`type`, z.`guid`
                                  FROM `Post` p LEFT OUTER JOIN `Post` z ON p.`channel_id` = z.`channel_id` AND p.`type` = z.`type`
                                                                        AND CURRENT_TIMESTAMP BETWEEN z.`publish_at` AND IFNULL(z.`expires_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 MINUTE))
                                                                        AND p.`id` <> z.`id` AND z.`privacy_type` = 'visibility.public' AND z.`is_deleted` = 'N'
                                 WHERE p.`is_deleted` = 'N' and p.`guid` = '[POST_GUID]'
                                 ORDER BY RAND()
                                 LIMIT 1) rnd ON p.`channel_id` = rnd.`channel_id`
               LEFT OUTER JOIN (SELECT z.`channel_id`, z.`canonical_url`, z.`title`, ROUND(UNIX_TIMESTAMP(z.`publish_at`)) as `publish_unix`, z.`type`, z.`guid`
                                  FROM `Post` p LEFT OUTER JOIN `Post` z ON p.`channel_id` = z.`channel_id` AND p.`type` = z.`type`
                                                                        AND CURRENT_TIMESTAMP BETWEEN z.`publish_at` AND IFNULL(z.`expires_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 MINUTE))
                                                                        AND p.`id` <> z.`id` AND z.`privacy_type` = 'visibility.public' AND z.`is_deleted` = 'N'
                                 WHERE p.`is_deleted` = 'N' and p.`guid` = '[POST_GUID]'
                                 ORDER BY RAND()
                                 LIMIT 1) ynd ON p.`channel_id` = ynd.`channel_id`
               LEFT OUTER JOIN (SELECT z.`channel_id`, z.`canonical_url`, z.`title`, ROUND(UNIX_TIMESTAMP(z.`publish_at`)) as `publish_unix`, z.`type`, z.`guid`
                                  FROM `Post` p LEFT OUTER JOIN `Post` z ON p.`channel_id` = z.`channel_id` AND p.`type` = z.`type`
                                                                        AND CURRENT_TIMESTAMP BETWEEN z.`publish_at` AND IFNULL(z.`expires_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 MINUTE))
                                                                        AND p.`id` <> z.`id` AND z.`privacy_type` = 'visibility.public' AND z.`is_deleted` = 'N'
                                 WHERE p.`is_deleted` = 'N' and p.`guid` = '[POST_GUID]'
                                 ORDER BY RAND()
                                 LIMIT 1) znd ON p.`channel_id` = znd.`channel_id`
 WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and p.`is_deleted` = 'N' and p.`guid` = '[POST_GUID]'
 ORDER BY su.`is_active` DESC
 LIMIT 1;