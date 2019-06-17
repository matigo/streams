DELIMITER ;;
DROP PROCEDURE IF EXISTS SetPayPalTXN;;
CREATE PROCEDURE SetPayPalTXN( IN `in_subject` varchar(64), IN `in_received_at` varchar(20), IN `in_type` varchar(64), IN `in_status` varchar(64), IN `in_ipn_track_id` varchar(64),
                               IN `in_subscr_id` varchar(64), IN `in_first_name` varchar(80), IN `in_last_name` varchar(80), IN `in_payer_id` varchar(64), IN `in_payer_email` varchar(120),
                               IN `in_payer_status` varchar(64), IN `in_res_country` varchar(64), IN `in_verify_sign` varchar(80), IN `in_txn_id` varchar(80),
                               IN `in_payment_gross` decimal(12,4), IN `in_payment_fee` decimal(12,4), IN `in_mc_fee` decimal(12,4), IN `in_mc_gross` decimal(12,4),
                               IN `in_recurring` char(1), IN `in_site_id` int(11) )
BEGIN
    DECLARE `x_account_id`  int(11);
    DECLARE `x_txn_id`      int(11);

    /** ********************************************************************** **
     *  Function records a PayPal Transaction to the PayPalTXN table and returns
     *      the Transaction ID and GUID if Successful.
     *
     *  Usage: CALL SetPayPalTXN( 'Test Subscription', '2019-03-23 00:00:00', 'subscr_payment', 'Completed', '0000000000000',
                                  'A-123456789ABC', 'Tester', 'Testington', '1234567890ABC', 'none@noaddy.com',
                                  'unverified', 'US', 'CorrectHorseBatteryStaple', '123456789ABCDEFGH',
                                  999, 15.15, 15.15, 999, 'N', 1 );
     ** ********************************************************************** **/

    /* Check to see if this is a duplicate transaction */
    SELECT txn.`id` INTO `x_txn_id`
      FROM `PayPalTXN` txn
     WHERE txn.`is_deleted` = 'N' and txn.`txn_id` = `in_txn_id` and txn.`verify_sign` = `in_verify_sign`
     ORDER BY txn.`id` DESC
     LIMIT 1;

    /* Determine the Account ID (Including Deleted Accounts) */
    SELECT tmp.`account_id` INTO `x_account_id`
      FROM (SELECT am.`account_id`
              FROM `AccountMeta` am
             WHERE am.`is_deleted` = 'N' and am.`key` = 'paypal.payer_id' and am.`value` = LOWER(IFNULL(`in_payer_id`, ''))
             UNION ALL
            SELECT am.`account_id`
              FROM `AccountMeta` am
             WHERE am.`is_deleted` = 'N' and am.`key` = 'paypal.payer_email' and am.`value` = LOWER(IFNULL(`in_payer_email`, ''))
               and IFNULL(`in_payer_email`, '') <> ''
             UNION ALL
            SELECT txn.`account_id`
              FROM `PayPalTXN` txn
             WHERE txn.`is_deleted` = 'N' and txn.`payer_id` = IFNULL(`in_payer_id`, '')
             UNION ALL
            SELECT pa.`account_id` FROM `streams`.`Persona` pa
             WHERE pa.`email` = LOWER(IFNULL(`in_payer_email`, '')) and IFNULL(`in_payer_email`, '') <> ''
             UNION ALL
            SELECT acct.`id` as `account_id` FROM `streams`.`Account` acct
             WHERE acct.`email` = LOWER(IFNULL(`in_payer_email`, '')) and IFNULL(`in_payer_email`, '') <> ''
             LIMIT 1) tmp;

    /* If we have an Account.id and a PayPal Email address and Payer Id, Record/Update the AccountMeta value */
    IF IFNULL(`x_account_id`, 0) > 0 THEN
        INSERT INTO `AccountMeta` (`account_id`, `key`, `value`)
        SELECT tmp.`account_id`, tmp.`key`, tmp.`value`
          FROM (SELECT `x_account_id` as `account_id`, 'paypal.payer_id' as `key`, LOWER(IFNULL(`in_payer_id`, '')) as `value`
                 UNION ALL
                SELECT `x_account_id` as `account_id`, 'paypal.payer_email' as `key`, LOWER(IFNULL(`in_payer_email`, '')) as `value`) tmp
         WHERE tmp.`value` <> ''
            ON DUPLICATE KEY UPDATE `value` = tmp.`value`;
    END IF;

    /* Write the Transaction to the PayPalTXN Table */
    IF IFNULL(`x_txn_id`, 0) <= 0 THEN
        INSERT INTO `PayPalTXN` (`subject`, `received_at`,
                                 `type`, `status`, `ipn_track_id`,
                                 `subscr_id`, `first_name`, `last_name`, `payer_id`, `payer_email`, `payer_status`,
                                 `res_country`,
                                 `verify_sign`, `txn_id`,
                                 `account_id`,
                                 `site_id`,
                                 `payment_gross`, `payment_fee`, `mc_fee`, `mc_gross`, `is_recurring`)
        SELECT LEFT(IFNULL(`in_subject`, ''), 64) as `subject`, DATE_FORMAT(`in_received_at`, '%Y-%m-%d %H:%i:%s') as `received_at`,
               LEFT(IFNULL(`in_type`, ''), 64) as `type`, LEFT(IFNULL(`in_status`, ''), 64) as `payer_status`, LEFT(IFNULL(`in_ipn_track_id`, ''), 64) as `ipn_track_id`,
               LEFT(IFNULL(`in_subscr_id`, ''), 64) as `subscr_id`, LEFT(IFNULL(`in_first_name`, ''), 80) as `first_name`, LEFT(IFNULL(`in_last_name`, ''), 80) as `last_name`,
               LEFT(IFNULL(`in_payer_id`, ''), 64) as `payer_id`, LOWER(LEFT(IFNULL(`in_payer_email`, ''), 120)) as `payer_email`, LEFT(IFNULL(`in_payer_status`, ''), 64) as `payer_status`,
               CASE WHEN IFNULL(`in_res_country`, '') <> '' THEN LEFT(`in_res_country`, 64) ELSE NULL END as `res_country`,
               LEFT(IFNULL(`in_verify_sign`, ''), 80) as `verify_sign`, LEFT(IFNULL(`in_txn_id`, ''), 80) as `txn_id`,
               CASE WHEN IFNULL(`x_account_id`, 0) > 0 THEN `x_account_id` ELSE NULL END as `account_id`,
               CASE WHEN IFNULL(`in_site_id`, 0) > 0 THEN `in_site_id` ELSE NULL END as `site_id`,
               IFNULL(`in_payment_gross`, 0) as `payment_gross`, IFNULL(`in_payment_fee`, 0) as `payment_fee`, IFNULL(`in_mc_fee`, 0) as `mc_fee`, IFNULL(`in_mc_gross`, 0) as `mc_gross`,
               CASE WHEN IFNULL(`in_recurring`, '') = 'Y' THEN 'Y' ELSE 'N' END as `is_recurring`;
        SELECT LAST_INSERT_ID() INTO `x_txn_id`;
    END IF;

    /* Output the PayPalTXN.id and PayPalTXN.guid Values */
    SELECT txn.`id` as `txn_id`, txn.`guid` as `guid`
      FROM `PayPalTXN` txn
     WHERE txn.`id` = `x_txn_id`;

END;;
DELIMITER ;