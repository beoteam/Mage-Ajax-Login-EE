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
class AW_Ajaxlogin_Block_AuthorizationFormLogin extends Mage_Customer_Block_Form_Login {
    
    /**
     * 
     */
    protected function _prepareLayout() {
        /*
        $__headBlock = $this->getLayout()->getBlock('head');
        if ( $__headBlock ) $__headBlock->setTitle(Mage::helper('customer')->__('Customer Login'));
        */
        
        return $this;
    }
    
    
    /**
     * 
     */
    public function getChildren() {
        return $this->_children;
    }
    
    
    /**
     *
     */
    public function getLoginActionUrl() {
        return Mage::helper('ajaxlogin/data')->getUrlSafeForAjax('ajaxlogin/index/loginPost');
    }
    
    
    /**
     *
     */
    public function getLogoutActionUrl() {
        return Mage::helper('ajaxlogin/data')->getUrlSafeForAjax('ajaxlogin/index/logoutPost');
    }
    
    
    /**
     * 
     */
    public function isCustomerLoggedIn() {
        return $this->_getSession()->isLoggedIn();
    }
    
    
    /**
     * 
     */
    protected function _getSession() {
        return Mage::getSingleton('customer/session');
    }
}