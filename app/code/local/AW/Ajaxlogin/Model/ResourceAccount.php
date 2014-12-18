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


/**
 * 
 */
class AW_Ajaxlogin_Model_ResourceAccount extends Mage_Core_Model_Mysql4_Abstract {
    
    /**
     * 
     */
    protected function _construct() {
        $this->_init('ajaxlogin/account', 'id');
    }
    
    
    /**
     * 
     */
    public function loadByNetworkUID(AW_Ajaxlogin_Model_Account $model, $network, $UID) {
        $__select = $this->_getReadAdapter()
            ->select()
            ->from(
                $this->getMainTable(),
                array( $this->getIdFieldName() )
            )
            ->where('network_code=:network')
            ->where('network_account_uid=:uid')
        ;
        $__accountRecordID = $this->_getReadAdapter()->fetchOne(
            $__select,
            array(
                'network' => $network,
                'uid'     => $UID
            )
        );
        
        if ( $__accountRecordID ) {
            $this->load($model, $__accountRecordID);
        }
        else {
            $model->setData(array());
        }
        
        return $this;
    }
}