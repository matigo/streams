DELIMITER ;;
DROP PROCEDURE IF EXISTS GetTokenData;;
CREATE PROCEDURE GetTokenData( IN `in_token_id` int(11), IN `in_token_guid` varchar(64), IN `in_password_age` int(11), IN `in_lifespan` int(11), `in_homeurl` varchar(256) )
BEGIN
    DECLARE `reset_pass`    enum('N','Y');
    DECLARE `premium_until` datetime;
    DECLARE `storage_gb`    smallint;

    /** ********************************************************************** **
     *  Function returns any Messages that are associated with the Account that is
     *      tied to the Authentication Token provided.
     *
     *  Usage: CALL GetTokenData(544, 'fa3b1e80-7879-11e9-941e-54ee758049c3-d371-a87ff679', 10000, 7200, 'nice.social');
     ** ********************************************************************** **/

    /* Ensure the password age is valid */
    IF IFNULL(`in_password_age`, 10000) < 0 OR IFNULL(`in_password_age`, 10000) > 10000 THEN
        SET `in_password_age` = 10000;
    END IF;

    IF IFNULL(`in_lifespan`, 7200) < 0 OR IFNULL(`in_lifespan`, 7200) > 7200 THEN
        SET `in_lifespan` = 7200;
    END IF;

    IF IFNULL(`in_token_id`, 0) > 0 THEN
        UPDATE `Tokens` t
           SET t.`updated_at` = Now()
         WHERE t.`is_deleted` = 'N' and t.`updated_at` >= DATE_SUB(Now(), INTERVAL `in_lifespan` DAY)
           and t.`id` = `in_token_id`;
    END IF;

    /* Determine if the password needs to be reset or not */
    SET `reset_pass` = (SELECT IFNULL((SELECT am.`value` FROM `AccountMeta` am INNER JOIN `Tokens` tt ON am.`account_id` = tt.`account_id`
                         WHERE tt.`is_deleted` = 'N' and am.`is_deleted` = 'N'
                           and am.`key` = 'system.password.reqchange' and tt.`id` = `in_token_id`
                         UNION ALL
                        SELECT CASE WHEN max(ap.`created_at`) <= DATE_FORMAT(DATE_SUB(Now(), INTERVAL `in_password_age` DAY), '%Y-%m-%d 00:00:00') THEN 'Y' ELSE 'N' END as `limit`
                          FROM `Account` acct INNER JOIN `AccountPass` ap ON acct.`id` = ap.`account_id`
                                              INNER JOIN `Tokens` tt ON acct.`id` = tt.`account_id`
                         WHERE acct.`is_deleted` = 'N' and ap.`is_deleted` = 'N' and tt.`is_deleted` = 'N'
                           and acct.`type` NOT IN ('account.admin') and tt.`id` = `in_token_id`
                         ORDER BY `value` DESC
                         LIMIT 1), 'N'));

    IF IFNULL(`reset_pass`, 'N') = 'Y' THEN
        INSERT INTO `AccountMeta` (`account_id`, `type`, `value`)
        SELECT acct.`id`, 'system.password.reqchange', 'Y'
          FROM `Account` acct INNER JOIN `Tokens` tt ON acct.`id` = tt.`account_id`
         WHERE acct.`is_deleted` = 'N' and tt.`is_deleted` = 'N' and acct.`type` NOT IN ('account.guest', 'account.admin')
           and tt.`id` = `in_token_id`
            ON DUPLICATE KEY UPDATE `value` = 'Y',
                                    `updated_at` = Now();
    END IF;

    /* Is the Account for a Premium Account Holder? */
    SELECT DATE_FORMAT(GREATEST((SELECT IFNULL((SELECT DATE_ADD(txn.`received_at`, INTERVAL CASE WHEN txn.`payment_gross` IN (15, 30, 60) THEN 366
                                                                                                 WHEN txn.`payment_gross` IN (5.75, 25) THEN 30
                                                                                                 ELSE 0 END DAY) as `until_at`
                                                  FROM `Tokens` tt INNER JOIN `Account` acct ON tt.`account_id` = acct.`id`
                                                                   INNER JOIN `PayPalTXN` txn ON acct.`id` = txn.`account_id`
                                                 WHERE txn.`is_deleted` = 'N' and txn.`type` = 'subscr_payment' and txn.`status` = 'Completed' and txn.`txn_id` <> ''
                                                   and acct.`is_deleted` = 'N' and tt.`is_deleted` = 'N' and tt.`guid` = `in_token_guid` and tt.`id` = `in_token_id`
                                                 ORDER BY txn.`received_at` DESC
                                                 LIMIT 1), '2000-01-01 00:00:00')),
                                (SELECT DATE_ADD(acct.`created_at`, INTERVAL CASE WHEN acct.`type` = 'account.admin' THEN DATEDIFF(DATE_ADD(acct.`created_at`, INTERVAL 1000 YEAR), acct.`created_at`)
                                                                                  WHEN acct.`type` = 'account.normal' THEN 30
                                                                                  ELSE 0 END DAY) as `until_at`
                                  FROM `Account` acct INNER JOIN `Tokens` tt ON acct.`id` = tt.`account_id`
                                 WHERE acct.`is_deleted` = 'N' and tt.`is_deleted` = 'N'
                                   and tt.`guid` = `in_token_guid` and tt.`id` = `in_token_id`)), '%Y-%m-%d 23:59:59') INTO `premium_until`;

    /* How much storage is available? */
    SELECT CASE WHEN `premium_until` > Now() THEN 25 ELSE 1 END INTO `storage_gb`;

    /* Collect the Base Information */
    DROP TEMPORARY TABLE IF EXISTS tmp;
    CREATE TEMPORARY TABLE tmp DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AS
    SELECT a.`id` as `account_id`, a.`email`, a.`type`, a.`display_name`, a.`language_code`, a.`timezone`,
           IFNULL((SELECT z.`avatar_img` FROM `Persona` z
                    WHERE z.`is_deleted` = 'N' and z.`account_id` = a.`id`
                    ORDER BY z.`is_active` DESC LIMIT 1), 'default.png') as `avatar_url`,
           CAST(0 AS UNSIGNED) as `file_count`,
           CAST(1024 * 1024 * 1024 * IFNULL(`storage_gb`, 1) AS UNSIGNED) as `storage_limit`,
           CAST(0 AS UNSIGNED) as `storage_used`,
           (SELECT z.`guid` FROM `Persona` z
             WHERE z.`is_deleted` = 'N' and z.`account_id` = a.`id`
             ORDER BY z.`is_active` DESC, z.`id` LIMIT 1) as `default_persona`,
           (SELECT z.`guid` FROM `Channel` z
             WHERE z.`is_deleted` = 'N' and z.`type` = 'channel.site' and z.`privacy_type` = 'visibility.public'
               and z.`account_id` = a.`id`
             ORDER BY z.`id` LIMIT 1) as `default_channel`,
           IFNULL((SELECT m.`value` FROM `AccountMeta` m
                    WHERE m.`is_deleted` = 'N' and m.`key` = 'preference.contact.mail'
                      and m.`account_id` = a.`id`), 'N') as `pref_contact_mail`,
           (SELECT CASE WHEN MAX(ca.`can_write`) = 'Y' THEN 'write'
                        WHEN MAX(ca.`can_read`) = 'Y' THEN 'read'
                        ELSE 'none' END as `access`
              FROM `SiteUrl` su INNER JOIN `Channel` ch ON su.`site_id` = ch.`site_id`
                                INNER JOIN `ChannelAuthor` ca ON ch.`id` = ca.`channel_id`
                                INNER JOIN `Persona` pa ON ca.`persona_id` = pa.`id`
             WHERE su.`is_deleted` = 'N' and su.`url` = `in_homeurl` and pa.`account_id` = t.`account_id`
             GROUP BY pa.`account_id`, ca.`persona_id`, ca.`channel_id`
             UNION ALL
            SELECT 'read' as `access`
              FROM `SiteUrl` su INNER JOIN `Channel` ch ON su.`site_id` = ch.`site_id`
                                INNER JOIN `Persona` pa
             WHERE su.`is_deleted` = 'N' and su.`url` = `in_homeurl` and pa.`account_id` = t.`account_id`
             GROUP BY pa.`account_id`, pa.`id`, ch.`id`
             ORDER BY `access` DESC
             LIMIT 1) as `access_level`,
           IFNULL(`reset_pass`, 'N') as `password_change`,
           IFNULL((SELECT m.`value` FROM `AccountMeta` m
                    WHERE m.`is_deleted` = 'N' and m.`key` = 'system.welcome.done'
                      and m.`account_id` = a.`id`), 'N') as `welcome_done`
      FROM `Tokens` t INNER JOIN `Account` a ON t.`account_id` = a.`id`
     WHERE a.`is_deleted` = 'N' and t.`is_deleted` = 'N'
       and t.`updated_at` >= DATE_SUB(Now(), INTERVAL `in_lifespan` DAY)
       and t.`guid` = `in_token_guid` and t.`id` = `in_token_id`
     GROUP BY a.`id`, a.`email`, a.`type`, a.`display_name`, a.`language_code`
     LIMIT 1;

    /* Get the Storage Results */
    UPDATE tmp INNER JOIN (SELECT fi.`account_id`, COUNT(fi.`id`) as `file_count`, SUM(fi.`bytes`) as `storage_used`
                             FROM `File` fi INNER JOIN `Tokens` tt ON fi.`account_id` = tt.`account_id`
                            WHERE tt.`is_deleted` = 'N' and fi.`is_deleted` = 'N' and IFNULL(fi.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE)) >= Now()
                              and tt.`id` = `in_token_id`
                            GROUP BY fi.`account_id`) fsum ON tmp.`account_id` = fsum.`account_id`
      SET tmp.`file_count` = fsum.`file_count`,
          tmp.`storage_used` = fsum.`storage_used`;

    /* Return the Processed Data */
    SELECT tmp.`account_id`, tmp.`email`, tmp.`type`, tmp.`display_name`, tmp.`language_code`, tmp.`timezone`, tmp.`avatar_url`,
           tmp.`file_count`, tmp.`storage_limit`, tmp.`storage_used`,
           `premium_until` as `premium_until`, CASE WHEN `premium_until` > Now() THEN 'Y' ELSE 'N' END as `premium_active`,
           tmp.`default_persona`, tmp.`default_channel`,
           tmp.`pref_contact_mail`, IFNULL(tmp.`access_level`, 'read') as `access_level`, tmp.`password_change`, tmp.`welcome_done`
      FROM tmp
     WHERE tmp.`account_id` > 0
     LIMIT 1;
 END ;;
DELIMITER ;