INSERT INTO `streams`.`Post` (`persona_id`, `client_id`, `title`, `value`, `canonical_url`, `channel_id`, `slug`, `type`, `guid`, `privacy_type`, `publish_at`, `created_at`, `created_by`, `updated_at`, `updated_by`)
SELECT 3 as `persona_id`, 1 as `client_id`, CASE WHEN IFNULL(s0.`title`, '') <> '' THEN s0.`title` ELSE NULL END as `title`,
       s0.`Value` as `content`,
       CONCAT('/note/', SUBSTRING(s0.`guid`, 1, 8), '-', SUBSTRING(s0.`guid`, 9, 4), '-', SUBSTRING(s0.`guid`, 13, 4), '-', SUBSTRING(s0.`guid`, 17, 4), '-', SUBSTRING(s0.`guid`, 21, 12)) as `canonical_url`,
       2 as `channel_id`,
       CONCAT(SUBSTRING(s0.`guid`, 1, 8), '-', SUBSTRING(s0.`guid`, 9, 4), '-', SUBSTRING(s0.`guid`, 13, 4), '-', SUBSTRING(s0.`guid`, 17, 4), '-', SUBSTRING(s0.`guid`, 21, 12)) as `slug`,
       'post.note' as `type`,
       CONCAT(SUBSTRING(s0.`guid`, 1, 8), '-', SUBSTRING(s0.`guid`, 9, 4), '-', SUBSTRING(s0.`guid`, 13, 4), '-', SUBSTRING(s0.`guid`, 17, 4), '-', SUBSTRING(s0.`guid`, 21, 12)) as `guid`,
       'visibility.public' as `privacy_type`,
       DATE_SUB(s0.`CreateDTS`, INTERVAL 9 HOUR) as `publish_at`,
       DATE_SUB(s0.`CreateDTS`, INTERVAL 9 HOUR) as `created_at`, 2 as `created_by`,
       DATE_SUB(s0.`CreateDTS`, INTERVAL 9 HOUR) as `updated_at`, 2 as `updated_by`
  FROM `Content_s0` s0
 WHERE s0.`TypeCd` IN ('ADN-POST', 'TWEET') and s0.`SiteID` IN (97)
   and 'Y' = CASE WHEN s0.`Value` LIKE '@matigo%' THEN 'Y'
                  WHEN s0.`Value` NOT LIKE '%@%' THEN 'Y'
                  ELSE 'N' END
 ORDER BY s0.`CreateDTS`;
