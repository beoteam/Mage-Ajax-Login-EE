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
require_once dirname(__FILE__) . DS . 'cmplib.OAuth.php';


/**
 * 
 */
class AW_Ajaxlogin_Model_AuthorizationTwitter extends AW_Ajaxlogin_Model_AuthorizationAbstract {
    
    /**
     * 
     */
    const CODENAME        = 'twitter';
    const API_MODEL       = 'ajaxlogin/twitter';
    const REDIRECT_ACTION = 'ajaxlogin/oauth/acceptToken/network/twitter/';
    const PROVIDER_URI    = 'https://api.twitter.com/oauth';
    
    
    /**
     * 
     */
    public function isProperlyConfigured() {
        return Mage::helper('ajaxlogin/adminhtml')->isTwitterConfigured();
    }
    
    
    /**
     * 
     */
    protected function __getConfig() {
        return
            array(
                'consumerKey'    => Mage::getStoreConfig('ajaxlogin/login_with_twitter_account/consumer_key'),
                'consumerSecret' => Mage::getStoreConfig('ajaxlogin/login_with_twitter_account/consumer_secret'),
                'callbackUrl'    => Mage::getUrl(self::REDIRECT_ACTION),
                'siteUrl'        => self::PROVIDER_URI
            )
        ;
    }
    
    
    /**
     * 
     */
    public function getAccountData() {
        return $this->__requestAccountData();
    }
    
    
    /**
     * 
     */
    public function login() {
        if ( !$this->getRequest() ) {
            throw new Exception(parent::E_MSG_NOREQUEST);
        }
        
        $__accessTokenParameters = $this->__getAccessTokenFromCookies();
        if ( $__accessTokenParameters ) {
            $__accountData = $this->__requestAccountData();
            if ( !is_array($__accountData) ) $__accountData = array();
            
            $__twitterUID = isset($__accountData['id']) ? $__accountData['id'] : null;
            if ( $__twitterUID ) {
                $__accountRecord = Mage::getModel('ajaxlogin/account');
                $__accountRecord->loadByNetworkUID(AW_Ajaxlogin_Model_Twitter::NETWORK_CODE, $__twitterUID);
                
                if ( $__accountRecord->getId() ) {
                    return $this->__attemptToLoginCustomerID($__accountRecord->getCustomerId());
                }
                else {
                    return false;
                }
            }
            else {
                throw new Exception(parent::E_MSG_NOUID);
            }
        }
        else {
            throw new Exception(parent::E_MSG_NOTOKEN);
        }
    }
    
    
    /**
     * 
     */
    public function register() {
        if ( !$this->getRequest() ) {
            throw new Exception(parent::E_MSG_NOREQUEST);
        }
        
        $__accessTokenParameters = $this->__getAccessTokenFromCookies();
        if ( $__accessTokenParameters ) {
            $__accountData = $this->__requestAccountData();
            if ( !is_array($__accountData) ) $__accountData = array();
            
            $__twitterUID = isset($__accountData['id']) ? $__accountData['id'] : null;
            if ( $__twitterUID ) {
                $__accountRecord = Mage::getModel('ajaxlogin/account');
                $__accountRecord->loadByNetworkUID(AW_Ajaxlogin_Model_Twitter::NETWORK_CODE, $__twitterUID);
                
                if ( $__accountRecord->getId() ) {
                    return false;
                }
                else {
                    $__accountRecord
                        ->setNetworkCode(AW_Ajaxlogin_Model_Twitter::NETWORK_CODE)
                        ->setNetworkAccountUid($__twitterUID)
                        ->setCustomerId($this->__getCurrentCustomerID())
                        ->setCreationTime($this->__getCurrentGMT())
                        ->save()
                    ;
                    
                    return $__accountRecord->getId() ? true : false;
                }
            }
        }
        else {
            throw new Exception(parent::E_MSG_NOTOKEN);
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
            $__response->setAccountData($__accountData);
        }
        else {
            try {
                $__api = Mage::getModel(self::API_MODEL);
                if ( $__api ) {
                    $__api->setConfig($this->__getConfig());
                    $__response->setAuthorizationRequestUrl( $__api->getAuthorizationUrl() );
                    $__lastRequestToken = $__api->getConsumer()->getLastRequestToken();
                    $this->__putRequestTokenIntoCookies(
                        array(
                            Zend_Oauth_Token::TOKEN_PARAM_KEY                => $__lastRequestToken->getParam(Zend_Oauth_Token::TOKEN_PARAM_KEY),
                            Zend_Oauth_Token::TOKEN_SECRET_PARAM_KEY         => $__lastRequestToken->getParam(Zend_Oauth_Token::TOKEN_SECRET_PARAM_KEY),
                            Zend_Oauth_Token::TOKEN_PARAM_CALLBACK_CONFIRMED => $__lastRequestToken->getParam(Zend_Oauth_Token::TOKEN_PARAM_CALLBACK_CONFIRMED)
                        )
                    );
                }
                else {
                    throw new Exception('Failed to load network model');
                }
            }
            catch ( Zend_Oauth_Exception $__E ) {
                Mage::logException($__E);
            }
            catch ( Exception $__E ) {
                Mage::logException($__E);
            }
        }
        
        return $__response->getData();
    }
    
    
    /**
     * 
     */
    public function acceptToken($callbackParameters) {
        $__requestTokenParameters = $this->__getRequestTokenFromCookies();
        
        if ( isset($callbackParameters['oauth_token']) and ($__requestTokenParameters) ) {
            $__api = Mage::getModel(self::API_MODEL);
            if ( $__api ) {
                $__api->setConfig($this->__getConfig());
                $__requestToken = new Zend_Oauth_Token_Request();
                $__requestToken->setParams($__requestTokenParameters);
                $__accessToken = $__api->getConsumer()->getAccessToken($callbackParameters, $__requestToken);
                if ( $__accessToken ) {
                    $this->__putAccessTokenIntoCookies(
                        array(
                            Zend_Oauth_Token::TOKEN_PARAM_KEY                => $__accessToken->getParam(Zend_Oauth_Token::TOKEN_PARAM_KEY),
                            Zend_Oauth_Token::TOKEN_SECRET_PARAM_KEY         => $__accessToken->getParam(Zend_Oauth_Token::TOKEN_SECRET_PARAM_KEY),
                            Zend_Oauth_Token::TOKEN_PARAM_CALLBACK_CONFIRMED => $__accessToken->getParam(Zend_Oauth_Token::TOKEN_PARAM_CALLBACK_CONFIRMED),
                            parent::PARAMKEY_OAUTH_EXPIRES_IN                => $__accessToken->getParam(parent::PARAMKEY_OAUTH_EXPIRES_IN),
                            parent::PARAMKEY_OAUTH_AUTH_EXPIRES_IN           => $__accessToken->getParam(parent::PARAMKEY_OAUTH_AUTH_EXPIRES_IN)
                        )
                    );
                    
                    $__accountData = $this->__requestAccountData();
                    return '<script type="text/javascript">window.opener.__twitterData = ' . Zend_Json_Encoder::encode($__accountData) . '; window.close();</script>';
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
    protected function __getRequestTokenFromCookies() {
        return Mage::helper('ajaxlogin/data')->fetchDataFromCookie('TWITTER_REQUEST_TOKEN');
    }
    
    
    /**
     * 
     */
    protected function __putRequestTokenIntoCookies($requestTokenParameters) {
        Mage::helper('ajaxlogin/data')->addDataToCookie('TWITTER_REQUEST_TOKEN', $requestTokenParameters);
    }
    
    
    /**
     * 
     */
    protected function __getAccessTokenFromCookies() {
        return Mage::helper('ajaxlogin/data')->fetchDataFromCookie('TWITTER_ACCESS_TOKEN');
    }
    
    
    /**
     * 
     */
    protected function __putAccessTokenIntoCookies($accessTokenParameters) {
        Mage::helper('ajaxlogin/data')->addDataToCookie('TWITTER_ACCESS_TOKEN', $accessTokenParameters);
    }
    
    
    /**
     * 
     */
    protected function __requestAccountData() {
        $__accountData = null;
        
        $__accessTokenParameters = $this->__getAccessTokenFromCookies();
        if ( $__accessTokenParameters ) {
            try {
                $__accessToken = new Zend_Oauth_Token_Access();
                $__accessToken->setParams($__accessTokenParameters);
                
                $__httpClient = $__accessToken->getHttpClient($this->__getConfig());
                
                $__httpClient->setUri('https://api.twitter.com/1.1/account/verify_credentials.json');
                $__httpClient->setMethod(Zend_Http_Client::GET);
                $__httpClient->setParameterGet('include_entities', 'true');
                $__httpClient->setParameterGet('skip_status', 'true');
                $__response = $__httpClient->request();
                $__accountData = Zend_Json::decode($__response->getBody());
            }
            catch ( Exception $__E ) {
                Mage::logException($__E);
            }
        }
        
        return $__accountData;
    }
}