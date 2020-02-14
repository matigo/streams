DELIMITER ;;
DROP PROCEDURE IF EXISTS SetSiteData;;
CREATE PROCEDURE SetSiteData( IN `in_account_id` int(11), IN `in_channel_guid` char(36),
                              IN `in_site_name` varchar(80), IN `in_site_descr` varchar(255), IN `in_site_keys` varchar(255),
                              IN `in_site_theme` varchar(128), IN `in_site_colour` varchar(64),
                              IN `in_privacy_type` varchar(64), IN `in_site_pass` varchar(2048),
                              IN `in_show_geo` char(1), IN `in_show_note` char(1), IN `in_show_blog` char(1),
                              IN `in_show_bookmark` char(1), IN `in_show_location` char(1), IN `in_show_quote` char(1),
                              IN `in_show_photo` char(1)
                             )
BEGIN
    DECLARE `x_can_edit` tinyint;

    /** ********************************************************************** **
     *  Function updates a Site's primary information and returns the Site.version value
     *
     *  Usage: CALL SetSiteData( 1, '91c46924-5461-11e8-99a0-54ee758049c3',
                                'Matigo dot See, eh?', 'The Semi-Coherent Ramblings of a Canadian in Asia', 'matigo, 10C, v5, development, dev',
                                'anri', 'auto',
                                'visibility.public', '',
                                'N', 'N', 'Y',
                                'N', 'N', 'Y',
                                'Y');
     ** ********************************************************************** **/

    /* Determine if the Channel / Account Combination is Valid */
    SELECT 1 INTO `x_can_edit`
      FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
     WHERE ch.`guid` = `in_channel_guid` and si.`account_id` = `in_account_id`;

    /* Set the Channel and Site details, including the version */
    UPDATE `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
       SET ch.`name` = LEFT(`in_site_name`, 128),
           ch.`privacy_type` = `in_privacy_type`,
           si.`name` = LEFT(`in_site_name`, 128),
           si.`description` = LEFT(`in_site_descr`, 255),
           si.`keywords` = LEFT(`in_site_keys`, 255),
           si.`version` = UNIX_TIMESTAMP(Now()),
           si.`updated_at` = Now(),
           ch.`updated_at` = Now()
     WHERE ch.`guid` = `in_channel_guid` and si.`account_id` = `in_account_id`;

    /* Set the Site's visibility metadata */
    INSERT INTO `SiteMeta` (`site_id`, `key`, `value`)
    SELECT si.`id` as `site_id`, tmp.`key`, CASE WHEN tmp.`value` = 'Y' THEN 'Y' ELSE 'N' END as `value`
      FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                        INNER JOIN (SELECT 'show_geo' as `key`, `in_show_geo` as `value` UNION ALL
                                    SELECT 'show_note' as `key`, `in_show_note` as `value` UNION ALL
                                    SELECT 'show_article' as `key`, `in_show_blog` as `value` UNION ALL
                                    SELECT 'show_bookmark' as `key`, `in_show_bookmark` as `value` UNION ALL
                                    SELECT 'show_location' as `key`, `in_show_location` as `value` UNION ALL
                                    SELECT 'show_photo' as `key`, `in_show_photo` as `value` UNION ALL
                                    SELECT 'show_quotation' as `key`, `in_show_quote` as `value`) tmp
     WHERE ch.`guid` = `in_channel_guid` and si.`account_id` = `in_account_id`
        ON DUPLICATE KEY UPDATE `value` = CASE WHEN tmp.`value` = 'Y' THEN 'Y' ELSE 'N' END,
                                `updated_at` = Now();

    INSERT INTO `SiteMeta` (`site_id`, `key`, `value`)
    SELECT si.`id` as `site_id`, tmp.`key`, CASE WHEN tmp.`value` <> '' THEN LEFT(tmp.`value`, 64) ELSE '' END as `value`
      FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                        INNER JOIN (SELECT 'site_color' as `key`, `in_site_colour` as `value`) tmp
     WHERE ch.`guid` = `in_channel_guid` and si.`account_id` = `in_account_id`
        ON DUPLICATE KEY UPDATE `value` = CASE WHEN tmp.`value` <> '' THEN LEFT(tmp.`value`, 64) ELSE '' END,
                                `updated_at` = Now();

    /* Set the Site password, if required */
    INSERT INTO `SiteMeta` (`site_id`, `key`, `value`, `is_deleted`, `updated_at`)
    SELECT ch.`site_id`, 'site.password' as `key`,
           CASE WHEN ch.`privacy_type` = 'visibility.password' THEN sha2(CONCAT(UNIX_TIMESTAMP(Now()), '.', `in_site_pass`), 512) ELSE '' END as `value`,
           CASE WHEN ch.`privacy_type` = 'visibility.password' THEN 'N' ELSE 'Y' END as `is_deleted`,
           Now() as `updated_at`
      FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
     WHERE ch.`is_deleted` = 'N' and ch.`guid` = `in_channel_guid`
       and si.`is_deleted` = 'N' and si.`account_id` = `in_account_id`
        ON DUPLICATE KEY UPDATE `value` = CASE WHEN ch.`privacy_type` = 'visibility.password' THEN sha2(CONCAT(UNIX_TIMESTAMP(Now()), '.', `in_site_pass`), 512) ELSE '' END,
                                `is_deleted` = CASE WHEN ch.`privacy_type` = 'visibility.password' THEN 'N' ELSE 'Y' END,
                                `updated_at` = Now();

    /* Return the Site Version or an Unhappy Integer */
    SELECT CASE WHEN IFNULL(`x_can_edit`, 0) > 0 THEN si.`version` ELSE 0 END as `version_id`
      FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
     WHERE ch.`guid` = `in_channel_guid` and si.`account_id` = `in_account_id`;

END ;;
DELIMITER ;