DELIMITER ;;
DROP PROCEDURE IF EXISTS GetExportKey;;
CREATE PROCEDURE GetExportKey( IN `in_token_id` int(11), IN `in_token_guid` varchar(64) )
BEGIN

   /** ********************************************************************** **
     *  Function generates an Export Key, allowing an Export to take place.
     *
     *  Usage: CALL GetExportKey( 560, '1321c9c0-d44f-11e9-9f97-0d7e521e9ec2-f8d1-a87ff679' );
     ** ********************************************************************** **/

    INSERT INTO `AccountMeta` (`account_id`, `key`, `value`)
    SELECT acct.`id` as `account_id`, 'export.access_key' as `key`, UPPER(MD5(uuid())) as `value`
      FROM `Account` acct INNER JOIN `Tokens` tt ON acct.`id` = tt.`account_id`
     WHERE acct.`is_deleted` = 'N' and tt.`is_deleted` = 'N'
       and tt.`guid` = `in_token_guid` and tt.`id` = `in_token_id`
     LIMIT 1
        ON DUPLICATE KEY UPDATE `value` = UPPER(MD5(uuid())),
                                `updated_at` = Now();

    SELECT am.`value`
      FROM `AccountMeta` am INNER JOIN `Account` acct ON am.`account_id` = acct.`id`
                            INNER JOIN `Tokens` tt ON acct.`id` = tt.`account_id`
     WHERE acct.`is_deleted` = 'N' and am.`is_deleted` = 'N' and tt.`is_deleted` = 'N'
       and am.`key` = 'export.access_key' and am.`updated_at` >= DATE_SUB(Now(), INTERVAL 15 SECOND)
       and tt.`guid` = `in_token_guid` and tt.`id` = `in_token_id`
     LIMIT 1;
END;;
DELIMITER ;