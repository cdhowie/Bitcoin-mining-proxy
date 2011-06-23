/*!40101 SET NAMES utf8 */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40101 SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET SQL_NOTES=0 */;


DROP PROCEDURE IF EXISTS `upgrade_schema`;

DELIMITER $$

CREATE PROCEDURE `upgrade_schema`()
ret:
BEGIN
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

    -- Get current DB version.
    SELECT @version := `value` FROM `settings` WHERE `key` = 'version';

    -- Upgrade paths for each version:

    IF @version = '0' THEN
        ALTER TABLE `submitted_work`
            ADD INDEX `dashboard_status_index2` (`time`, `worker_id`);

        COMMIT;
        SET @version = '1';
    END IF;
    
    IF @version = '1' THEN
        ALTER TABLE `work_data` 
            ADD INDEX `time_requested_index` (`time_requested`);
        ALTER TABLE `work_data` 
            ADD INDEX `pool_time` (`pool_id`, `time_requested`);
        
        COMMIT;
        SET @version = '2';
    END IF;

    -- Store updated version.
    UPDATE `settings` SET `value` = @version WHERE `key` = 'version';
    COMMIT;

    -- Message for the console.
    SELECT 'Database upgraded successfully.';
END

$$

DELIMITER ;

CALL `upgrade_schema`();
DROP PROCEDURE `upgrade_schema`;
