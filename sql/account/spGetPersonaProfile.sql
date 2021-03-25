DELIMITER ;;
DROP PROCEDURE IF EXISTS GetPersonaProfile;;
CREATE PROCEDURE GetPersonaProfile( IN `in_name` varchar(40) )
BEGIN
    DECLARE `x_persona_id`  int(11);

    /** ********************************************************************** **
     *  Function collects the public profile information for a given persona.
     *
     *  Usage: CALL GetPersonaProfile( 'matigo' );
     ** ********************************************************************** **/

    /* If the Persona Name is bad, Exit */
    IF LENGTH(IFNULL(`in_name`, '')) <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Name Provided';
    END IF;

    /* Construct the primary temporary table that will be used to collect the output to be returned */
    DROP TEMPORARY TABLE IF EXISTS `summary`;
    CREATE TEMPORARY TABLE IF NOT EXISTS `summary` (
        `persona_id`    int(11)        UNSIGNED     NOT NULL    ,

        `name`          varchar(40)                 NOT NULL    ,
        `last_name`     varchar(80)                 NOT NULL    DEFAULT '',
        `first_name`    varchar(80)                 NOT NULL    DEFAULT '',
        `display_name`  varchar(80)                 NOT NULL    DEFAULT '',
        `bio`           varchar(2048)               NOT NULL    DEFAULT '',
        `site_url`      varchar(512)                NOT NULL    ,

        `avatar_img`    varchar(120)                NOT NULL    DEFAULT 'default.png',
        `guid`          char(36)                    NOT NULL    ,
        `created_at`    timestamp                   NOT NULL    ,

        `followers`     int(11)        UNSIGNED     NOT NULL    DEFAULT 0,
        `following`     int(11)        UNSIGNED     NOT NULL    DEFAULT 0,

        `posts`         int(11)        UNSIGNED     NOT NULL    DEFAULT 0,
        `notes`         int(11)        UNSIGNED     NOT NULL    DEFAULT 0,
        `articles`      int(11)        UNSIGNED     NOT NULL    DEFAULT 0,
        `bookmarks`     int(11)        UNSIGNED     NOT NULL    DEFAULT 0,
        `locations`     int(11)        UNSIGNED     NOT NULL    DEFAULT 0,
        `quotations`    int(11)        UNSIGNED     NOT NULL    DEFAULT 0,
        `photos`        int(11)        UNSIGNED     NOT NULL    DEFAULT 0,
        `pins`          int(11)        UNSIGNED     NOT NULL    DEFAULT 0,
        `stars`         int(11)        UNSIGNED     NOT NULL    DEFAULT 0,
        `points`        int(11)        UNSIGNED     NOT NULL    DEFAULT 0,

        `years_active`  varchar(4096)                   NULL    ,

        `first_at`      timestamp                       NULL    ,
        `recent_at`     timestamp                       NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    /* Collect the Base Data */
    INSERT INTO `summary` (`persona_id`, `name`, `last_name`, `first_name`, `display_name`, `bio`, `site_url`, `avatar_img`, `guid`, `created_at`)
    SELECT pa.`id` as `persona_id`, pa.`name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pm.`value` as `bio`, tmp.`site_url`,
           pa.`avatar_img`, pa.`guid`, pa.`created_at`
      FROM `Persona` pa LEFT OUTER JOIN `PersonaMeta` pm ON pa.`id` = pm.`persona_id` AND pm.`key` = 'persona.bio' AND pm.`is_deleted` = 'N'
                        LEFT OUTER JOIN (SELECT pm.`persona_id`, su.`site_id`, CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `site_url`
                                           FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                                                             INNER JOIN `PersonaMeta` pm ON si.`id` = CAST(pm.`value` AS UNSIGNED) AND pm.`key` = 'site.default'
                                                             INNER JOIN `Persona` pa ON pm.`persona_id` = pa.`id`
                                          WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and pm.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
                                            and su.`is_active` = 'Y' and pa.`name` = `in_name`
                                          ORDER BY su.`site_id`
                                          LIMIT 1) tmp ON pa.`id` = tmp.`persona_id`
     WHERE pa.`is_deleted` = 'N' and pa.`name` = `in_name`
     LIMIT 1;

    /* Put the Persona.id value into a variable so we can properly update the temporary table without a circular reference error */
    SELECT z.`persona_id` INTO `x_persona_id`
      FROM `summary` z
     LIMIT 1;

    /* Update the Temporary table for the Post stats (this is not done as part of the first query due to a 490ms lookup penalty) */
    UPDATE `summary` src INNER JOIN (SELECT po.`persona_id`,
                                            COUNT(po.`id`) as `posts`,
                                            COUNT(DISTINCT CASE WHEN po.`type` = 'post.note' THEN po.`id` ELSE NULL END) as `notes`,
                                            COUNT(DISTINCT CASE WHEN po.`type` = 'post.article' THEN po.`id` ELSE NULL END) as `articles`,
                                            COUNT(DISTINCT CASE WHEN po.`type` = 'post.bookmark' THEN po.`id` ELSE NULL END) as `bookmarks`,
                                            COUNT(DISTINCT CASE WHEN po.`type` = 'post.location' THEN po.`id` ELSE NULL END) as `locations`,
                                            COUNT(DISTINCT CASE WHEN po.`type` = 'post.quotation' THEN po.`id` ELSE NULL END) as `quotations`,
                                            COUNT(DISTINCT CASE WHEN po.`type` = 'post.photo' THEN po.`id` ELSE NULL END) as `photos`,
                                            MIN(po.`created_at`) as `first_at`, MAX(po.`created_at`) as `recent_at`
                                       FROM `Post` po
                                      WHERE po.`is_deleted` = 'N' and IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 5 SECOND)) >= Now()
                                        and po.`persona_id` = `x_persona_id`
                                      GROUP BY po.`persona_id`) tmp ON src.`persona_id` = tmp.`persona_id`
       SET src.`posts` = tmp.`posts`,
           src.`notes` = tmp.`notes`,
           src.`articles` = tmp.`articles`,
           src.`bookmarks` = tmp.`bookmarks`,
           src.`locations` = tmp.`locations`,
           src.`quotations` = tmp.`quotations`,
           src.`photos` = tmp.`photos`,
           src.`first_at` = tmp.`first_at`,
           src.`recent_at` = tmp.`recent_at`
     WHERE src.`persona_id` = `x_persona_id`;

    /* Update the Temporary table for the Action stats */
    UPDATE `summary` src INNER JOIN (SELECT act.`persona_id`,
                                            COUNT(DISTINCT CASE WHEN act.`pin_type` <> 'pin.none' THEN act.`post_id` ELSE NULL END) as `pins`,
                                            COUNT(DISTINCT CASE WHEN act.`is_starred` = 'Y' THEN act.`post_id` ELSE NULL END) as `stars`,
                                            COUNT(DISTINCT CASE WHEN act.`points` > 0 THEN act.`post_id` ELSE NULL END) as `points`
                                       FROM `PostAction` act
                                      WHERE act.`is_deleted` = 'N' and act.`persona_id` = `x_persona_id`
                                      GROUP BY act.`persona_id`) tmp ON src.`persona_id` = tmp.`persona_id`
       SET src.`pins` = tmp.`pins`,
           src.`stars` = tmp.`stars`,
           src.`points` = tmp.`points`
     WHERE src.`persona_id` = `x_persona_id`;

    /* Update the Temporary table for the Follow counts */
    UPDATE `summary` src INNER JOIN (SELECT COUNT(DISTINCT CASE WHEN pr.`persona_id` = 1 THEN pr.`related_id` ELSE NULL END) as `following`,
                                            COUNT(DISTINCT CASE WHEN pr.`persona_id` <> `x_persona_id` THEN pr.`persona_id` ELSE NULL END) as `followers`
                                       FROM `PersonaRelation` pr INNER JOIN `Persona` pa ON pr.`persona_id` = pa.`id`
                                                                 INNER JOIN `Account` acct ON pa.`account_id` = acct.`id`
                                      WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and pr.`is_deleted` = 'N'
                                        and pr.`follows` = 'Y' and `x_persona_id` IN (pr.`persona_id`, pr.`related_id`)) tmp ON tmp.`following` >= 0
       SET src.`following` = tmp.`following`,
           src.`followers` = tmp.`followers`
     WHERE src.`persona_id` = `x_persona_id`;

    /* Collect the Years Active information */
    UPDATE `summary` src INNER JOIN (SELECT GROUP_CONCAT(CONCAT('"', z.`year`, '": ', z.`posts`)) as stats
                                       FROM (SELECT YEAR(po.`publish_at`) as `year`, COUNT(po.`id`) as `posts`
                                               FROM `Post` po
                                              WHERE po.`is_deleted` = 'N' and po.`persona_id` = `x_persona_id`
                                              GROUP BY YEAR(po.`publish_at`)
                                              ORDER BY `year` DESC ) z ) tmp ON tmp.`stats` IS NOT NULL
       SET src.`years_active` = tmp.`stats`
     WHERE src.`persona_id` = `x_persona_id`;

    /* Now let's return the summarized data */
    SELECT `persona_id`, `name`, `last_name`, `first_name`, `display_name`, `bio`, `site_url`, `avatar_img`, `guid`, `created_at`,
           `posts`, `notes`, `articles`, `bookmarks`, `locations`, `quotations`, `photos`, `pins`, `stars`, `points`, `following`, `followers`,
           CONCAT('{', `years_active`, '}') as `years_active`, `first_at`, `recent_at`
      FROM `summary` src
     WHERE src.`persona_id` = `x_persona_id`
     LIMIT 1;
      DROP TEMPORARY TABLE `summary`;

END ;;
DELIMITER ;