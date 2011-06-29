/*!40101 SET NAMES utf8 */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40101 SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET SQL_NOTES=0 */;


DROP PROCEDURE IF EXISTS `upgrade_schema`;

DELIMITER $$

CREATE PROCEDURE `upgrade_schema`()
ret:
BEGIN
    DECLARE current_version INT DEFAULT 0;
    DECLARE target_version INT DEFAULT 3;

    SET autocommit = 0;

    -- Create settings table for users who don't have it.
    CREATE TABLE IF NOT EXISTS `settings` (
      `key` varchar(50) NOT NULL,
      `value` varchar(50) NOT NULL,
      PRIMARY KEY (`key`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

    -- Set default DB version number in case the user didn't have the table.
    INSERT IGNORE INTO `settings` (`key`, `value`)
        VALUES ('version', '0');

    -- Fetch the current version out of the database.
    SELECT `value` INTO current_version
        FROM `settings` WHERE `key` = 'version';

    -- If the current version is the target, then nothing needs to be done.
    IF current_version = target_version THEN
        SELECT CONCAT(
            'Current database version is ',
            current_version,
            ', which is the latest.  No migration is necessary.'
        ) AS ' ';
        LEAVE ret;
    END IF;

    SELECT CONCAT(
        'Current database version is ',
        current_version,
        '.  Attempting migration to version ',
        target_version,
        '.'
    ) AS ' ';

    -- Migrate paths for each version:

    IF current_version = '0' THEN
        SELECT 'Migrating 0 -> 1 ...' AS ' ';

        ALTER TABLE `submitted_work`
            ADD INDEX `dashboard_status_index2` (`time`, `worker_id`);

        UPDATE `settings` SET `value` = '1' WHERE `key` = 'version';
        COMMIT;
        SET current_version = '1';
    END IF;
    
    IF current_version = '1' THEN
        SELECT 'Migrating 1 -> 2 ...' AS ' ';

        ALTER TABLE `work_data` 
            ADD INDEX `time_requested_index` (`time_requested`);
        ALTER TABLE `work_data` 
            ADD INDEX `pool_time` (`pool_id`, `time_requested`);
        
        UPDATE `settings` SET `value` = '2' WHERE `key` = 'version';
        COMMIT;
        SET current_version = '2';
    END IF;

    IF current_version = '2' THEN
        SELECT 'Migrating 2 -> 3 ...' AS ' ';

        BEGIN
            DECLARE EXIT HANDLER FOR SQLEXCEPTION
                SELECT 'The work_data.data column cannot be shrunk because that would result in duplicate primary key values.  Please truncate the work_data table and try migrating again.' AS ' ';

            ALTER TABLE `work_data`
                CHANGE `data`
                `data` CHAR(136)
                       CHARACTER SET ascii
                       COLLATE ascii_bin
                       NOT NULL;

            UPDATE `settings` SET `value` = '3' WHERE `key` = 'version';
            COMMIT;
            SET current_version = '3';
        END;
    END IF;

    SELECT CONCAT('Final database version: ', current_version, '.') AS ' ';

    SELECT IF(current_version = target_version,
        'Database migrated successfully.',
        'Database migration did not fully complete.  Correct the errors displayed above and try again.') AS ' ';
END

$$

DELIMITER ;

CALL `upgrade_schema`();
DROP PROCEDURE `upgrade_schema`;
