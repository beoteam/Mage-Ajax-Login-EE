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
class AW_Ajaxlogin_Model_AuthorizationAmazon extends AW_Ajaxlogin_Model_AuthorizationAbstract {
    
    /**
     * 
     */
    const CODENAME        = 'amazon';
    const API_MODEL       = 'ajaxlogin/amazon';
    
    const E_MSG_NOREQUEST = 'No request instance is assigned to authorization model';
    
    
    /**
     * 
     */
    public function isProperlyConfigured() {
        return Mage::helper('ajaxlogin/adminhtml')->isAmazonConfigured();
    }
    
    
    /**
     * 
     */
    public function getAuthorizationStatus() {
        $__response = new Varien_Object();
        
        $__accessToken = $this->__getAccessTokenFromCookies();
        if ( $__accessToken ) {
            $__accountData = $this->__requestAccountData($__accessToken);
            
            $__response->setAccessTokenAccepted(1);
            $__response->setAccountData($__accountData);
        }
        
        return $__response->getData();
    }
    
    
    /**
     * 
     */
    public function login() {
        $__accessToken = $this->getRequest()->getPost('accessToken');
        if ( $__accessToken ) $this->__putAccessTokenIntoCookies( $__accessToken );
        else $__accessToken = $this->__getAccessTokenFromCookies();
        
        if ( $__accessToken ) {
            $__accountData = $this->__requestAccountData($__accessToken);
            if ( is_array($__accountData) ) {
                $__amazonEmail = isset($__accountData['email']) ? $__accountData['email'] : null;
                
                if ( $__amazonEmail ) {
                    return $this->__attemptToLoginCustomerEmail($__amazonEmail);
                }
                else {
                    throw new Exception(parent::E_MSG_NOEMAIL);
                }
            }
            else {
                throw new Exception(parent::E_MSG_HTTPCLIENTERROR);
            }
        }
        else {
            throw new Exception(parent::E_MSG_NOTOKEN);
        }
    }
    
    
    /**
     * 
     */
    protected function __getAccessTokenFromCookies() {
        return Mage::helper('ajaxlogin/data')->fetchDataFromCookie('AMAZON_ACCESS_TOKEN');
    }
    
    
    /**
     * 
     */
    protected function __putAccessTokenIntoCookies($accessToken) {
        Mage::helper('ajaxlogin/data')->addDataToCookie('AMAZON_ACCESS_TOKEN', $accessToken);
    }
    
    
    /**
     * 
     */
    protected function __requestAccountData($__accessToken) {
        $__accountData = null;
        
        if ( !$__accessToken ) {
            $__accessToken = $this->__getAccessTokenFromCookies();
        }
        if ( $__accessToken ) {
            try {
                $__api = Mage::getModel(self::API_MODEL);
                if ( $__api ) {
                    $__accountData = $__api->fetchAccountData($__accessToken);
                }
            }
            catch ( Exception $__E ) {
                Mage::logException($__E);
            }
        }
        
        return $__accountData;
    }
}