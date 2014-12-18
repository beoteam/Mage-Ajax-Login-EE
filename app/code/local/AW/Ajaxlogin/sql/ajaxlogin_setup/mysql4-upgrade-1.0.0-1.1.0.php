<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento enterprise edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Ajaxlogin
 * @version    1.1.3
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


$__installer = $this;
$__installer->startSetup();

/**
 * Existing "_getColumnDefinition" method indicates the expanded
 * version of Varien Mysql PDO adapter implementation
 * 
 * Expanded version appeared in 1.6.x and is 100% suitable for
 * object-oriented DB manipulations
 */

try {
    /**
     * Creating table `aw_ajaxlogin_account`
     */
    if ( method_exists($__installer->getConnection(), '_getColumnDefinition') ) {
        $__table = $__installer->getConnection()
            ->newTable($__installer->getTable('ajaxlogin/account'))
            ->addColumn(
                'id',
                Varien_Db_Ddl_Table::TYPE_INTEGER,
                11,
                array( 'identity' => true, 'nullable' => false, 'primary' => true ),
                'Account instance ID'
            )
            ->addColumn(
                'network_code',
                Varien_Db_Ddl_Table::TYPE_VARCHAR,
                32,
                array( 'nullable' => false ),
                'Code name of the social network'
            )
            ->addColumn(
                'customer_id',
                Varien_Db_Ddl_Table::TYPE_INTEGER,
                10,
                array( 'nullable' => false, 'unsigned' => true ),
                'ID of the local customer account'
            )
            ->addColumn(
                'network_account_uid',
                Varien_Db_Ddl_Table::TYPE_VARCHAR,
                64,
                array( 'identity' => false, 'nullable' => true, 'primary' => false ),
                'ID of the customer account at the social network'
            )
            ->addColumn(
                'creation_time',
                Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
                null,
                array(),
                'Account instance creation time'
            )
            ->setComment('Ajax Login module (aheadWorks), Account table')
        ;
        
        $__installer->getConnection()->createTable($__table);
        $__installer->getConnection()->addConstraint(
            'FK_AJAXLOGIN_ACCOUNT_CUSTOMER_ID',
            $__installer->getTable('ajaxlogin/account'),
            'customer_id',
            $__installer->getTable('customer/entity'),
            'entity_id'
        );
    }
    else {
        $__installer->run("
            CREATE TABLE IF NOT EXISTS `" . $__installer->getTable('ajaxlogin/account') . "` (
                `id`                  int(11)              NOT NULL AUTO_INCREMENT COMMENT 'Account instance ID',
                `network_code`        varchar(32)          NOT NULL                COMMENT 'Code name of the social network',
                `customer_id`         int(10)     UNSIGNED NOT NULL                COMMENT 'ID of the local customer account',
                `network_account_uid` int(11)      DEFAULT     NULL                COMMENT 'ID of the customer account at the social network',
                `creation_time`       timestamp                                    COMMENT 'Account instance creation time',
                PRIMARY KEY (`id`),
                FOREIGN KEY (`customer_id`) REFERENCES `" . $__installer->getTable('customer/entity') . "` (`entity_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Ajax Login module (aheadWorks), Account table' AUTO_INCREMENT=1;
        ");
    }
}
catch (Exception $__E) {
    Mage::logException($__E);
}


$__installer->endSetup();