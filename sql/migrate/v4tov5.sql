SELECT DISTINCT p.`canonical_url`, REPLACE(LOWER(pt.`name`), ' ', '-') as `slug`, pt.`name`
  FROM `century`.`User` u INNER JOIN `century`.`Site` s ON u.`id` = s.`user_id`
                          INNER JOIN `century`.`Channel` c ON s.`id` = c.`site_id`
                          INNER JOIN `century`.`Post` p ON c.`id` = p.`channel_id`
                          INNER JOIN `century`.`PostContent` pc ON p.`id` = pc.`post_id`
                          INNER JOIN `century`.`PostTags` pt ON pc.`post_id` = pt.`post_id`
 WHERE p.`is_deleted` = 'N' and p.`type` IN ('post.blog')
   and u.`id` = 13 and 'sumudu.me' IN (s.`url`, s.`custom_url`)
 ORDER BY p.`canonical_url`;

UPDATE `Post`
   SET `type` = 'post.article'
 WHERE `persona_id` = 3 and `title` IS NOT NULL;

SELECT *
  FROM `Post`
 WHERE `persona_id` = 3 and `title` IS NOT NULL
 ORDER BY `id` DESC
 LIMIT 250;

SELECT * FROM `Channel`;

SELECT * FROM `SiteUrl`
 LIMIT 25;




SELECT 3 as `persona_id`, 1 as `client_id`, p.`title`, REPLACE(REPLACE(REPLACE(pc.`value`, 'https://cdn.10centuries.org/NtyZNP/', 'https://blog.sumudu.me/files/nDATlw/'), '//cdn.10centuries.org/OELwyN/', 'https://blog.sumudu.me/files/nDATlw/'), 'https:https://', 'https://') as `value`,
       p.`canonical_url` as `canonical_url`, 2 as `channel_id`, p.`slug`, 'post.note' as `type`, p.`guid`, p.`privacy_type`, p.`publish_at`,
       p.`created_at`, 2 as `created_by`, p.`updated_at`, 2 as `updated_at`
  FROM `century`.`User` u INNER JOIN `century`.`Site` s ON u.`id` = s.`user_id`
                          INNER JOIN `century`.`Channel` c ON s.`id` = c.`site_id`
                          INNER JOIN `century`.`Post` p ON c.`id` = p.`channel_id`
                          INNER JOIN `century`.`PostContent` pc ON p.`id` = pc.`post_id`
 WHERE p.`is_deleted` = 'N' and p.`type` IN ('post.blog')
   and u.`id` = 13 and 'sumudu.me' IN (s.`url`, s.`custom_url`)
   and pc.`value` LIKE '%ies.org/NtyZNP/%'
 ORDER BY p.`publish_at`;


INSERT INTO `Post` (`persona_id`, `client_id`, `title`, `value`, `canonical_url`, `channel_id`,
                    `slug`, `type`, `guid`, `privacy_type`, `publish_at`,
                    `created_at`, `created_by`, `updated_at`, `updated_by`);
SELECT 3 as `persona_id`, 1 as `client_id`, p.`title`, REPLACE(REPLACE(REPLACE(pc.`value`, 'https://cdn.10centuries.org/NtyZNP/', 'https://blog.sumudu.me/files/nDATlw/'), '//cdn.10centuries.org/OELwyN/', 'https://blog.sumudu.me/files/nDATlw/'), 'https:https://', 'https://') as `value`,
       p.`canonical_url` as `canonical_url`, 2 as `channel_id`, p.`slug`, 'post.note' as `type`, uuid() as `guid`, p.`privacy_type`, p.`publish_at`,
       p.`created_at`, 2 as `created_by`, p.`updated_at`, 2 as `updated_at`
  FROM `century`.`User` u INNER JOIN `century`.`Site` s ON u.`id` = s.`user_id`
                          INNER JOIN `century`.`Channel` c ON s.`id` = c.`site_id`
                          INNER JOIN `century`.`Post` p ON c.`id` = p.`channel_id`
                          INNER JOIN `century`.`PostContent` pc ON p.`id` = pc.`post_id`
 WHERE p.`is_deleted` = 'N' and p.`type` IN ('post.blog')
   and u.`id` = 13 and 'sumudu.me' IN (s.`url`, s.`custom_url`)
 ORDER BY p.`publish_at`;

