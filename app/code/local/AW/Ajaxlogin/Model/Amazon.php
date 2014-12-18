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
class AW_Ajaxlogin_Model_Amazon extends AW_Ajaxlogin_Model_OAuth {
    
    /**
     * 
     */
    const NETWORK_CODE                 = 'amazon';
    const AMAZON_API_REQUESTPROFILEURI = 'https://api.amazon.com/user/profile';
    
    
    /**
     * 
     */
    public function fetchAccountData($accessToken) {
        $__H = curl_init(self::AMAZON_API_REQUESTPROFILEURI);
        curl_setopt($__H, CURLOPT_HTTPHEADER, array('Authorization: bearer ' . $accessToken));
        curl_setopt($__H, CURLOPT_RETURNTRANSFER, true);
        $__result = curl_exec($__H);
        curl_close($__H);
        
        $__result = json_decode($__result);
        if ( is_object($__result) ) {
            $__data = array(
                'name'  => $__result->name,
                'uid'   => $__result->user_id,
                'email' => $__result->email
            );
        } else {
            $__data = null;
        }
        return $__data;
    }
}