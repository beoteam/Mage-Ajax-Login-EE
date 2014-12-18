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
class AW_Ajaxlogin_Model_AuthorizationAbstract extends Varien_Object {
    
    /**
     * 
     */
    const PARAMKEY_OAUTH_EXPIRES_IN      = 'oauth_expires_in';
    const PARAMKEY_OAUTH_AUTH_EXPIRES_IN = 'oauth_authorization_expires_in';
    
    const E_MSG_NOREQUEST                = 'No request instance is assigned to authorization model';
    const E_MSG_NOTOKEN                  = 'Failed to get an access token';
    const E_MSG_NOEMAIL                  = 'Unable to fetch email address from social network account';
    const E_MSG_NOUID                    = 'Unable to fetch UID from social network account';
    const E_MSG_HTTPCLIENTERROR          = 'An error occured when making HTTP request to API';
    const E_MSG_UIDNOTASSOCIATED         = 'No store customers associated with network account';
    
    
    /**
     * 
     */
    public function register() {
        return true;
    }
    
    
    /**
     * 
     */
    protected function __attemptToLoginCustomerEmail($email) {
        $__customer = Mage::getModel('customer/customer');
        $__customer
            ->setWebsiteId(Mage::app()->getWebsite()->getId())
            ->loadByEmail($email)
        ;
        
        return $this->__attemptToLoginCustomer($__customer);
    }
    
    
    /**
     * 
     */
    protected function __attemptToLoginCustomerID($customerID) {
        $__customer = Mage::getModel('customer/customer');
        $__customer
            ->setWebsiteId(Mage::app()->getWebsite()->getId())
            ->load($customerID)
        ;
        
        return $this->__attemptToLoginCustomer($__customer);
    }
    
    
    /**
     * 
     */
    private function __attemptToLoginCustomer($customer) {
        if ( ($customer) and (is_a($customer, 'Varien_Object')) and ($customer->getId()) ) {
            if ($customer->getConfirmation() && $customer->isConfirmationRequired()) {
                $__value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
                throw new Exception(Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $__value));
            }
            else {
                $__session = Mage::getSingleton('customer/session');
                $__session->setCustomerAsLoggedIn($customer);
                
                /* CE 1.7.* and above */
                if ( method_exists($__session, 'renewSession') ) $__session->renewSession();
                
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * 
     */
    protected function __getCurrentCustomerID() {
        return Mage::getSingleton('customer/session')->getCustomer()->getId();
    }
    
    
    /**
     * 
     */
    protected function __getCurrentGMT() {
        return Mage::getSingleton('core/date')->gmtDate();
    }
}