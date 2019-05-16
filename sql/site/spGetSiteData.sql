DELIMITER ;;
DROP PROCEDURE IF EXISTS GetSiteData;;
CREATE PROCEDURE GetSiteData( IN `account_id` int(11), IN `site_url` varchar(128), IN `request_uri` varchar(256), IN `site_token` varchar(256), IN `site_pass` varchar(512) )
BEGIN
    DECLARE `x_site_locked` ENUM('N', 'Y');
    DECLARE `x_pass_valid` ENUM('N', 'Y');

   /** ********************************************************************** **
     *  Function collects the pertinent Site information for a given URL.
     *
     *  Usage: CALL GetSiteData(1, 'matigo.local', '/', '', '');
     ** ********************************************************************** **/

    DECLARE EXIT HANDLER FOR SQLEXCEPTION, SQLWARNING
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    DROP TEMPORARY TABLE IF EXISTS tmp;
    CREATE TEMPORARY TABLE tmp AS
    SELECT su.`site_id`, si.`guid` as `site_guid`, su.`id` as `url_id`, su.`url` as `site_url`,
           si.`name` as `site_name`, si.`description`, si.`keywords`, si.`https`, si.`theme`, si.`is_default`,
           IFNULL((SELECT z.`value` FROM `SiteMeta` z
                    WHERE z.`is_deleted` = 'N' and z.`key` = 'summary' and z.`site_id` = si.`id`), '') as `summary`,
           CAST(NULL AS CHAR(512)) as `page_title`,
           CAST(NULL AS CHAR(64)) as `page_type`,
           ch.`name` as `channel_name`, ch.`guid` as `channel_guid`, ch.`id` as `channel_id`, ch.`privacy_type` as `channel_privacy`,
           (SELECT z.`guid` FROM `Client` z
             WHERE z.`is_deleted` = 'N' and z.`is_active` = 'Y' and z.`name` = 'Default Client') as `client_guid`,
           'N' as `show_geo`, 'N' as `show_note`, 'Y' as `show_article`, 'Y' as `show_bookmark`, 'Y' as `show_location`, 'Y' as `show_quotation`,
           si.`version` as `site_version`, si.`updated_at` as `site_updated_at`,
           lu.`is_active` as `url_active`, UNIX_TIMESTAMP(lu.`updated_at`) as `url_ua`,
           CASE WHEN si.`account_id` = `account_id` THEN 'Y' ELSE 'N' END as `can_edit`,
           CASE WHEN ch.`privacy_type` IN ('visibility.password', 'visibility.none') THEN 'Y' ELSE 'N' END as `site_locked`,
           CASE WHEN lu.`id` <> su.`id` THEN 'Y'
                WHEN lu.`url` <> LOWER(`site_url`) THEN 'Y'
                ELSE 'N' END as `do_redirect`,
           0 as `sort_order`
      FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                        INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
                        INNER JOIN `SiteUrl` lu ON su.`site_id` = lu.`site_id`
     WHERE si.`is_deleted` = 'N' and su.`is_deleted` = 'N' and su.`is_active` = 'Y' and lu.`url` IN (`site_url`, CONCAT('www.', `site_url`))
     UNION ALL
    SELECT su.`site_id`, si.`guid` as `site_guid`, su.`id` as `url_id`, su.`url` as `site_url`,
           si.`name` as `site_name`, si.`description`, si.`keywords`, si.`https`, si.`theme`, si.`is_default`,
           '' as `summary`,
           '' as `page_title`, 'website' as `page_type`,
           ch.`name` as `channel_name`, ch.`guid` as `channel_guid`, ch.`id` as `channel_id`, ch.`privacy_type` as `channel_privacy`,
           '' as `client_guid`,
           'N' as `show_geo`, 'N' as `show_note`, 'Y' as `show_article`, 'Y' as `show_bookmark`, 'Y' as `show_location`, 'Y' as `show_quotation`,
           '0' as `site_version`, Now() as `site_updated_at`,
           'Y' as `url_active`, 0 as `url_ua`,
           'N' as `can_edit`, CASE WHEN ch.`privacy_type` IN ('visibility.password', 'visibility.none') THEN 'Y' ELSE 'N' END as `site_locked`,
           'Y' as `do_redirect`, 1 as `sort_order`
      FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                        INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
     WHERE si.`is_deleted` = 'N' and su.`is_deleted` = 'N' and si.`is_default` = 'Y' and su.`is_active` = 'Y'
     ORDER BY `sort_order`, `url_id` DESC, `url_active` DESC, `url_ua` DESC
     LIMIT 1;

    /* Add the Page Title and Type if Applicable */
    IF IFNULL(`request_uri`, '') NOT IN ('', '/') THEN
        UPDATE tmp INNER JOIN `Post` po ON tmp.`channel_id` = po.`channel_id` AND po.`canonical_url` = `request_uri`
           SET tmp.`page_title` = IFNULL(po.`title`, po.`value`),
               tmp.`page_type` = po.`type`
         WHERE tmp.`do_redirect` = 'N' and po.`id` > 0;
    END IF;

    /* Is the Site locked? */
    SELECT CASE WHEN tmp.`can_edit` = 'Y' THEN 'N'
                ELSE tmp.`site_locked` END as `site_locked` INTO `x_site_locked`
      FROM tmp;

    IF IFNULL(`x_site_locked`, 'N') = 'Y' THEN
        SELECT zz.`pass_valid` INTO `x_pass_valid`
          FROM (SELECT 'Y' as `pass_valid`
                  FROM (SELECT SHA2(CONCAT(tmp.`site_guid`, '.', tmp.`url_ua`, '.', DATE_FORMAT(DATE_SUB(Now(), INTERVAL cnt.`num` HOUR), '%Y-%m-%d %H:00:00')), 256) as `hash`
                          FROM tmp INNER JOIN (SELECT 0 as `num` UNION ALL SELECT  1 as `num` UNION ALL SELECT  2 as `num`) cnt ON `num` >= 0
                         WHERE tmp.`site_locked` = 'Y') z
                 WHERE z.`hash` = `site_token`
                 UNION ALL
                SELECT 'N' as `pass_valid`) zz
        ORDER BY zz.`pass_valid` DESC
        LIMIT 1;
    END IF;

    IF IFNULL(`x_site_locked`, 'N') = 'Y' AND IFNULL(`x_pass_valid`, 'N') = 'N' AND IFNULL(`site_pass`, '') <> '' THEN
        SELECT zz.`pass_valid` INTO `x_pass_valid`
          FROM (SELECT 'Y' as `pass_valid`
                  FROM `SiteMeta` sm INNER JOIN tmp ON sm.`site_id` = tmp.`site_id`
                 WHERE sm.`is_deleted` = 'N' and sm.`key` = 'site.password'
                   and sm.`value` = sha2(CONCAT(UNIX_TIMESTAMP(`updated_at`), '.', `site_pass`), 512)
                 UNION ALL
                SELECT 'N' as `pass_valid`) zz
        ORDER BY zz.`pass_valid` DESC
        LIMIT 1;
    END IF;

    /* Prepare the Meta If Any Exists */
    DROP TEMPORARY TABLE IF EXISTS meta;
    CREATE TEMPORARY TABLE meta AS
    SELECT zz.`site_id`,
           MAX(zz.`show_geo`) as `show_geo`,
           MAX(zz.`show_article`) as `show_article`,
           MAX(zz.`show_bookmark`) as `show_bookmark`,
           MAX(zz.`show_location`) as `show_location`,
           MAX(zz.`show_note`) as `show_note`,
           MAX(zz.`show_quotation`) as `show_quotation`
      FROM (SELECT sm.`site_id`,
                   CASE WHEN sm.`key` = 'show_geo' THEN sm.`value` ELSE 'N' END as `show_geo`,
                   CASE WHEN sm.`key` = 'show_article' THEN sm.`value` ELSE 'N' END as `show_article`,
                   CASE WHEN sm.`key` = 'show_bookmark' THEN sm.`value` ELSE 'N' END as `show_bookmark`,
                   CASE WHEN sm.`key` = 'show_location' THEN sm.`value` ELSE 'N' END as `show_location`,
                   CASE WHEN sm.`key` = 'show_note' THEN sm.`value` ELSE 'N' END as `show_note`,
                   CASE WHEN sm.`key` = 'show_quotation' THEN sm.`value` ELSE 'N' END as `show_quotation`
              FROM `SiteMeta` sm INNER JOIN tmp ON sm.`site_id` = tmp.`site_id`
             WHERE sm.`is_deleted` = 'N') zz
     GROUP BY zz.`site_id`;

    /* Return the Detailed Site Information */
    SELECT tmp.`site_id`, tmp.`site_guid`, tmp.`url_id`, tmp.`https`, tmp.`site_url`, tmp.`site_name`, tmp.`description`, tmp.`keywords`,
           tmp.`theme`, tmp.`is_default`, tmp.`client_guid`,
           tmp.`summary`, IFNULL(tmp.`page_title`, tmp.`site_name`) as `page_title`, IFNULL(tmp.`page_type`, 'website') as `page_type`,
           tmp.`channel_name`, tmp.`channel_guid`, tmp.`channel_id`, tmp.`channel_privacy`,
           IFNULL(meta.`show_geo`, tmp.`show_geo`) as `show_geo`,
           IFNULL(meta.`show_note`, tmp.`show_note`) as `show_note`,
           IFNULL(meta.`show_article`, tmp.`show_article`) as `show_article`,
           IFNULL(meta.`show_bookmark`, tmp.`show_bookmark`) as `show_bookmark`,
           IFNULL(meta.`show_location`, tmp.`show_location`) as `show_location`,
           IFNULL(meta.`show_quotation`, tmp.`show_quotation`) as `show_quotation`,
           tmp.`site_version`, tmp.`site_updated_at`,
           CASE WHEN tmp.`can_edit` = 'Y' THEN 'N'
                WHEN tmp.`site_locked` = 'Y' AND IFNULL(`x_pass_valid`, 'N') = 'Y' THEN 'N'
                ELSE tmp.`site_locked` END as `site_locked`,
           CASE WHEN IFNULL(`site_pass`, '') <> '' AND IFNULL(`x_pass_valid`, 'N') = 'Y'
                THEN SHA2(CONCAT(tmp.`site_guid`, '.', tmp.`url_ua`, '.', DATE_FORMAT(Now(), '%Y-%m-%d %H:00:00')), 256)
                ELSE NULL END as `site_token`,
           tmp.`url_active`, tmp.`url_ua`, tmp.`can_edit`, tmp.`do_redirect`
      FROM tmp INNER JOIN meta ON tmp.`site_id` = meta.`site_id`
     ORDER BY tmp.`sort_order`;
END ;;
DELIMITER ;