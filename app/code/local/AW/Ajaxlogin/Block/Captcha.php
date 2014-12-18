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


if (@class_exists('Mage_Captcha_Block_Captcha_Zend')) {
    class AW_Ajaxlogin_Block_Captcha_Parent extends Mage_Captcha_Block_Captcha_Zend
    {}
} else {
    class AW_Ajaxlogin_Block_Captcha_Parent extends Mage_Core_Block_Template
    {}
}

class AW_Ajaxlogin_Block_Captcha extends AW_Ajaxlogin_Block_Captcha_Parent
{
    public function getRefreshUrl()
    {
        return Mage::getUrl(
            Mage::app()->getStore()->isAdmin() ? 'adminhtml/refresh/refresh' : 'ajaxlogin/index/refreshCaptcha',
            array('_secure' => Mage::app()->getStore()->isCurrentlySecure())
        );
    }

    protected function _toHtml()
    {
        if ($this instanceof Mage_Captcha_Block_Captcha_Zend) {
            if ($this->getImgWidth()) {
                $this->getCaptchaModel()->setWidth($this->getImgWidth());
            }
            if ($this->getImgHeight()) {
                $this->getCaptchaModel()->setHeight($this->getImgHeight());
            }
            $this->getCaptchaModel()->generate();
            if (!$this->getTemplate()) {
                return '';
            }
            return $this->renderView();
        }
        return '';
    }

    public function isRequired()
    {
        return $this->helper('ajaxlogin')->isCaptchaRequired($this->getFormId(),
            Mage::app()->getRequest()->getPost('email'))
        ;
    }
}