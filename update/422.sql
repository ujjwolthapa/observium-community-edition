ALTER TABLE `winservices` CHANGE `display-name` `displayname` VARCHAR(96) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, CHANGE `State` `state` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, CHANGE `StartMode` `startmode` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `winservices` CHANGE `winsrv_id` `winsvc_id` INT(11) NOT NULL AUTO_INCREMENT;