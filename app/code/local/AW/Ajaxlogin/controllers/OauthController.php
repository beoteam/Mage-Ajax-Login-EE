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
class AW_Ajaxlogin_OauthController extends Mage_Core_Controller_Front_Action {
    
    /**
     * Request parameters:
     *     network - codename of the social network
     */
    public function checkAuthorizationStatusAction() {
        $__response = $this->__callAuthorizationStatus();
        
        if ( is_array($__response) and isset($__response['authorization_request_url']) ) {
            $this->_redirectUrl( $__response['authorization_request_url'] );
        }
        
        $this->getResponse()->setBody('<script type="text/javascript">window.close();</script>');
        return $this;
    }
    
    
    /**
     * Request parameters:
     *     network - codename of the social network
     */
    public function getAuthorizationStatusAction() {
        $__response = $this->__callAuthorizationStatus();
        
        $this->getResponse()->setBody(
            Zend_Json_Encoder::encode($__response)
        );
        
        return $this;
    }
    
    
    /**
     * Request parameters:
     *     network - codename of the social network
     * 
     * Every other parameter is passed to network authorization model
     */
    public function acceptTokenAction() {
        $__response = '';
        
        try {
            $__targetNetworkName = $this->getRequest()->getParam('network');
            if ( $__targetNetworkName ) {
                $__targetNetwork = null;
                
                $__networks = Mage::helper('ajaxlogin')->getNetworks();
                foreach ( $__networks as $__network ) {
                    if ( $__targetNetworkName == $__network->getName() ) {
                        $__targetNetwork = $__network;
                        break;
                    }
                }
                
                if ( $__targetNetwork ) {
                    $__authorizationModel = Mage::getModel($__targetNetwork->getModel());
                    if ( $__authorizationModel ) {
                        $__response = $__authorizationModel->acceptToken($this->getRequest()->getParams());
                    }
                    else {
                        throw new Exception('Failed to load network authorization model');
                    }
                }
                else {
                    throw new Exception('Network named "' . $__targetNetworkName . '" not found');
                }
            }
            else {
                throw new Exception('No network name passed within parameters');
            }
        }
        catch ( Exception $__E ) {
            Mage::logException($__E);
        }
        
        $this->getResponse()->setBody('<script type="text/javascript">window.close();</script>');
        return $this;
    }
    
    
    /**
     * 
     */
    protected function __callAuthorizationStatus() {
        $__response = '';
        
        try {
            $__targetNetworkName = $this->getRequest()->getParam('network');
            if ( $__targetNetworkName ) {
                $__targetNetwork = null;
                
                $__networks = Mage::helper('ajaxlogin')->getNetworks();
                foreach ( $__networks as $__network ) {
                    if ( $__targetNetworkName == $__network->getName() ) {
                        $__targetNetwork = $__network;
                        break;
                    }
                }
                
                if ( $__targetNetwork ) {
                    $__authorizationModel = Mage::getModel($__targetNetwork->getModel());
                    if ( $__authorizationModel ) {
                        $__response = $__authorizationModel->getAuthorizationStatus();
                    }
                    else {
                        throw new Exception('Failed to load network authorization model');
                    }
                }
                else {
                    throw new Exception('Network named "' . $__targetNetworkName . '" not found');
                }
            }
            else {
                throw new Exception('No network name passed within parameters');
            }
        }
        catch ( Exception $__E ) {
            Mage::logException($__E);
            $__response = $__E->getMessage();
        }
        
        return $__response;
    }
}