SELECT pt.`key`, COUNT(pt.`post_id`) as `instances`
  FROM `Post` p INNER JOIN `PostTags` pt ON p.`id` = pt.`post_id`
 WHERE pt.`is_deleted` = 'N' and p.`is_deleted` = 'N' and p.`persona_id` = 1
 GROUP BY pt.`key`
 ORDER BY `instances` DESC
LIMIT 250;

INSERT INTO `PostTags` (`post_id`, `key`, `value`, `created_at`);
SELECT DISTINCT sp.`id` as `post_id`, REPLACE(LOWER(pt.`name`), ' ', '-') as `key`, pt.`name` as `value`, p.`created_at`
  FROM `century`.`User` u INNER JOIN `century`.`Site` s ON u.`id` = s.`user_id`
                          INNER JOIN `century`.`Channel` c ON s.`id` = c.`site_id`
                          INNER JOIN `century`.`Post` p ON c.`id` = p.`channel_id`
                          INNER JOIN `century`.`PostContent` pc ON p.`id` = pc.`post_id`
                          INNER JOIN `century`.`PostTags` pt ON pc.`post_id` = pt.`post_id`
                          INNER JOIN `streams`.`Post` sp ON p.`canonical_url` = sp.`canonical_url`
 WHERE p.`is_deleted` = 'N' and p.`type` IN ('post.blog')
   and u.`id` = 1 and 'matigo.ca' IN (s.`url`, s.`custom_url`)
 ORDER BY sp.`id`
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N';


INSERT INTO `Post` (`persona_id`, `client_id`, `title`, `value`, `canonical_url`, `channel_id`,
                    `slug`, `type`, `guid`, `privacy_type`, `publish_at`,
                    `created_at`, `created_by`, `updated_at`, `updated_by`)
SELECT 1 as `persona_id`, 1 as `client_id`, NULL as `title`,
       REPLACE(REPLACE(p.`Value`, 'http://jasonirwin.ca', 'https://matigo.ca'), ' [files.app.net]', '') as `value`,
       CONCAT('/note/', tmp.`prop_guid`) as `canonical_url`, 1 as `channel_id`, tmp.`prop_guid` as `slug`, 'post.note' as `type`, tmp.`prop_guid` as `guid`,
       'visibility.public' as `privacy_type`, p.`CreateDTS` as `publish_at`,
       p.`CreateDTS`, 1 as `created_by`, p.`CreateDTS`, 1 as `updated_at`
  FROM `notemaster`.`Content_s1` p INNER JOIN
       (SELECT z.`guid`, uuid() as `prop_guid` FROM `notemaster`.`Content_s1` z
         WHERE z.`TypeCd` = 'TWEET' and z.`SiteID` = 5) tmp ON p.`guid` = tmp.`guid`
 WHERE p.`TypeCd` = 'TWEET' and p.`SiteID` = 5
   and p.`Value` <> '' and p.`Value` NOT LIKE '%@%' and p.`Value` NOT LIKE '%https://photos.app.net/%' and p.`Value` NOT LIKE '%http://rpstpp.net%'
   and p.`Value` NOT LIKE 'Just wrote about "%' and p.`Value` NOT LIKE 'New blog post:%' and p.`Value` NOT LIKE 'New Post!:"%'
 ORDER BY p.`CreateDTS`;


SELECT DISTINCT `TypeCd` FROM `notemaster`.`Content_s1`
 LIMIT 500
;

SELECT * FROM `notemaster`.`Content_s1`
 WHERE `SiteID` = 5 and `TypeCd` = 'TWEET' and `value` LIKE 'Standing in a parking lot%'
;

UPDATE `Post`
   SET `is_deleted` = 'Y'
  WHERE `is_deleted` = 'N' and `persona_id` = 1 and `channel_id` = 1 and `value` LIKE 'New Post:%';

SELECT * FROM `PostMeta`
 ORDER BY `post_id` DESC
 LIMIT 250;

INSERT INTO `PostMeta` (`post_id`, `key`, `value`)
SELECT DISTINCT ph.`post_id`, 'source_network' as `key`, 'App.Net' as `value`
  FROM `PostHistory` ph INNER JOIN `Post` p ON ph.`post_id` = p.`id`
 WHERE ph.`is_deleted` = 'N' and p.`is_deleted` = 'N'
   and ph.`created_at` BETWEEN '2018-07-12 05:21:40' AND '2018-07-12 05:21:45'
   and p.`persona_id` = 1
 ORDER BY `post_id`;

SELECT * FROM `PostHistory`
 WHERE `created_at` < '2018-07-12 06:13:10'
 ORDER BY `post_id` DESC
 LIMIT 250;

