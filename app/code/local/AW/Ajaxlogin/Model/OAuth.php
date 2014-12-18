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
class AW_Ajaxlogin_Model_OAuth extends Varien_Object {
    
    /**
     * 
     */
    private $__config   = null;
    private $__consumer = null;
    
    
    /**
     * 
     */
    public function getConfig() {
        return $this->__config;
    }
    
    
    /**
     * 
     */
    public function setConfig($config) {
        $this->__config = $config;
        return $this;
    }
    
    
    /**
     * 
     */
    public function getConsumer() {
        if ( !$this->__consumer ) {
            $this->__loadConsumer();
        }
        
        return $this->__consumer;
    }
    
    
    /**
     * 
     */
    protected function __loadConsumer() {
        $this->__consumer = new Zend_Oauth_Consumer($this->getConfig());
        
        return $this;
    }
    
    
    /**
     * 
     */
    public function getAuthorizationUrl() {
        $__url = null;
        
        if ( $this->getConsumer()->getConsumerKey() and $this->getConsumer()->getConsumerSecret() ) {
            if ( $this->getConsumer() ) {
                $__requestToken = $this->getConsumer()->getRequestToken();
                $__url = $this->getConsumer()->getRedirectUrl();
            }
        }
        else {
            throw new Exception('Consumer key and consumer secret required');
        }
        
        return $__url;
    }
}