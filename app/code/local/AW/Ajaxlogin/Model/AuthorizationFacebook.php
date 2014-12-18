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
class AW_Ajaxlogin_Model_AuthorizationFacebook extends AW_Ajaxlogin_Model_AuthorizationAbstract {
    
    /**
     * 
     */
    const CODENAME        = 'facebook';
    const API_MODEL       = 'ajaxlogin/facebook';
    
    const E_MSG_NOREQUEST = 'No request instance is assigned to authorization model';
    
    
    /**
     * 
     */
    public function isProperlyConfigured() {
        return Mage::helper('ajaxlogin/adminhtml')->isFacebookConfigured();
    }
    
    
    /**
     * 
     */
    public function getCustomerInformation() {
        if ( !$this->hasData('customer_information') ) {
            $this->__loadCustomerInformation();
        }
        
        return $this->getData('customer_information');
    }
    
    
    /**
     * 
     */
    public function login() {
        if ( !$this->getRequest() ) {
            throw new Exception(self::E_MSG_NOREQUEST);
        }
        
        $__accessToken = $this->getRequest()->getPost('accessToken');
        
        $__api = Mage::getModel(self::API_MODEL);
        if ( $__accessToken ) {
            $__api->setAccessToken($__accessToken);
        }
        $__api->setAppId(Mage::getStoreConfig('ajaxlogin/login_with_facebook_account/application_id'));
        $__api->setAppSecret(Mage::getStoreConfig('ajaxlogin/login_with_facebook_account/application_secret'));
        $__facebookUserID = $__api->getUser();
        
        if ( $__facebookUserID ) {
            $__facebookUserInformation = $__api->api('/me');
            if ( is_array($__facebookUserInformation) ) {
                $__facebookEmail = isset($__facebookUserInformation['email']) ? $__facebookUserInformation['email'] : null;
                $__facebookUserInformation['firstname'] = isset($__facebookUserInformation['first_name']) ? $__facebookUserInformation['first_name'] : null;
                $__facebookUserInformation['lastname'] = isset($__facebookUserInformation['last_name']) ? $__facebookUserInformation['last_name'] : null;
                $this->setCustomerInformation($__facebookUserInformation);
            }
            
            if ( $__facebookEmail ) {
                return $this->__attemptToLoginCustomerEmail($__facebookEmail);
            }
            else {
                throw new Exception('Unable to fetch email address from Facebook account');
            }
        }
        else {
            throw new Exception('Must be logged in at Facebook');
        }
    }
    
    
    /**
     * 
     */
    protected function __loadCustomerInformation() {
        if ( !$this->getRequest() ) {
            throw new Exception(self::E_MSG_NOREQUEST);
        }
        
        $__accessToken = $this->getRequest()->getPost('accessToken');
        
        $__api = Mage::getModel(self::API_MODEL);
        if ( $__accessToken ) {
            $__api->setAccessToken($__accessToken);
        }
        $__api->setAppId(Mage::getStoreConfig('ajaxlogin/login_with_facebook_account/application_id'));
        $__api->setAppSecret(Mage::getStoreConfig('ajaxlogin/login_with_facebook_account/application_secret'));
        $__facebookUserID = $__api->getUser();
        
        if ( $__facebookUserID ) {
            $__facebookUserInformation = $__api->api('/me');
            if ( is_array($__facebookUserInformation) ) {
                $__facebookEmail = isset($__facebookUserInformation['email']) ? $__facebookUserInformation['email'] : null;
                $__facebookUserInformation['firstname'] = isset($__facebookUserInformation['first_name']) ? $__facebookUserInformation['first_name'] : null;
                $__facebookUserInformation['lastname'] = isset($__facebookUserInformation['last_name']) ? $__facebookUserInformation['last_name'] : null;
                $this->setCustomerInformation($__facebookUserInformation);
            }
        }
    }
}