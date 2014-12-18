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
class AW_Ajaxlogin_Helper_Data extends Mage_Core_Helper_Abstract {
    
    /**
     *
     */
    const TOPLINKS_HTMLCLASSNAME_DEFAULT           = 'top.links';
    const TOPLINKS_HTMLCLASSNAME_IPHONETHEME       = 'awmobile.top.links';
    const TOPLINKS_HTMLCLASSNAME_RWD               = 'before_body_end';
    const TOPLINKS_JS_SELECTION_DEFAULT            = '.header-container .links a';
    const TOPLINKS_JS_SELECTION_RWD                = '#header-account .links a';
    const TOPLINKS_JS_SELECTION_IPHONETHEME        = '.header-top a';
    
    const VARIABLE_CODE_BASEURL                    = '{base_url}';
    const VARIABLE_CODE_BASEURL_SECURE             = '{base_url_secure}';
    const VARIABLE_CODE_HINT                       = '{hint_link}';
    
    const HANDLER_CUSTOMERACCOUNTLOGIN             = 'customer_account_login';
    const HANDLER_CHECKOUTONEPAGEINDEX             = 'checkout_onepage_index';
    const HANDLER_CHECKOUTMULTISHIPPINGLOGIN       = 'checkout_multishipping_login';
    
    const LAYOUT_HANDLER_DEFAULT                   = 'ajaxlogin_default';
    const LAYOUT_HANDLER_CUSTOMERACCOUNTLOGIN      = 'ajaxlogin_customer_account_login';
    const LAYOUT_HANDLER_CHECKOUTONEPAGEINDEX      = 'ajaxlogin_checkout_onepage_index';
    
    const XML_CONFIG_PATH_GENERAL_MODULE_ENABLED   = 'ajaxlogin/general/module_enabled';
    const XML_CONFIG_PATH_LOGINFORM_LOGIN_LANDING  = 'ajaxlogin/login_form/login_success_landing_page';
    const XML_CONFIG_PATH_LOGINFORM_LOGOUT_LANDING = 'ajaxlogin/login_form/logout_success_landing_page';
    const XML_CONFIG_PATH_REGISTERFORM_LANDING     = 'ajaxlogin/registration_form/success_landing_page';
    const XML_CONFIG_PATH_REGISTERFORM_NEWSLETTER  = 'ajaxlogin/registration_form/display_newsletter_subscription_section';
    const XML_CONFIG_PATH_REGISTERFORM_TERMS       = 'ajaxlogin/registration_form/display_terms_and_conditions';
    const XML_CONFIG_PATH_RECOVERYFORM_LANDING     = 'ajaxlogin/password_recovery_form/success_landing_page';
    
    
    /**
     * 
     */
    private $__uid                               = 1;
    
    
    /**
     * 
     */
    public function getUniqueID() {
        $this->__uid++;
        
        return 'ajaxlogin-' . $this->__uid;
    }
    
    
    /**
     * 
     */
    public function getNetworks() {
        $__configData = Mage::getConfig()->getNode('global/ajaxlogin/authorization');
        if ( is_object($__configData) ) $__configData = (array)$__configData;
        
        $__networks = array();
        if ( is_array($__configData) ) {
            foreach ( $__configData as $__key => $__info ) {
                if ( (!$__info->config_path_enabled) or ( Mage::getStoreConfig((string)$__info->config_path_enabled) ) ) {
                    $__network = new Varien_Object();
                    
                    $__network->setName((string)$__key);
                    $__network->setTitle((string)$__info->title);
                    $__network->setModel((string)$__info->model);
                    $__network->setBlock((string)$__info->block);
                    $__network->setTemplate((string)$__info->template);
                    $__network->setThumbnailImagePath((string)$__info->thumbnail_image_path);
                    $__network->setButtonHtmlId($this->getUniqueId());
                    
                    array_push($__networks, $__network);
                }
            }
        }
        
        return $__networks;
    }
    
    
    /**
     * 
     */
    public function getConfigLanding($path) {
        $__configValue = Mage::getStoreConfig($path);
        $__configValue = str_replace(AW_Ajaxlogin_Helper_Data::VARIABLE_CODE_BASEURL, Mage::getBaseUrl(), $__configValue);
        $__configValue = str_replace(AW_Ajaxlogin_Helper_Data::VARIABLE_CODE_BASEURL_SECURE, Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true), $__configValue);
        
