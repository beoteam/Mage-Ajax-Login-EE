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
class AW_Ajaxlogin_Block_AdminhtmlSystemConfigFieldHint extends Mage_Adminhtml_Block_System_Config_Form_Field {
    
    /**
     * 
     */
    public function render(Varien_Data_Form_Element_Abstract $element) {
        $__hintTemplate = (string)$element->getFieldConfig()->hint_template;
        $__htmlID       = $element->getHtmlId();
        
        $__comment = str_replace(
            AW_Ajaxlogin_Helper_Data::VARIABLE_CODE_HINT,
            '<a title="Click to open a hint" href="javascript:void(0);" onclick="__openOptionHint(document.getElementById(\'' . $__htmlID . '\'))">What\'s&nbsp;this?</a>',
            $element->getComment()
        );
        $element->setComment($__comment);
        
        if ( $__hintTemplate ) {
            $__hintBlockHtml = null;
            
            try {
                $__block = $this->getLayout()->createBlock('core/template', $element->getName() . '_option_hint');
                if ( $__block ) {
                    $__block->setTemplate($__hintTemplate);
                    $__hintBlockHtml = $__block->toHtml();
                }
            }
            catch ( Exception $__E ) {
                Mage::logException($__E);
            }
            
            if ( $__hintBlockHtml ) {
                $element->setData('after_element_html', $this->__encloseHintHtml($__hintBlockHtml));
            }
        }
        
        return parent::render($element);
    }
    
    
    /**
     * 
     */
    protected function __encloseHintHtml($html) {
        return '<span rel="__optionHint" style="display: none;">' . $html . '</span>';
    }
}