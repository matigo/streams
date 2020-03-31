DELIMITER ;;
DROP PROCEDURE IF EXISTS CreateSite;;
CREATE PROCEDURE CreateSite( IN `in_account_id` int(11), IN `in_persona_guid` char(36),
                             IN `in_site_name` varchar(80), IN `in_site_descr` varchar(255), IN `in_site_keys` varchar(255),
                             IN `in_site_url` varchar(128), IN `in_privacy_type` varchar(64) )
BEGIN
    DECLARE `x_channel_id` int(11);
    DECLARE `x_site_id` int(11);

   /** ********************************************************************** **
     *  Function creates a Site and all the requisite data for it to function.
     *
     *  Usage: CALL CreateSite(1, '5182dbd0-5463-11e8-99a0-54ee758049c3', 'Nozomi the Dog', 'A Happy Blog for a Happy Puppy!', 'Nozomi, Japan, Puppy, Dog', 'nozomi.10centuries.org', 'visibility.public');
     ** ********************************************************************** **/

    /* If the Persona GUID is bad, Exit */
    IF LENGTH(IFNULL(`in_persona_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Persona GUID Provided';
    END IF;

    /* If the Persona is Owned by a Different Account, Exit */
    IF (SELECT COUNT(z.`id`) FROM `Persona` z WHERE z.`is_deleted` = 'N' and z.`guid` = `in_persona_guid` and z.`account_id` = `in_account_id`) <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Persona / Account Combination Provided';
    END IF;

    /* If the Requested URL is Used, Exit */
    IF (SELECT COUNT(su.`id`) as `sites`
          FROM `Account` acct INNER JOIN `Site` s ON acct.`id` = s.`account_id`
                              INNER JOIN `SiteUrl` su ON s.`id` = su.`site_id`
         WHERE acct.`is_deleted` = 'N' and su.`is_deleted` = 'N' and s.`is_deleted` = 'N' and su.`url` = `in_site_url`) <> 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Requested URL Is In Use';
    END IF;

    /* Create the Site Record */
    INSERT INTO `Site` (`account_id`, `name`, `description`, `keywords`, `https`, `theme`)
    SELECT acct.`id` as `account_id`, LEFT(TRIM(`in_site_name`), 80) as `name`, LEFT(TRIM(`in_site_descr`), 255) as `description`,
           LEFT(TRIM(`in_site_keys`), 255) as `keywords`, 'Y' as `https`, 'anri' as `theme`
      FROM `Account` acct
     WHERE acct.`is_deleted` = 'N' and acct.`id` = `in_account_id`
     LIMIT 1;
    SELECT LAST_INSERT_ID() INTO `x_site_id`;

    /* Ensure We Have a Site ID */
    IF IFNULL(`x_site_id`, 0) <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Could Not Create Site Record';
    END IF;

    /* Set the Site's URL */
    INSERT INTO `SiteUrl` (`site_id`, `url`, `is_active`)
    SELECT si.`id` as `site_id`, LEFT(TRIM(`in_site_url`), 140) as `url`, 'Y' as `is_active`
      FROM `Site` si
     WHERE si.`is_deleted` = 'N' and si.`id` = `x_site_id`
     LIMIT 1;

    /* Create the Channel Record */
    INSERT INTO `Channel` (`account_id`, `name`, `type`, `privacy_type`, `site_id`)
    SELECT acct.`id` as `account_id`, LEFT(TRIM(`in_site_name`), 80) as `name`, 'channel.site' as `type`,
           `in_privacy_type` as `privacy_type`, `x_site_id` as `site_id`
      FROM `Account` acct
     WHERE acct.`is_deleted` = 'N' and acct.`id` = `in_account_id`
     LIMIT 1;
    SELECT LAST_INSERT_ID() INTO `x_channel_id`;

    /* Set the Channel Author */
    INSERT INTO `ChannelAuthor` (`channel_id`, `persona_id`, `can_read`, `can_write`)
    SELECT `x_channel_id` as `channel_id`, pa.`id` as `persona_id`, 'Y' as `can_read`, 'Y' as `can_write`
      FROM `Persona` pa
     WHERE pa.`is_deleted` = 'N' and pa.`guid` = `in_persona_guid`
     LIMIT 1;

    /* Set Initial the Site Meta */
    INSERT INTO `SiteMeta` (`site_id`, `key`, `value`)
    SELECT `x_site_id` as `site_id`, tmp.`key`, tmp.`value`
      FROM (SELECT 'show_article' as `key`, 'Y' as `value` UNION ALL
            SELECT 'show_bookmark' as `key`, 'Y' as `value` UNION ALL
            SELECT 'show_geo' as `key`, 'N' as `value` UNION ALL
            SELECT 'show_location' as `key`, 'N' as `value` UNION ALL
            SELECT 'show_note' as `key`, 'N' as `value` UNION ALL
            SELECT 'show_quotation' as `key`, 'Y' as `value`) tmp
     ORDER BY tmp.`key`;

    /* If We're Here, It's Good. Return the Basic Info */
    SELECT `x_site_id` as `site_id`, `x_channel_id` as `channel_id`;
END ;;
DELIMITER ;