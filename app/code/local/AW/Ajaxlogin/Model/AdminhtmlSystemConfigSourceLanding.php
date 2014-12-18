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
class AW_Ajaxlogin_Model_AdminhtmlSystemConfigSourceLanding {
    
    /**
     * 
     */
    static private $__options    = null;
    static private $__translator = null;
    
    
    /**
     * 
     */
    public function toOptionArray() {
        if ( is_null(self::$__options) ) {
            self::$__options = $this->__loadOptions();
        }
        
        return self::$__options;
    }
    
    
    /**
     * 
     */
    public function toLinksArray() {
        return $this->__getCustomerAccountNavigationLinks();
    }
    
    
    /**
     * 
     */
    protected function __loadOptions() {
        $__options = array(
            array(
                'label' => 'Stay on current',
                'value' => ''
            ),
            array(
                'label' => '',
                'value' => '-',
                'style' => '" disabled class="Disabled'
            ),
            array(
                'label' => 'Home Page',
                'value' => Mage::getBaseUrl()
            ),
        );
        
        foreach ( $this->__getCustomerAccountNavigationLinks() as $__link ) {
            $__translationScope = $this->__getModuleNameByURL($__link->getUrl());
            array_push(
                $__options,
                array(
                    'label' => 'Customer → ' . $this->__translateAsInFrontend($__link->getLabel(), $__translationScope),
                    'value' => $__link->getUrl()
                )
            );
        }
        
        array_push(
            $__options,
            array(
                'label' => 'Customer Service',
                'value' => Mage::getUrl('customer-service')
            )
        );
        array_push(
            $__options,
            array(
                'label' => '',
                'value' => '-',
                'style' => '" disabled class="Disabled'
            )
        );
        array_push(
            $__options,
            array(
                'label' => 'Specified URL ...',
                'value' => '...'
            )
        );
        
        foreach ( $__options as $__key => $__value ) {
            $__options[$__key] = str_replace(Mage::getBaseUrl(), AW_Ajaxlogin_Helper_Data::VARIABLE_CODE_BASEURL, $__value);
        }
        
        return $__options;
    }
    
    
    /**
     * 
     */
    protected function __getCustomerAccountNavigationLinks() {
        $__layout = Mage::getModel('core/layout');
        
        /* Emulate frontend area environment */
        $__originalEnv = array(
            'layout_area'           => $__layout->getArea(),
            'design_area'           => Mage::getDesign()->getArea(),
            'design_package'        => Mage::getDesign()->getPackageName(),
            'design_theme_layout'   => Mage::getDesign()->getTheme('layout'),
            'design_theme_template' => Mage::getDesign()->getTheme('template'),
            'design_theme_skin'     => Mage::getDesign()->getTheme('skin'),
            'design_theme_locale'   => Mage::getDesign()->getTheme('locale')
        );
        $__layout->setArea('frontend');
        Mage::getDesign()->setArea('frontend');
        Mage::getDesign()->setPackageName( (string)Mage::getConfig()->getNode('design/package/name', 'default') );
        Mage::getDesign()->setTheme('default');
        
        $__layout->getUpdate()
            ->addHandle('default')
            ->addHandle('customer_account')
            ->load()
        ;
        $__layout
            ->generateXml()
            ->generateBlocks()
        ;
        
        $__links = $__layout->getBlock('customer_account_navigation')->getLinks();
        
        /* Roll back to adimnhtml area environment */
        $__layout->setArea($__originalEnv['layout_area']);
        Mage::getDesign()->setArea($__originalEnv['design_area']);
        Mage::getDesign()->setPackageName($__originalEnv['design_package']);
        Mage::getDesign()->setTheme('layout', $__originalEnv['design_theme_layout']);
        Mage::getDesign()->setTheme('template', $__originalEnv['design_theme_template']);
        Mage::getDesign()->setTheme('skin', $__originalEnv['design_theme_skin']);
        Mage::getDesign()->setTheme('locale', $__originalEnv['design_theme_locale']);
        
        return $__links;
    }
    
    
    /**
     * 
     */
    protected function __getStoreId() {
        $__storeCode = Mage::app()->getFrontController()->getRequest()->getParam('store');
        return Mage::app()->getStore($__storeCode)->getId();
    }
    
    
    /**
     *
     */
    protected function __translateAsInFrontend($text, $scope) {
        if ( !self::$__translator ) {
            self::$__translator = Mage::getModel('ajaxlogin/translate');
            self::$__translator->setStoreId($this->__getStoreId())->init('frontend', true);
        }
        
        return self::$__translator->translate(
            array(
                new Mage_Core_Model_Translate_Expr($text, $scope)
            )
        );
    }
    
    
    /**
     * 
     */
    protected function __getModuleNameByURL($__url) {
        $__request = new Mage_Core_Controller_Request_Http($__url);
        
        $__path = trim($__request->getPathInfo(), '/');
        if ($__path) $p = explode('/', $__path);
        else $p = explode('/', Mage::getStoreConfig('web/default/front'));
        
        $__moduleName = $__request->getModuleName();
        if ( !$__moduleName ) {
            if ( !empty($p[0]) ) {
                $__moduleName = $p[0];
            }
            else {
                $__moduleName = Mage::app()->getFrontController()->getDefault('module');
            }
        }
        $__moduleInfo = Mage::app()->getFrontController()->getRouter('standard')->getModuleByFrontName($__moduleName);
        
        return (is_array($__moduleInfo) and isset($__moduleInfo[0])) ? $__moduleInfo[0] : null;
    
    }
}