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
class AW_Ajaxlogin_Model_Observer {
    
    /**
     * 
     */
    public function controllerActionLayoutLoadBefore($theObserver) {
        if ( $this->__isModuleEnabled() ) {
            $__update = $theObserver->getEvent()->getLayout()->getUpdate();
            
            $__update->addHandle(AW_Ajaxlogin_Helper_Data::LAYOUT_HANDLER_DEFAULT);
            if (
                ( in_array(AW_Ajaxlogin_Helper_Data::HANDLER_CUSTOMERACCOUNTLOGIN, $__update->getHandles()) )
                OR
                ( in_array(AW_Ajaxlogin_Helper_Data::HANDLER_CHECKOUTMULTISHIPPINGLOGIN, $__update->getHandles()) )
                OR
                ( in_array('ajaxlogin_example_index', $__update->getHandles()) )
            ) {
                $__update->addHandle(AW_Ajaxlogin_Helper_Data::LAYOUT_HANDLER_CUSTOMERACCOUNTLOGIN);
            }
            if ( in_array(AW_Ajaxlogin_Helper_Data::HANDLER_CHECKOUTONEPAGEINDEX, $__update->getHandles()) ) {
                $__update->addHandle(AW_Ajaxlogin_Helper_Data::LAYOUT_HANDLER_CHECKOUTONEPAGEINDEX);
            }
        }
    }
    
    
    /**
     * 
     */
    public function coreBlockAbstractToHtmlAfter($theObserver) {
        if ( $this->__isModuleEnabled() && !$this->isMobile()) {
            $__block = $theObserver->getBlock();
            if ( $__block ) {
                if ( $__block->getNameInLayout() == Mage::helper('ajaxlogin/data')->getTopLinksHtmlClassname() ) {
                    $__transport = $theObserver->getTransport();
                    $__extraBlock = $__block->getLayout()->createBlock('ajaxlogin/overwriterToplinks', 'al_ow_toplinks');
                    if ( $__extraBlock ) {
                        $__extraBlock->setTemplate('ajaxlogin/overwritter.topLinks.phtml');
                        $__transport->setHtml( $__transport->getHtml() . $__extraBlock->toHtml() );
                    }
                }
            }
        }
    }
    
    
    /**
     * 
     */
    protected function __isModuleEnabled() {
        return Mage::getStoreConfig(AW_Ajaxlogin_Helper_Data::XML_CONFIG_PATH_GENERAL_MODULE_ENABLED) ? true : false;
    }

    protected function isMobile()
    {
        if (!Mage::helper('core')->isModuleOutputEnabled('AW_Mobile')) {
            return false;
        }
        if (Mage::getSingleton('customer/session')->getShowDesktop() === true) {
            return false;
        }
        if (Mage::helper('awmobile')->getTargetPlatform() != AW_Mobile_Model_Observer::TARGET_MOBILE) {
            return false;
        }
        return true;
    }
}