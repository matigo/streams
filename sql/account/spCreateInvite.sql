DELIMITER ;;
DROP PROCEDURE IF EXISTS CreateInvite;;
CREATE PROCEDURE CreateInvite( IN `in_account_id` int(11) )
BEGIN
    DECLARE `x_invite_id`   int(11);

    /** ********************************************************************** **
     *  Function attempts to create an invitation code for the current account
     *      and provides it back as a string.
     *
     *  Usage: CALL CreateInvite(1);
     ** ********************************************************************** **/

    /* If the Login Name is bad, Exit */
    IF IFNULL(`in_account_id`, 0) <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Account Identifier Provided';
    END IF;

    /* Determine if the Credentials are any good */
    DROP TEMPORARY TABLE IF EXISTS tmp;
    CREATE TEMPORARY TABLE tmp AS
    SELECT acct.`id` as `account_id`, 
           DATEDIFF(CURRENT_TIMESTAMP, acct.`created_at`) as `account_days`,
           SUM(txn.`payment_gross`) as `pp_gross`,
           UNIX_TIMESTAMP(MAX(txn.`received_at`)) as `pp_recent_unix`,
           DATEDIFF(CURRENT_TIMESTAMP, MAX(txn.`received_at`)) as `pp_days`,
           tmp.`posts_365`, tmp.`posts_90`,
           CASE WHEN acct.`type` IN ('account.global', 'account.admin') THEN 'Y'
                WHEN acct.`created_at` <= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 10 YEAR) THEN 'Y'
                WHEN DATEDIFF(CURRENT_TIMESTAMP, acct.`created_at`) < 180 THEN 'N'
                WHEN COUNT(inv.`id`) >= 3 THEN 'N'

                WHEN DATEDIFF(CURRENT_TIMESTAMP, MAX(txn.`received_at`)) <= 366 THEN 'Y'
                WHEN SUM(txn.`payment_gross`) > 250 THEN 'Y'

                WHEN IFNULL(tmp.`posts_90`, 0) < 30 THEN 'N'
                ELSE 'N' END as `can_invite`,
           COUNT(inv.`id`) as `invites_created`,
           COUNT(DISTINCT inv.`consumed_by`) as `invites_accepted`
      FROM `Account` acct LEFT OUTER JOIN `PayPalTXN` txn ON acct.`id` = txn.`account_id` AND txn.`is_deleted` = 'N'
                                                         AND txn.`type` = 'subscr_payment' AND txn.`status` = 'Completed'
                                                         AND txn.`payment_gross` > 5
                          LEFT OUTER JOIN `AccountInvite` inv ON acct.`id` = inv.`account_id` AND inv.`is_deleted` = 'N'
                                                         AND DATEDIFF(CURRENT_TIMESTAMP, inv.`created_at`) < 365
                          LEFT OUTER JOIN (SELECT pa.`account_id`,
                                                  COUNT(DISTINCT CASE WHEN po.`created_at` >= DATE_SUB(CURRENT_DATE, INTERVAL 365 DAY) THEN po.`id` ELSE NULL END) as `posts_365`,
                                                  COUNT(DISTINCT CASE WHEN po.`created_at` >= DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY) THEN po.`id` ELSE NULL END) as `posts_90`
                                             FROM `Post` po INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                                            WHERE po.`is_deleted` = 'N' and pa.`is_deleted` = 'N' 
                                              and IFNULL(po.`expires_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 5 MINUTE)) >= CURRENT_TIMESTAMP
                                              and po.`created_at` >= DATE_SUB(CURRENT_DATE, INTERVAL 370 DAY)
                                              and pa.`account_id` = `in_account_id`
                                            GROUP BY pa.`account_id`) tmp ON acct.`id` = tmp.`account_id`
     WHERE acct.`is_deleted` = 'N' and acct.`id` = `in_account_id`
     GROUP BY acct.`id` ORDER BY acct.`id` LIMIT 1;

    IF (SELECT COUNT(`account_id`) FROM `tmp`) <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Account Id Returned';
    END IF;

    /* Insert a record into the Invitation table if applicable */
    INSERT INTO `AccountInvite` (`account_id`, `key`, `comment`, `guid`)
    SELECT acct.`id` as `account_id`,
           SHA2(CONCAT(acct.`email`, DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d %H:%i:%s'), ROUND(RAND() * 1000000)), 512) as `key`,
           CASE WHEN LENGTH('') > 0 THEN '' ELSE NULL END as `comment`,
           uuid() as `guid`
      FROM `Account` acct INNER JOIN tmp ON acct.`id` = tmp.`account_id`
     WHERE acct.`is_deleted` = 'N' and tmp.`can_invite` = 'Y'
     LIMIT 1;

    SELECT LAST_INSERT_ID() INTO `x_invite_id`;

    /* Return the Invitation Information */
    SELECT inv.`id`, inv.`guid`, inv.`key`, inv.`comment`, inv.`consumed_by`, inv.`consumed_at`,
           UNIX_TIMESTAMP(inv.`created_at`) as `created_unix`, UNIX_TIMESTAMP(inv.`updated_at`) as `updated_unix`
      FROM `Account` acct INNER JOIN `AccountInvite` inv ON acct.`id` = inv.`account_id`
     WHERE acct.`is_deleted` = 'N' and inv.`is_deleted` = 'N' and inv.`id` = `x_invite_id`
     LIMIT 1;

END ;;
DELIMITER ;