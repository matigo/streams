DELIMITER ;;
DROP PROCEDURE IF EXISTS FixPostMeta;;
CREATE PROCEDURE FixPostMeta( IN `in_post_id` int(11), IN `in_words` text )
BEGIN
    DECLARE `x_word_limit`  int(11);

    /** ********************************************************************** **
     *  Function fixes the PostSearch and PostMention blanks from older posts
     *
     *  Usage: CALL FixPostMeta(1, 'a,short,body,too');
     ** ********************************************************************** **/

    DECLARE EXIT HANDLER FOR SQLEXCEPTION, SQLWARNING
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    /* Mark any Files as Detached */
    DELETE FROM `PostFile` pf WHERE pf.`is_deleted` = 'N' and pf.`post_id` = `in_post_id`;

    /* Mark any Mentions as Deleted */
    DELETE FROM `PostMention` pm WHERE pm.`is_deleted` = 'N' and pm.`post_id` = `in_post_id`;

    /* Mark any Search Items as Deleted */
    DELETE FROM `PostSearch` ps WHERE ps.`is_deleted` = 'N' and ps.`post_id` = `in_post_id`;

    /* Set the Post Search Items Accordingly */
    SELECT ROUND(ROUND(LENGTH(`in_words`) / 3.25, 0), -1) + 50 as `chars` INTO `x_word_limit`;

    INSERT INTO `PostSearch` (`post_id`, `word`)
    SELECT zz.`post_id`, zz.`word`
      FROM (SELECT DISTINCT `in_post_id` as `post_id`, LOWER(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(`in_words`, ',', num.`id`), ',', -1))) as `word`
              FROM (SELECT (h*1000+t*100+u*10+v+1) as `id`
                      FROM (SELECT 0 h UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                           (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                           (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c,
                           (SELECT 0 v UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) d) num
             WHERE num.`id` <= IFNULL(`x_word_limit`, 250)) zz
     WHERE zz.`word` NOT IN ('')
     ORDER BY zz.`word`
        ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                                `updated_at` = Now();

    /* Set any Post Mentions that Might Exist */
    INSERT INTO `PostMention` (`post_id`, `persona_id`, `created_at`)
    SELECT ps.`post_id`, pa.`id` as `persona_id`, ps.`created_at`
      FROM `PostSearch` ps INNER JOIN `Persona` pa ON ps.`word` = CONCAT('@', pa.`name`)
     WHERE ps.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and ps.`post_id` = `in_post_id`
        ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                                `updated_at` = Now();

    /* Mark any Files contained in the Post as Attached */
    INSERT INTO `PostFile` (`post_id`, `file_id`, `created_at`, `updated_at`)
    SELECT po.`id` as `post_id`, fi.`id` as `file_id`, po.`created_at`, po.`updated_at`
      FROM `File` fi INNER JOIN `Post` po ON po.`is_deleted` = 'N' and po.`id` = `in_post_id`
     WHERE LOCATE(CONCAT(fi.`location`, fi.`local_name`), po.`value`) > 0
     ORDER BY po.`id`, fi.`id`
        ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                                `updated_at` = Now();

    /* Return the Post.id for this Object */
    SELECT `in_post_id` as `post_id`;
END;;
DELIMITER ;