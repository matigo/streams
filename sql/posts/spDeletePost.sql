DELIMITER ;;
DROP PROCEDURE IF EXISTS DeletePost;;
CREATE PROCEDURE DeletePost( IN `token_id` int(11), IN `token_guid` varchar(64), IN `post_guid` varchar(36) )
BEGIN
    DECLARE post_id int(11);
    DECLARE site_id int(11);

    /** ********************************************************************** **
     *  Function Deletes a Post record and any Meta associated with it.
     *
     *  Usage: CALL DeletePost(424, '29b215ec-64c7-11e9-9881-54ee758049c3-f8d1-a87ff679', '00000000-0000-0000-0000-000000000000');
     ** ********************************************************************** **/

    DECLARE EXIT HANDLER FOR SQLEXCEPTION, SQLWARNING
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    /* If the Post.guid Length is Wrong, Exit */
    IF LENGTH(IFNULL(`post_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Post.guid Supplied';
    END IF;

    /* If the Token.guid Length is Wrong, Exit */
    IF LENGTH(IFNULL(`token_guid`, '')) < 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Token.guid Provided';
    END IF;

    /* If the Token.id is Bad, Exit */
    IF IFNULL(`token_id`, 0) <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Token.id Provided';
    END IF;

    /* Check that the Account has permission to Delete the Post (via their Token) */
    SELECT po.`id`, ch.`site_id` INTO `post_id`, `site_id`
      FROM `Tokens` tt INNER JOIN `Persona` pa ON tt.`account_id` = pa.`account_id`
                       INNER JOIN `Post` po ON pa.`id` = po.`persona_id`
                       INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
     WHERE tt.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and po.`is_deleted` = 'N' and ch.`is_deleted` = 'N'
       and tt.`guid` = `token_guid` and tt.`id` = `token_id` and po.`guid` = `post_guid`
     LIMIT 1;

    IF IFNULL(`post_id`, 0) <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'You Cannot Delete This Object';
    END IF;

    /* Begin a Transaction, and Remove the Records in Order */
    START TRANSACTION;

    DELETE px FROM `PostHistory` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
     WHERE po.`is_deleted` = 'N' and po.`id` = `post_id`;

    DELETE px FROM `PostSearch` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
     WHERE po.`is_deleted` = 'N' and po.`id` = `post_id`;

    DELETE px FROM `PostMention` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
     WHERE po.`is_deleted` = 'N' and po.`id` = `post_id`;

    DELETE px FROM `PostMeta` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
     WHERE po.`is_deleted` = 'N' and po.`id` = `post_id`;

    DELETE px FROM `PostMeta` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
     WHERE po.`is_deleted` = 'N' and po.`id` = `post_id`;

    DELETE px FROM `PostFile` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
     WHERE po.`is_deleted` = 'N' and po.`id` = `post_id`;

    DELETE px FROM `PostFile` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
     WHERE po.`is_deleted` = 'N' and po.`id` = `post_id`;

    DELETE px FROM `PostTags` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
     WHERE po.`is_deleted` = 'N' and po.`id` = `post_id`;

    DELETE px FROM `PostAction` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
     WHERE po.`is_deleted` = 'N' and po.`id` = `post_id`;

    DELETE px FROM `Post` px
     WHERE px.`is_deleted` = 'N' and px.`id` = `post_id`;

    /* Update the Site Version */
    UPDATE `Site`
       SET `version` = UNIX_TIMESTAMP(Now()),
           `updated_at` = Now()
     WHERE `is_deleted` = 'N' and `id` = `site_id`;

    COMMIT;

    /* Return the Channel.guid and Post ID */
    SELECT ch.`guid` as `channel_guid`, `post_id`
      FROM `Channel` ch
     WHERE ch.`is_deleted` = 'N' and ch.`site_id` = `site_id`;

END ;;
DELIMITER ;