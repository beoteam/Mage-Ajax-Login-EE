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
class AW_Ajaxlogin_Model_Translate extends Mage_Core_Model_Translate {
    
    /**
     * 
     */
    private $__storeID = null;
    
    
    /**
     * 
     */
    public function getStoreId() {
        return $this->__storeID;
    }
    
    
    /**
     * 
     */
    public function setStoreId($storeID) {
        $this->__storeID = $storeID;
        
        return $this;
    }
    
    
    /**
     * 
     */
    protected function _loadDbTranslation($forceReload = false) {
        $__storeID = $this->getStoreId();
        
        $arr = $this->getResource()->getTranslationArray($__storeID, $this->getLocale($__storeID));
        $this->_addData($arr, $this->getConfig(self::CONFIG_KEY_STORE), $forceReload);
        
        return $this;
    }
    
    
    /**
     * 
     */
    protected function _getModuleFilePath($module, $fileName) {
        $__storeID = $this->getStoreId();
        
        $file = Mage::getBaseDir('locale');
        $file .= DS . $this->getLocale($__storeID) . DS . $fileName;
        return $file;
    }
    
    
    /**
     * 
     */
    public function getLocale($storeId = null) {
        if ( $storeId ) {
            $__locale = Mage::app()->getLocale();
            $__locale->emulate($storeId);
            $__localeCode = $__locale->getLocaleCode();
            $__locale->revert();
            
            return $__localeCode;
        }
        
        if (is_null($this->_locale)) {
            $this->_locale = Mage::app()->getLocale()->getLocaleCode();
        }
        
        return $this->_locale;
    }
}