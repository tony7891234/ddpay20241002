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


insert into `cd_fit_data` (`EntryId`, `create_at`, `create_date`, `content`) values
(0, 202935691731, 1728518400,
 {"InternalIdentifier":0,"EntryId":0,"Description":"Saldo Inicial","Subtype":21,"EntryDate":"2024-10-10T00:00:00","EntryValue":345316.72,"Type":"InitialBalance","UsedGuaranteed":null,"GuaranteedValue":null,"Details":"Saldo Inicial","ReceiptUrl":null,"BankDetails":null,"DocumentNumber":null,"TransactionId":null,"Bank":null,"BankBranch":null,"BankAccount":null,"BankAccountDigit":null,"OperationId":null,"NoteId":null,"NoteEntry":null,"OperationType":0,"ManualEntryCategory":0,"ReceiptFileName":null,"TotalRows":0,"Tags":[]}))
