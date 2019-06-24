DELIMITER ;;
DROP PROCEDURE IF EXISTS PerformLogout;;
CREATE PROCEDURE PerformLogout( IN `in_token_id` int(1), IN `in_token_guid` varchar(64) )
BEGIN

    /** ********************************************************************** **
     *  Function marks an active Token as expired.
     *
     *  Usage: CALL PerformLogout(541, 'caf0f594-9660-11e9-af41-92a1745f8169-f8d1-a87ff679');
     ** ********************************************************************** **/

    /* Update the Token Record */
    UPDATE `Tokens` tt INNER JOIN `Account` acct ON tt.`account_id` = acct.`id`
       SET tt.`is_deleted` = 'Y'
     WHERE acct.`is_deleted` = 'N' and tt.`is_deleted` = 'N'
       and tt.`id` = `in_token_id` and tt.`guid` = `in_token_guid`;

    /* Return the Token Status */
    SELECT tt.`id` as `token_id`, tt.`guid` as `token_guid`, tt.`is_deleted`
      FROM `Tokens` tt INNER JOIN `Account` acct ON tt.`account_id` = acct.`id`
     WHERE acct.`is_deleted` = 'N' and tt.`id` = `in_token_id`
     LIMIT 1;

END ;;
DELIMITER ;