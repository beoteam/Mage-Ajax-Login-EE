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
require_once 'cmplib.OAuth2.php';


/**
 * 
 */
class AW_Ajaxlogin_Model_Google extends Google_Client {
    
    /**
     * 
     */
    private $__oauth2 = null;
    
    
    /**
     * 
     */
    public function __construct() {
        $__result = parent::__construct();
        $this->__oauth2 = new Google_Oauth2Service($this);
        
        return $__result;
    }
    
    
    /**
     * 
     */
    public function setConsumerKey($key) {
        $this->setClientId($key);
        
        return $this;
    }
    
    
    /**
     * 
     */
    public function setConsumerSecret($secret) {
        $this->setClientSecret($secret);
        
        return $this;
    }
    
    
    /**
     * 
     */
    public function getAuthorizationUrl() {
        return $this->createAuthUrl();
    }
    
    
    /**
     * 
     */
    public function getUserInformation() {
        $__data = null;
        
        if ( $this->__oauth2 ) {
            $__data = $this->__oauth2->userinfo->get();
        }
        
        return $__data;
    }
}