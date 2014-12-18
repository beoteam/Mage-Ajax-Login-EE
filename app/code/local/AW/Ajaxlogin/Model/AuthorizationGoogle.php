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
class AW_Ajaxlogin_Model_AuthorizationGoogle extends AW_Ajaxlogin_Model_AuthorizationAbstract {
    
    /**
     * 
     */
    const CODENAME        = 'google';
    const API_MODEL       = 'ajaxlogin/google';
    const REDIRECT_ACTION = 'ajaxlogin/oauth/acceptToken/network/google';
    
    const E_MSG_NOREQUEST = 'No request instance is assigned to authorization model';
    const E_MSG_NOTOKEN         = 'Failed to get an access token';
    const E_MSG_NOEMAIL         = 'Unable to fetch email address from LinkedIn account';
    
    
    /**
     * 
     */
    public function isProperlyConfigured() {
        return Mage::helper('ajaxlogin/adminhtml')->isGoogleConfigured();
    }
    
    
    /**
     * 
     */
    public function getCustomerInformation() {
        
    }
    
    
    /**
     * 
     */
    public function login() {
        if ( !$this->getRequest() ) {
            throw new Exception(self::E_MSG_NOREQUEST);
        }
        
        $__accessToken = $this->__getAccessTokenFromCookies();
        if ( $__accessToken ) {
            try {
                $__api = Mage::getModel(self::API_MODEL);
                if ( $__api ) {
                    $__api
                        ->setConsumerKey(Mage::getStoreConfig('ajaxlogin/login_with_google_account/client_id'))
                        ->setConsumerSecret(Mage::getStoreConfig('ajaxlogin/login_with_google_account/client_secret'))
                        ->setRedirectUri(Mage::getUrl(self::REDIRECT_ACTION))
                    ;
                    $__api->setAccessToken($__accessToken);
                    $__googleData = $__api->getUserInformation();
                    $__googleEmail = isset($__googleData['email']) ? $__googleData['email'] : null;
                }
            }
            catch ( Exception $__E ) {
                throw new Exception(self::E_MSG_HTTPCLIENTERROR);
            }
            
            if ( $__googleEmail ) {
                return $this->__attemptToLoginCustomerEmail($__googleEmail);
            }
            else {
                throw new Exception(self::E_MSG_NOEMAIL);
            }
        }
        else {
            throw new Exception(self::E_MSG_NOTOKEN);
        }
    }
    
    
    /**
     * 
     */
    public function getAuthorizationStatus() {
        $__response = new Varien_Object();
        
        $__accessToken = $this->__getAccessTokenFromCookies();
        if ( $__accessToken ) {
            $__accountData = $this->__requestAccountData();
            
            $__response->setAccessTokenAccepted(1);
            $__response->setAccessToken($__accessToken);
            $__response->setAccountData($__accountData);
        }
        else {
            $__api = Mage::getModel(self::API_MODEL);
            if ( $__api ) {
                $__api
                    ->setConsumerKey(Mage::getStoreConfig('ajaxlogin/login_with_google_account/client_id'))
                    ->setConsumerSecret(Mage::getStoreConfig('ajaxlogin/login_with_google_account/client_secret'))
                    ->setRedirectUri(Mage::getUrl(self::REDIRECT_ACTION))
                ;
                
                $__response->setAuthorizationRequestUrl( $__api->getAuthorizationUrl() );
            }
        }
        
        return $__response->getData();
    }
    
    
    /**
     * 
     */
    public function acceptToken($callbackParameters) {
        if ( isset($callbackParameters['code']) ) {
            $__api = Mage::getModel(self::API_MODEL);
            if ( $__api ) {
                $__api
                    ->setConsumerKey(Mage::getStoreConfig('ajaxlogin/login_with_google_account/client_id'))
                    ->setConsumerSecret(Mage::getStoreConfig('ajaxlogin/login_with_google_account/client_secret'))
                    ->setRedirectUri(Mage::getUrl(self::REDIRECT_ACTION))
                ;
                $__api->authenticate($callbackParameters['code']);
                $__token = $__api->getAccessToken();
                if ( $__token ) {
                    $this->__putAccessTokenIntoCookies($__token);
                    $__accountData = $__api->getUserInformation();
                    return '<script type="text/javascript">window.opener.__googleData = ' . Zend_Json_Encoder::encode($__accountData) . '; window.close();</script>';
                }
            }
            else {
                throw new Exception('Failed to load network model');
            }
        }
        else {
            /**
             * User probably cancelled or closed the dialog or disallowed the application
             * to access his account, and the script receives no token
             */
        }
        
        return null;
    }
    
    
    /**
     * 
     */
    protected function __getAccessTokenFromCookies() {
        return Mage::helper('ajaxlogin/data')->fetchDataFromCookie('GOOGLE_ACCESS_TOKEN');
    }
    
    
    /**
     * 
     */
    protected function __putAccessTokenIntoCookies($accessTokenParameters) {
        Mage::helper('ajaxlogin/data')->addDataToCookie('GOOGLE_ACCESS_TOKEN', $accessTokenParameters);
    }
    
    
    /**
     * 
     */
    protected function __requestAccountData() {
        $__accountData = null;
        
        $__accessToken = $this->__getAccessTokenFromCookies();
        if ( $__accessToken ) {
            try {
                $__api = Mage::getModel(self::API_MODEL);
                if ( $__api ) {
                    $__api
                        ->setConsumerKey(Mage::getStoreConfig('ajaxlogin/login_with_google_account/client_id'))
                        ->setConsumerSecret(Mage::getStoreConfig('ajaxlogin/login_with_google_account/client_secret'))
                        ->setRedirectUri(Mage::getUrl(self::REDIRECT_ACTION))
                    ;
                    $__api->setAccessToken($__accessToken);
                    $__accountData = $__api->getUserInformation();
                }
            }
            catch ( Exception $__E ) {
                Mage::logException($__E);
            }
        }
        
        return $__accountData;
    }
}