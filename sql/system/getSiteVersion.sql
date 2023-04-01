SELECT ROUND(UNIX_TIMESTAMP(GREATEST(si.`updated_at`, ch.`updated_at`, po.`updated_at`, pa.`updated_at`,
                                     IFNULL(po.`publish_at`, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 YEAR)),
                                     IFNULL(po.`expires_at`, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 YEAR))))) as `version`
  FROM `Site` si INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
            LEFT OUTER JOIN `Post` po ON ch.`id` = po.`channel_id` AND po.`is_deleted` = 'N'
                                     AND po.`type` IN (SELECT REPLACE(mm.`key`, 'show_', 'post.') FROM `SiteMeta` mm
                                                        WHERE mm.`is_deleted` = 'N' and mm.`key` LIKE 'show%' and mm.`value` = 'Y' and mm.`site_id` = si.`id`)
                                     AND IFNULL(po.`publish_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 MONTH)) <= CURRENT_TIMESTAMP
                                     AND IFNULL(po.`expires_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 MONTH)) > CURRENT_TIMESTAMP
            LEFT OUTER JOIN `Persona` pa ON po.`persona_id` = pa.`id` AND pa.`is_deleted` = 'N'
 WHERE ch.`is_deleted` = 'N' and si.`is_deleted` = 'N' and si.`id` = [SITE_ID]
 ORDER BY `version` DESC LIMIT 1;