CREATE TABLE `cd_fit_data` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `EntryId` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '时间' COLLATE 'utf8_general_ci',
    `create_date` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '时间' COLLATE 'utf8_general_ci',
    `create_at` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '领取金额',
    `content` TEXT NULL DEFAULT NULL COMMENT '产品详情' COLLATE 'utf8_general_ci',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `create_at` (`create_at`) USING BTREE,
    UNIQUE INDEX `EntryId` (`EntryId`) USING BTREE,
    INDEX `create_date` (`create_date`) USING BTREE
)
COMMENT='fit数据'
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
