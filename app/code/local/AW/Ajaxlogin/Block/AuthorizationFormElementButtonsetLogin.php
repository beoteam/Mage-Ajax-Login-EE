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
class AW_Ajaxlogin_Block_AuthorizationFormElementButtonsetLogin extends AW_Ajaxlogin_Block_Template {
    
    /**
     * 
     */
    private $__forgotpasswordbuttonUID      = null;
    private $__createaccountbuttonUID       = null;
    private $__showForgotpasswordbuttonFlag = true;
    private $__showCreateaccountbuttonFlag  = true;
    
    
    /**
     *
     */
    public function hideForgotpasswordbutton() {
        $this->__showForgotpasswordbuttonFlag = false;
    }
    
    
    /**
     *
     */
    public function hideCreateaccountbutton() {
        $this->__showCreateaccountbuttonFlag = false;
    }
    
    
    /**
     * 
     */
    public function shouldDisplayForgotpasswordbutton() {
        return $this->__showForgotpasswordbuttonFlag;
    }
    
    
    /**
     * 
     */
    public function shouldDisplayCreateaccountbutton() {
        return $this->__showCreateaccountbuttonFlag;
    }
    
    
    /**
     * 
     */
    public function getForgotpasswordbuttonHtmlId() {
        if ( !$this->__forgotpasswordbuttonUID ) {
            $this->__forgotpasswordbuttonUID = Mage::helper('ajaxlogin/data')->getUniqueID();
        }
        
        return 'node-uid-' . $this->__forgotpasswordbuttonUID;
    }
    
    
    /**
     * 
     */
    public function getCreateaccountbuttonHtmlId() {
        if ( !$this->__createaccountbuttonUID ) {
            $this->__createaccountbuttonUID = Mage::helper('ajaxlogin/data')->getUniqueID();
        }
        
        return 'node-uid-' . $this->__createaccountbuttonUID;
    }
}