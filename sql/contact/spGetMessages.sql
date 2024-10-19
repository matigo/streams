DELIMITER ;;
DROP PROCEDURE IF EXISTS GetMessages;;
CREATE PROCEDURE GetMessages( IN `token_id` int(11), IN `token_guid` varchar(64), IN `count_only` char(1) )
BEGIN
    DECLARE my_home varchar(255);

    /** ********************************************************************** **
     *  Function returns any Messages that are associated with the Account that is
     *      tied to the Authentication Token provided.
     *
     *  Usage: CALL GetMessages(424, '29b215ec-64c7-11e9-9881-54ee758049c3-f8d1-a87ff679', 'N');
     ** ********************************************************************** **/

    /* If the Count Only value is not cromulent, make it so */
    IF IFNULL(`count_only`, '') NOT IN ('N', 'Y') THEN
        SET `count_only` = 'N';
    END IF;

    /* Get the MyHome URL for the Account */
    SELECT CONCAT(CASE WHEN zsi.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', zsu.`url`) INTO my_home
      FROM `Channel` zch INNER JOIN `Site` zsi ON zch.`site_id` = zsi.`id`
                         INNER JOIN `SiteUrl` zsu ON zsi.`id` = zsu.`site_id`
                         INNER JOIN `Tokens` ztt ON zsi.`account_id` = ztt.`account_id`
     WHERE zch.`is_deleted` = 'N' and zsi.`is_deleted` = 'N' and ztt.`is_deleted` = 'N'
       and zsu.`is_deleted` = 'N' and zch.`type` = 'channel.site' and ztt.`id` = `token_id` and ztt.`guid` = `token_guid`
     ORDER BY zsi.`created_at`, zsu.`is_active` DESC, zsu.`created_at` DESC
     LIMIT 1;

    /* Collect the Information */
    DROP TEMPORARY TABLE IF EXISTS tmp;
    CREATE TEMPORARY TABLE tmp AS
    SELECT su.`url`, sc.`name`, sc.`mail`, sc.`subject`, sc.`message`, sc.`guid`,
           sc.`is_read`, sc.`is_mailed`,
           CASE WHEN sc.`mail` IN (SELECT DISTINCT `email` FROM `Account` UNION ALL
                                   SELECT DISTINCT `email` FROM `Persona`) THEN 'N'
                WHEN sc.`message` LIKE '%viagra%' THEN 'Y'
                WHEN sc.`message` LIKE '%cialis%' THEN 'Y'
                WHEN sc.`message` LIKE '%brands%' THEN 'Y'
                WHEN sc.`message` LIKE '%agency%' THEN 'Y'
                WHEN sc.`message` LIKE '%afiliate%' THEN 'Y'
                WHEN sc.`message` LIKE '%about.me%' THEN 'Y'
                WHEN sc.`message` LIKE '%украине%' THEN 'Y'
                WHEN sc.`message` LIKE '%сайт%' THEN 'Y'
                WHEN sc.`message` LIKE '%.ru%' THEN 'Y'
                WHEN sc.`message` LIKE '%.tk/%' THEN 'Y'
                WHEN sc.`message` LIKE '%http%' THEN 'Y'
                WHEN sc.`message` LIKE '%f r e e%' THEN 'Y'
                WHEN sc.`message` LIKE '%voip%' THEN 'Y'
                WHEN sc.`message` LIKE '%to advertise%' THEN 'Y'
                WHEN sc.`message` LIKE '%you have been hacked%' THEN 'Y'
                WHEN sc.`message` LIKE '%?asturbat?on%' THEN 'Y'
                WHEN sc.`message` LIKE '%investment%' THEN 'Y'
                WHEN sc.`message` LIKE '%v?deo%' THEN 'Y'
                WHEN sc.`message` lIKE '%кино%' THEN 'Y'
                WHEN sc.`message` LIKE '%BTC%' THEN 'Y'
                WHEN sc.`message` LIKE '%SEO%' THEN 'Y'
                WHEN sc.`message` LIKE '%рублях%' THEN 'Y'
                WHEN sc.`message` LIKE '%원%' THEN 'Y'
                WHEN sc.`mail` IN ('plan.b.fundingoptions@gmail.com', 'melody_fan@gmail.com') THEN 'Y'
                WHEN sc.`mail` LIKE '%investment%' THEN 'Y'
                WHEN sc.`mail` LIKE '%@alliedconsults.com' THEN 'Y'
                WHEN sc.`mail` LIKE '%@alychidesigns.com' THEN 'Y'
                WHEN sc.`mail` LIKE 'raphae%@gmail.com' THEN 'Y'
                WHEN sc.`mail` LIKE '%@qmails.pro' THEN 'Y'
                WHEN sc.`mail` LIKE '%@qmails.co' THEN 'Y'
                WHEN sc.`mail` LIKE '%@yandex.com' THEN 'Y'
                WHEN sc.`mail` LIKE '%@bigmir.net' THEN 'Y'
                WHEN sc.`mail` LIKE '%.email' THEN 'Y'
                WHEN sc.`mail` LIKE '%.ru' THEN 'Y'
                WHEN hsh.`hits` > 1 THEN 'Y'
                ELSE 'N' END as `is_spam`,
           sc.`created_at`, sc.`updated_at`
      FROM `SiteContact` sc INNER JOIN `SiteUrl` su ON sc.`site_id` = su.`site_id`
                            INNER JOIN `Site` si ON su.`site_id` = si.`id`
                            INNER JOIN `Account` acct ON si.`account_id` = acct.`id`
                            INNER JOIN `Tokens` tt ON acct.`id` = tt.`account_id`
                       LEFT OUTER JOIN (SELECT zc.`hash`, COUNT(zc.`id`) as `hits`
                                          FROM `SiteContact` zc
                                         GROUP BY zc.`hash` ORDER BY `hits` DESC) hsh ON sc.`hash` = hsh.`hash`
     WHERE sc.`is_deleted` = 'N' and su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and acct.`is_deleted` = 'N' and tt.`is_deleted` = 'N'
       and su.`is_active` = 'Y' and tt.`id` = `token_id` and tt.`guid` = `token_guid`
     ORDER BY sc.`created_at` DESC;

    /* If We're Just Returning Counts, then Include "My Data" */
    IF IFNULL(`count_only`, '') = 'Y' THEN
        SELECT my_home as `my_home`, COUNT(DISTINCT tmp.`guid`) as `unread`
          FROM tmp
         WHERE tmp.`is_read` = 'N' and tmp.`is_spam` = 'N';

    ELSE
        /* Set the Non-Spam Messages as Read */
        UPDATE `SiteContact` sc INNER JOIN tmp ON sc.`guid` = tmp.`guid`
           SET sc.`is_read` = 'Y',
               sc.`updated_at` = Now()
         WHERE tmp.`is_read` = 'N' and tmp.`is_spam` = 'N';

        /* Return the Messages */
        SELECT my_home as `my_home`, tmp.`url`, tmp.`name`, tmp.`mail`, tmp.`subject`, tmp.`message`,
               tmp.`guid`, tmp.`is_read`, tmp.`is_mailed`, tmp.`is_spam`,
               tmp.`created_at`, ROUND(UNIX_TIMESTAMP(tmp.`created_at`)) as `created_unix`,
               tmp.`updated_at`, ROUND(UNIX_TIMESTAMP(tmp.`updated_at`)) as `updated_unix`
          FROM tmp
         ORDER BY tmp.`is_read`, tmp.`created_at` DESC
         LIMIT 250;
    END IF;
END ;;
DELIMITER ;