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
class AW_Ajaxlogin_Block_AdminhtmlSystemConfigFieldLanding extends Mage_Adminhtml_Block_System_Config_Form_Field {
    
    
    
    /**
     * 
     */
    public function render(Varien_Data_Form_Element_Abstract $element) {
        $__name   = $element->getName();
        $__value  = $element->getValue();
        $__htmlID = $element->getHtmlId();
        
        $__comment = $element->getComment();
        $__comment = str_replace(
            AW_Ajaxlogin_Helper_Data::VARIABLE_CODE_BASEURL,
            '<a title="Click to insert" href="javascript:void(0);" onclick="__insertVariable(document.getElementById(\'' . $__htmlID . '_textInputID\'), \''. AW_Ajaxlogin_Helper_Data::VARIABLE_CODE_BASEURL .'\')">'
                . AW_Ajaxlogin_Helper_Data::VARIABLE_CODE_BASEURL .
            '</a>',
            $__comment
        );
        $__comment = str_replace(
            AW_Ajaxlogin_Helper_Data::VARIABLE_CODE_BASEURL_SECURE,
            '<a title="Click to insert" href="javascript:void(0);" onclick="__insertVariable(document.getElementById(\'' . $__htmlID . '_textInputID\'), \''. AW_Ajaxlogin_Helper_Data::VARIABLE_CODE_BASEURL_SECURE .'\')">'
                . AW_Ajaxlogin_Helper_Data::VARIABLE_CODE_BASEURL_SECURE .
            '</a>',
            $__comment
        );
        
        $element->setData('after_element_html', '
            <input type="text" name="' . $__name . '" id="' . $__htmlID . '_textInputID" class="input-text SelectableText_Input" style="display: none;" />
            <p id="' . $__htmlID . '_textInputCommentID" class="note" style="display: none;"><span>' . $__comment . '</span></p>
            <script type="text/javascript">
                Event.observe(
                    window,
                    \'load\',
                    function() {
                        var __selectNode = document.getElementById(\'' . $__htmlID . '\');
                        var __value = \'' . $__value . '\';
                        
                        if ( !__landingPage_doSelect(__selectNode, __value) ) {
                            var __textInputNode = __getNearestSiblingAfter(__selectNode, \'INPUT\');
                            
                            __landingPage_doSelect(__selectNode, \'...\');
                            __textInputNode.value = __value;
                        }
                        
                        Event.observe(
                            __selectNode,
                            \'change\',
                            function() {
                                __landingPage_onChangeHandler(this);
                            }
                        );
                    }
                );
            </script>
        ');
        
        $element->setName(null);
        $element->setComment(null);
        
        return parent::render($element);
    }
}