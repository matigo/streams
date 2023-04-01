SELECT po.`id` as `post_id`, po.`guid` as `post_guid`, po.`type`,
       ROUND(UNIX_TIMESTAMP(GREATEST(si.`updated_at`, ch.`updated_at`, po.`updated_at`, pa.`updated_at`))) as `post_version`,
       CASE WHEN pa.`account_id` = [ACCOUNT_ID] THEN 'Y'
            WHEN IFNULL(pr.`follows`, 'N') = 'Y' AND 
                 CURRENT_TIMESTAMP BETWEEN IFNULL(po.`publish_at`, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)) 
                                       AND IFNULL(po.`expires_at`, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR)) THEN 'Y'
            WHEN ch.`privacy_type` IN ('visibility.public') AND po.`privacy_type` IN ('visibility.public') AND 
                 CURRENT_TIMESTAMP BETWEEN IFNULL(po.`publish_at`, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)) 
                                       AND IFNULL(po.`expires_at`, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR)) THEN 'Y'
            ELSE 'N' END as `is_visible`,
       po.`canonical_url`, po.`slug`
  FROM Site si INNER JOIN Channel ch ON si.`id` = ch.`site_id`
               INNER JOIN Post po ON ch.`id` = po.`channel_id`
               INNER JOIN Persona pa ON po.`persona_id` = pa.`id`
          LEFT OUTER JOIN PersonaRelation pr ON pa.`id` = pr.`persona_id` AND pr.`related_id` = [ACCOUNT_ID]
                                            AND IFNULL(pr.`is_blocked`, 'N') = 'N' AND pr.`is_deleted` = 'N'
 WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' 
   and 'Y' = CASE WHEN '' = '' AND po.`type` IN (SELECT REPLACE(mm.`key`, 'show_', 'post.') FROM `SiteMeta` mm
                                                  WHERE mm.`is_deleted` = 'N' and mm.`key` LIKE 'show%' and mm.`value` = 'Y' and mm.`site_id` = si.`id`) THEN 'Y'
                  WHEN LOWER('') = REPLACE(po.`type`, 'post.', '') THEN 'Y'
                  ELSE 'N' END
   and 'Y' = CASE WHEN pa.`account_id` = [ACCOUNT_ID] THEN 'Y'
                  WHEN IFNULL(pr.`follows`, 'N') = 'Y' AND 
                       CURRENT_TIMESTAMP BETWEEN IFNULL(po.`publish_at`, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)) 
                                             AND IFNULL(po.`expires_at`, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR)) THEN 'Y'
                  WHEN ch.`privacy_type` IN ('visibility.public') AND po.`privacy_type` IN ('visibility.public') AND 
                       CURRENT_TIMESTAMP BETWEEN IFNULL(po.`publish_at`, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)) 
                                             AND IFNULL(po.`expires_at`, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR)) THEN 'Y'
                  ELSE 'N' END
   and si.`id` = [SITE_ID]
 ORDER BY po.`publish_at` DESC
 LIMIT [PAGE], [COUNT];