        return $__configValue;
    }
    
    
    /**
     * 
     */
    public function getUrlSafeForAjax($action) {
        $__url = Mage::getUrl($action);
        
        $__urlScheme = parse_url($__url, PHP_URL_SCHEME);
        if ( Mage::app()->getRequest()->isSecure() and ($__urlScheme == 'http') ) {
            $__url = str_replace('http://', 'https://', $__url);
        }
        if ( !Mage::app()->getRequest()->isSecure() and ($__urlScheme == 'https') ) {
            $__url = str_replace('https://', 'http://', $__url);
        }
        
        return $__url;
    }
    
    
    /**
     * 
     */
    public function addDataToCookie($key, $data) {
        $__serializedValue = Mage::getSingleton('core/cookie')->get('ajaxlogin-oauth');
        $__value = @unserialize($__serializedValue);
        if ( !is_array($__value) ) $__value = array();
        $__value[$key] = $data;
        $__value = serialize($__value);
        Mage::getSingleton('core/cookie')->set('ajaxlogin-oauth', $__value);
        
        return $this;
    }
    
    
    /**
     * 
     */
    public function fetchDataFromCookie($key) {
        $__serializedValue = Mage::getSingleton('core/cookie')->get('ajaxlogin-oauth');
        $__value = @unserialize($__serializedValue);
        if ( !is_array($__value) ) $__value = array();
        return isset($__value[$key]) ? $__value[$key] : null;
    }
    
    
    /**
     * 
     */
    public function getTopLinksHtmlClassname() {
        $__toplinksHtmlClassname = null;

        switch (Mage::getSingleton('core/design_package')->getPackageName()) {
            case 'aw_mobile':
                $__toplinksHtmlClassname = self::TOPLINKS_HTMLCLASSNAME_IPHONETHEME;
                break;
            case 'rwd':
                $__toplinksHtmlClassname = self::TOPLINKS_HTMLCLASSNAME_RWD;
                break;
            default:
                $__toplinksHtmlClassname = self::TOPLINKS_HTMLCLASSNAME_DEFAULT;
                break;
        }
        return $__toplinksHtmlClassname;
    }
    
    
    /**
     * 
     */
    public function getToplinksSelection() {
        $__toplinksJsSelection = null;

        switch (Mage::getSingleton('core/design_package')->getPackageName()) {
            case 'aw_mobile':
                $__toplinksJsSelection = self::TOPLINKS_JS_SELECTION_IPHONETHEME;
                break;
            case 'rwd':
                $__toplinksJsSelection = self::TOPLINKS_JS_SELECTION_RWD;
                break;
            default:
                $__toplinksJsSelection = self::TOPLINKS_JS_SELECTION_DEFAULT;
                break;
        }
        
        return $__toplinksJsSelection;
    }
    
    
    /**
     * 
     */
    public function isModuleOutputDisabled($moduleName) {
        $__output = (array)Mage::getStoreConfig('advanced/modules_disable_output');
        return ( isset($__output[$moduleName]) and ($__output[$moduleName]) ) ? true : false;
    }

    public function isCaptchaRequired($formId, $login = null)
    {
        if ($this->isModuleOutputEnabled('Mage_Captcha')) {
            switch ($formId) {
                case 'ajax_user_create' : $type = 'user_create';
                    break;
                case 'ajax_user_forgotpassword' : $type = 'user_forgotpassword';
                    break;
                case 'ajax_guest_checkout' : $type = 'guest_checkout';
                    break;
                default : $type = 'user_login';
            }
            $captchaModel = Mage::helper('captcha')->getCaptcha($type);
            return $captchaModel->isRequired($login);
        }
        return false;
    }
}