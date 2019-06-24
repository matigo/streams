DELIMITER ;;
DROP PROCEDURE IF EXISTS PerformDirectLogin;;
CREATE PROCEDURE PerformDirectLogin( IN `in_account_id` int(11) )
BEGIN
    DECLARE `x_client_guid` char(36);
    DECLARE `x_token_id`    int(11);

    /** ********************************************************************** **
     *  Function performs a direct login to generate a valid Token for use by
     *      the account.
     *
     *  Usage: CALL PerformDirectLogin( 88 );
     ** ********************************************************************** **/

    /* If the Password is bad, Exit */
    IF IFNULL((SELECT z.`is_deleted` FROM `Account` z WHERE z.`is_deleted` = 'N' and z.`id` = `in_account_id`), 'Y') = 'Y' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Account.id Provided';
    END IF;

    /* Get the Default Client.guid Value */
    SELECT cl.`guid` INTO `x_client_guid`
      FROM `Client` cl
     WHERE cl.`is_deleted` = 'N'
     ORDER BY cl.`id`
     LIMIT 1;

    /* Determine if the Credentials are any good */
    DROP TEMPORARY TABLE IF EXISTS tmp;
    CREATE TEMPORARY TABLE tmp AS
    SELECT acct.`id` as `account_id`, acct.`type`, acct.`display_name`, acct.`language_code`,
           CAST(0 AS UNSIGNED) as `persona_count`, CAST('Y' AS CHAR(1)) as `can_read`, CAST('N' AS CHAR(1)) as `can_write`,
           CASE WHEN acct.`type` = 'account.admin' THEN 0
                ELSE DATEDIFF(Now(), IFNULL((SELECT max(tt.`updated_at`) FROM `Tokens` tt WHERE tt.`account_id` = acct.`id`), Now())) END as `last_activity`
      FROM `Account` acct
     WHERE acct.`is_deleted` = 'N' and acct.`type` IN ('account.admin', 'account.normal') and acct.`id` = `in_account_id`
     LIMIT 1;

    IF (SELECT COUNT(`account_id`) FROM `tmp`) <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Account.id Provided';
    END IF;

    /* Determine the Channel Permissions */
    UPDATE tmp INNER JOIN (SELECT pa.`account_id`, COUNT(pa.`id`) as `persona_count`, 'Y' as `can_read`, 'N' as `can_write`
                             FROM `Persona` pa
                            WHERE pa.`is_deleted` = 'N' and pa.`account_id` = `in_account_id`
                            GROUP BY pa.`account_id`
                            ORDER BY `persona_count` DESC LIMIT 1) perms ON tmp.`account_id` = perms.`account_id`
       SET tmp.`persona_count` = perms.`persona_count`,
           tmp.`can_write` = perms.`can_write`,
           tmp.`can_read` = perms.`can_read`;

    /* Create the Token Record */
    INSERT INTO `Tokens` (`guid`, `account_id`, `client_id`)
    SELECT CONCAT(uuid(), '-', LEFT(md5(a.`email`), 4), '-', LEFT(md5(count(p.`id`) + a.`id`), 8)) as `guid`, a.`id` as `account_id`, c.`id` as `client_id`
      FROM `Account` a INNER JOIN `Persona` p ON a.`id` = p.`account_id`
                       INNER JOIN `Client` c
     WHERE p.`is_deleted` = 'N' and c.`is_deleted` = 'N' and a.`is_deleted` = 'N'
       and a.`type` IN ('account.admin', 'account.normal') and c.`guid` = `x_client_guid`
       and a.`id` = `in_account_id`
     GROUP BY a.`email`, a.`id`, c.`id`
     LIMIT 1;
    SELECT LAST_INSERT_ID() INTO `x_token_id`;

    /* Return the Token Information */
    SELECT tt.`id` as `token_id`, tt.`guid` as `token_guid`,
           tt.`account_id`, tmp.`type` as `account_type`, tmp.`display_name`, tmp.`language_code`, tmp.`persona_count`,
           tmp.`can_read`, tmp.`can_write`, tmp.`last_activity`
      FROM `Tokens` tt INNER JOIN tmp ON tt.`account_id` = tmp.`account_id`
     WHERE tt.`is_deleted` = 'N' and tt.`id` = `x_token_id`
     LIMIT 1;

END ;;
DELIMITER ;