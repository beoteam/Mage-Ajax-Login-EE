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
class AW_Ajaxlogin_Block_AuthorizationFormElementSocialnets extends AW_Ajaxlogin_Block_Template {
    
    /**
     * 
     */
    public function getNetworks() {
        return Mage::helper('ajaxlogin/data')->getNetworks();
    }
    
    
    /**
     * 
     */
    public function getThumbnailImageURL($imagePath) {
        return Mage::getDesign()->getSkinUrl($imagePath);
    }
    
    
    /**
     * 
     */
    public function isProperlyConfigured($network) {
        $__configured = false;
        
        if ( $network and $network->getModel() ) {
            try {
                $__authorizationModel = Mage::getModel($network->getModel());
                $__configured = $__authorizationModel->isProperlyConfigured();
            }
            catch ( Exception $__E ) {
                Mage::logException($__E);
            }
        }
        
        return $__configured;
    }
    
    
    /**
     * 
     */
    public function getButtonInitializationHtml($network) {
        $__html = '';
        
        if ( $network and $network->getTemplate() ) {
            try {
                if ( $network->getBlock() ) {
                    $__block = $this->getLayout()->createBlock($network->getBlock());
                }
                else {
                    $__block = $this->getLayout()->createBlock('core/template');
                }
                
                if ($__block) {
                    $__html = $__block
                        ->setAuthorizationBlock($this->getParentBlock()->getParentBlock())
                        ->setNetwork($network)
                        ->setTemplate($network->getTemplate())
                        ->toHtml()
                    ;
                }
            }
            catch ( Exception $__E ) {
                Mage::logException($__E);
            }
        }
        
        return $__html;
    }
}