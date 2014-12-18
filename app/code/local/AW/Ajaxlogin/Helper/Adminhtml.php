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
class AW_Ajaxlogin_Helper_Adminhtml extends Mage_Core_Helper_Abstract {
    
    /**
     * 
     */
    public function isFacebookConfigured() {
        return
            (
                    ( Mage::getStoreConfig('ajaxlogin/login_with_facebook_account/application_id') )
                and ( Mage::getStoreConfig('ajaxlogin/login_with_facebook_account/application_secret') )
            )
        ;
    }
    
    
    /**
     * 
     */
    public function isLinkedinConfigured() {
        return
            (
                    ( Mage::getStoreConfig('ajaxlogin/login_with_linkedin_account/api_key') )
                and ( Mage::getStoreConfig('ajaxlogin/login_with_linkedin_account/secret_key') )
            )
        ;
    }
    
    
    /**
     * 
     */
    public function isGoogleConfigured() {
        return
            (
                    ( Mage::getStoreConfig('ajaxlogin/login_with_google_account/client_id') )
                and ( Mage::getStoreConfig('ajaxlogin/login_with_google_account/client_secret') )
            )
        ;
    }
    
    
    /**
     * 
     */
    public function isTwitterConfigured() {
        return
            (
                    ( Mage::getStoreConfig('ajaxlogin/login_with_twitter_account/consumer_key') )
                and ( Mage::getStoreConfig('ajaxlogin/login_with_twitter_account/consumer_secret') )
            )
        ;
    }
    
    
    /**
     * 
     */
    public function isAmazonConfigured() {
        return
            (
                    ( Mage::getStoreConfig('ajaxlogin/login_with_amazon_account/client_id') )
                and ( Mage::getStoreConfig('ajaxlogin/login_with_amazon_account/client_secret') )
            )
        ;
    }
}