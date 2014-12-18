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
class AW_Ajaxlogin_IndexController extends Mage_Core_Controller_Front_Action {
    
    /**
     * 
     */
    private $__responseObject = null;
    
    
    /**
     * 
     */
    protected function __prepareAction() {
        if ( $this->getRequest()->isPost() ) {
            if ( $this->getRequest()->getPost('__forceReloading') ) {
                $this->__forceReloading();
            }
            if ( $this->getRequest()->getPost('__forceUpdating') ) {
                $this->__forceUpdating();
            }
            
            if ( $this->__relatedToCustomerAccountRoutine($this->__fetchLocation()) ) {
                if ( $this->getRequest()->getActionName() != 'recoveryPost' ) {
                    $this->__forceReloading();
                }
            }
            if ( $this->__relatedToCheckoutRoutine($this->__fetchLocation()) ) {
                if ( $this->getRequest()->getActionName() != 'recoveryPost' ) {
                    $this->__forceLanding( $this->__fetchLocation() );
                    $this->__forceReloading();
                }
            }
            if ( $this->__relatedToShoppingCartRoutine($this->__fetchLocation()) ) {
                if ( $this->getRequest()->getActionName() != 'logoutPost' ) {
                    $this->__forceLanding( $this->__fetchLocation() );
                }
                if ( $this->getRequest()->getActionName() != 'recoveryPost' ) {
                    $this->__forceReloading();
                }
            }
        }
        else {
            $this->_redirect('');
        }
    }
    
    
    /**
     * 
     */
    public function loginPostAction() {
        $this->__prepareAction();
        
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            return $this->__sendResponse(
                array(
                    'success'      => 0,
                    'errorMessage' => 'Already logged in'
                )
            );
        }
        $showCaptcha = '';
        $login['username'] = $this->getRequest()->getPost('email');
        if ($this->getRequest()->isPost()) {
            /* Checking captcha if provided */
            if (Mage::helper('ajaxlogin')->isModuleOutputEnabled('Mage_Captcha')) {

                $__captchaModel = Mage::helper('captcha')->getCaptcha('user_login');
                $__captchaParameters = $this->getRequest()->getPost(Mage_Captcha_Helper_Data::INPUT_NAME_FIELD_VALUE);
                $__captchaString = isset( $__captchaParameters['ajax_user_login'] ) ? $__captchaParameters['ajax_user_login'] : '';
                $isCaptchaRequired = $__captchaModel->isRequired($login['username']);
                $__captchaModel->logAttempt($login['username']);
                $formId = 'ajax_user_login';
                if ($__captchaModel->isRequired($login['username'])) {
                    $showCaptcha = 'ajax_user_login';
                }

                if (array_key_exists('ajax_user_login', $__captchaParameters)) {
                    $__captchaModel = Mage::helper('captcha')->getCaptcha('ajax_user_login');
                }

                if (array_key_exists('ajax_guest_checkout', $__captchaParameters)) {
                    $__captchaModel = Mage::helper('captcha')->getCaptcha('guest_checkout');
                    $__captchaString = $__captchaParameters['ajax_guest_checkout'];
                    $isCaptchaRequired = $__captchaModel->isRequired($login['username']);
                    $__captchaModel->logAttempt($login['username']);
                    $formId = 'ajax_guest_checkout';
                    if ($__captchaModel->isRequired($login['username'])) {
                        $showCaptcha = 'ajax_guest_checkout';
                    }
                    $__captchaModel = Mage::helper('captcha')->getCaptcha('ajax_guest_checkout');
                }

                if ( $isCaptchaRequired ) {
                    if ( !$__captchaModel->isCorrect($__captchaString) ) {
                        return $this->__sendResponse(
                            array(
                                 'success'      => 0,
                                 'errorMessage' => Mage::helper('captcha')->__('Incorrect CAPTCHA.'),
                                 'captcha'      => $showCaptcha,
                                 'form_id'      => $formId
                            )
                        );
                    }
                }
            }

            $login['username'] = $this->getRequest()->getPost('email');
            $login['password'] = $this->getRequest()->getPost('password');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    if ($session->getCustomer()->getIsJustConfirmed()) {
                        $this->_welcomeCustomer($session->getCustomer(), true);
                    }
                    
                    $this
                        ->__addToResponse( array('success' => 1) )
                        ->__addToResponse( array('customer_information' => $this->__getCustomerData()) )
                        ->__setResponseLanding( Mage::helper('ajaxlogin/data')->getConfigLanding(AW_Ajaxlogin_Helper_Data::XML_CONFIG_PATH_LOGINFORM_LOGIN_LANDING) )
                        ->__sendResponse()
                    ;
                }
                catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $__value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $__value);
                        break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                        break;
                        default:
                            $message = $e->getMessage();
                    }
                    $this->__sendResponse(
                        array(
                            'success'      => 0,
                            'errorMessage' => $message,
                            'captcha'      => $showCaptcha,
                        )
                    );
                    $session->setUsername($login['username']);
                }
                catch (Exception $e) {
                    $this->__sendResponse(
                        array(
                            'success'      => 0,
                            'errorMessage' => 'Unknown error',
                            'captcha'      => $showCaptcha,
                        )
                    );
                }
            }
            else {
                $this->__sendResponse(
                    array(
                        'success'      => 0,
                        'errorMessage' => 'Login and password are required.',
                        'captcha'      => $showCaptcha,
                    )
                );
            }
        }
        return $this;
    }
    
    
    /**
     * 
     */
    public function loginWithNetworkAction() {
        $this->__prepareAction();
        
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            return $this->__sendResponse(
                array(
                    'success'      => 0,
                    'errorMessage' => 'Already logged in'
                )
            );
        }
        
        if ($this->getRequest()->isPost()) {
            $__networkName = $this->getRequest()->getPost('network');
            
            $__network = null;
            foreach ( Mage::helper('ajaxlogin/data')->getNetworks() as $__networkInfo ) {
                if ( $__networkInfo->getName() == $__networkName ) {
                    $__network = $__networkInfo;
                    break;
                }
            }
            
            if ( $__network ) {
                if ( $__network->getModel() ) {
                    try {
                        $__model = Mage::getModel($__network->getModel());
                        if ( $__model ) {
                            $__model->setRequest($this->getRequest());
                            if ( $__model->login() ) {
                                $this
                                    ->__addToResponse( array('success' => 1) )
                                    ->__addToResponse( array('customer_information' => $this->__getCustomerData()) )
                                    ->__setResponseLanding( Mage::helper('ajaxlogin/data')->getConfigLanding(AW_Ajaxlogin_Helper_Data::XML_CONFIG_PATH_LOGINFORM_LOGIN_LANDING) )
                                    ->__sendResponse()
                                ;
                            }
                            else {
                                $this
                                    ->__addToResponse( array('success' => 0) )
                                    ->__addToResponse( array('notRegistered' => 1) )
                                    ->__sendResponse()
                                ;
                            }
                        }
                        else {
                            $this->__sendResponse(
                                array(
                                    'success'      => 0,
                                    'errorMessage' => 'Unknown error'
                                )
                            );
                        }
                    }
                    catch ( Exception $__E ) {
                        $this->__sendResponse(
                            array(
                                'success'      => 0,
                                'errorMessage' => $__E->getMessage()
                            )
                        );
                    }
                }
            }
        }
        
        return $this;
    }
    
    
    /**
     * 
     */
    public function logoutPostAction() {
        $this->__prepareAction();
        
        $this->_getSession()->logout()->setBeforeAuthUrl(Mage::getUrl());
        $this
            ->__addToResponse( array('success' => 1) )
            ->__setResponseLanding( Mage::helper('ajaxlogin/data')->getConfigLanding(AW_Ajaxlogin_Helper_Data::XML_CONFIG_PATH_LOGINFORM_LOGOUT_LANDING) )
            ->__sendResponse()
        ;
        
        return $this;
    }
    
    
    /**
     * 
     */
    public function registerPostAction() {
        $this->__prepareAction();
        
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            return $this->__sendResponse(
                array(
                    'success'      => 0,
                    'errorMessage' => $this->__('Already logged in')
                )
            );
        }
        
        $session->setEscapeMessages(true);
        if ($this->getRequest()->isPost()) {
            $errors = array();
            /* Checking captcha if provided */
            if (Mage::helper('ajaxlogin')->isModuleOutputEnabled('Mage_Captcha')) {
                $__captchaModel = Mage::helper('captcha')->getCaptcha('user_create');
                $__captchaParameters = $this->getRequest()->getPost(Mage_Captcha_Helper_Data::INPUT_NAME_FIELD_VALUE);
                $__captchaString = isset( $__captchaParameters['user_create'] ) ? $__captchaParameters['user_create'] : '';
                $__isCaptchaRequired = $__captchaModel->isRequired();
                if (null === $__captchaModel->getWord()) {
                    $__captchaModel = Mage::helper('captcha')->getCaptcha('ajax_user_create');
                    $__captchaString = isset( $__captchaParameters['ajax_user_create'] ) ? $__captchaParameters['ajax_user_create'] : '';
                }
                if ( $__isCaptchaRequired ) {
                    if ( !$__captchaModel->isCorrect($__captchaString) ) {
                        return $this->__sendResponse(
                            array(
                                 'success'      => 0,
                                 'errorMessage' => Mage::helper('captcha')->__('Incorrect CAPTCHA.')
                            )
                        );
                    }
                }
            }

            if (!$customer = Mage::registry('current_customer')) {
                $customer = Mage::getModel('customer/customer')->setId(null);
            }
            
            /* Attempt to safely load a customer form model */
            if ( method_exists($customer, 'getEntityType') ) {
                /* This indicates Magento CE 1.4.2.0 and above + EE */
                $customerForm = Mage::getModel('customer/form');
            }
            else {
                $customerForm = null;
            }
            
            if ( $customerForm ) {
                /**
                 * CE 1.4.2.x and above, EE
                 */
                
                $customerForm
                    ->setFormCode('customer_account_create')
                    ->setEntity($customer)
                ;
                $customerData = $customerForm->extractData($this->getRequest());
            }
            else {
                /**
                 * 1.4.1.1
                 * There wasn't any form model that time in Magento
                 */
                $data = $this->_filterPostData($this->getRequest()->getPost());
                
                foreach (Mage::getConfig()->getFieldset('customer_account') as $code=>$node) {
                    if ($node->is('create') && isset($data[$code])) {
                        if ($code == 'email') {
                            $data[$code] = trim($data[$code]);
                        }
                        $customer->setData($code, $data[$code]);
                    }
                }
            }
            
            if ($this->getRequest()->getParam('is_subscribed', false)) {
                $customer->setIsSubscribed(1);
            }
            
            $customer->getGroupId();
            
            if ($this->getRequest()->getPost('create_address')) {
                $address = Mage::getModel('customer/address');
                $addressForm = Mage::getModel('customer/form');
                $addressForm
                    ->setFormCode('customer_register_address')
                    ->setEntity($address)
                ;
                
                $addressData   = $addressForm->extractData($this->getRequest(), 'address', false);
                $addressErrors = $addressForm->validateData($addressData);
                if ($addressErrors === true) {
                    $address
                        ->setId(null)
                        ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
                        ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false))
                    ;
                    $addressForm->compactData($addressData);
                    $customer->addAddress($address);
                    
                    $addressErrors = $address->validate();
                    if (is_array($addressErrors)) {
                        $errors = array_merge($errors, $addressErrors);
                    }
                }
                else {
                    $errors = array_merge($errors, $addressErrors);
                }
            }
            
            try {
                if ( $customerForm ) {
                    /**
                     * CE 1.4.2.x and above, EE
                     */
                    
                    $customerErrors = $customerForm->validateData($customerData);
                    if ($customerErrors !== true) {
                        $errors = array_merge($customerErrors, $errors);
                    }
                    else {
                        $customerForm->compactData($customerData);
                        $customer->setPassword($this->getRequest()->getPost('password'));
                        $customer->setConfirmation($this->getRequest()->getPost('confirmation'));
                        $customerErrors = $customer->validate();
                        if (is_array($customerErrors)) {
                            $errors = array_merge($customerErrors, $errors);
                        }
                    }
                }
                else {
                    /**
                     * CE 1.4.1.1
                     */
                    
                    $customerErrors = $customer->validate();
                    if (is_array($customerErrors)) {
                        $errors = array_merge($customerErrors, $errors);
                    }
                }
                
                $validationResult = count($errors) == 0;
                if (true === $validationResult) {
                    $customer->save();
                    
                    Mage::dispatchEvent(
                        'customer_register_success',
                        array(
                            'account_controller' => $this,
                            'customer'           => $customer
                        )
                    );
                    
                    if ($customer->isConfirmationRequired()) {
                        $customer->sendNewAccountEmail(
                            'confirmation',
                            $session->getBeforeAuthUrl(),
                            Mage::app()->getStore()->getId()
                        );
                        $this->__addToResponse( array('loggedIn' => 0) );
                        $__message =
                              $this->__('Thank you for registering with %s.', Mage::app()->getStore()->getFrontendName()) . "\n"
                            . $this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.', Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail()))
                        ;
                        $this->__addToResponse( array('successMessage' => $__message) );
                        $this->__cancelReloading();
                    }
                    else {
                        $session->setCustomerAsLoggedIn($customer);
                        $this->__addToResponse( array('loggedIn' => 1) );
                    }
                    
                    $this
                        ->__addToResponse( array('success' => 1) )
                        ->__addToResponse( array('customer_information' => $this->__getCustomerData()) )
                        ->__setResponseLanding( Mage::helper('ajaxlogin/data')->getConfigLanding(AW_Ajaxlogin_Helper_Data::XML_CONFIG_PATH_REGISTERFORM_LANDING) )
                        ->__sendResponse()
                    ;
                }
                else {
                    $session->setCustomerFormData($this->getRequest()->getPost());
                    if ( is_array($errors) ) {
                        $this->__addToResponse( array('errorMessage' => join('<br />', $errors)) );
                    }
                    else {
                        $this->__addToResponse( array('errorMessage' => 'Invalid customer data') );
                    }
                    $this->__sendResponse( array('success' => 0) );
                }
            }
            catch (Mage_Core_Exception $e) {
                $session->setCustomerFormData($this->getRequest()->getPost());
                if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
                    $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, try to request your new password and access your account.');
                    $session->setEscapeMessages(false);
                    $this->__addToResponse( array('frame' => 'recovery') );
                }
                else {
                    $message = $e->getMessage();
                }
                
                $this->__sendResponse(
                    array(
                        'success'      => 0,
                        'errorMessage' => $message
                    )
                );
            }
            catch (Exception $e) {
                $session
                    ->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the customer.'))
                ;
                $this->__sendResponse(
                    array(
                        'success'      => 0,
                        'errorMessage' => $this->__('Cannot save the customer.') . ': ' . $e->getMessage()
                    )
                );
            }
        }
        
        return $this;
    }
    
    
    /**
     * 
     */
    public function registerWithNetworkAction() {
        $__result = $this->registerPostAction();
        
        $__networkName = $this->getRequest()->getPost('network');
        $__network = null;
        foreach ( Mage::helper('ajaxlogin/data')->getNetworks() as $__networkInfo ) {
            if ( $__networkInfo->getName() == $__networkName ) {
                $__network = $__networkInfo;
                break;
            }
        }
        if ( $__network ) {
            if ( $__network->getModel() ) {
                try {
                    $__model = Mage::getModel($__network->getModel());
                    if ( $__model ) {
                        $__model->setRequest($this->getRequest());
                        if ( $__model->register() ) {
                            if ( $this->__getResponseObject()->getData('loggedIn') ) {
                                $this
                                    ->__addToResponse( array('customer_information' => $this->__getCustomerData()) )
                                    ->__sendResponse()
                                ;
                            }
                        }
                        else {
                            $this->__sendResponse(
                                array(
                                    'success'      => 0,
                                    'errorMessage' => $this->__('Social network account account failed to get assigned')
                                )
                            );
                        }
                    }
                }
                catch ( Exception $__E ) {
                    Mage::logException( $__E );
                }
            }
        }
        
        return $__result;
    }
    
    
    /**
     * 
     */
    public function recoveryPostAction() {
        $this->__prepareAction();
        if (Mage::helper('ajaxlogin')->isModuleOutputEnabled('Mage_Captcha')) {
            $__captchaModel = Mage::helper('captcha')->getCaptcha('user_forgotpassword');
            $__captchaParameters = $this->getRequest()->getPost(Mage_Captcha_Helper_Data::INPUT_NAME_FIELD_VALUE);
            $__captchaString = isset( $__captchaParameters['ajax_user_forgotpassword'] ) ? $__captchaParameters['ajax_user_forgotpassword'] : '';
            $isCaptchaRequired = $__captchaModel->isRequired();
            if (null === $__captchaModel->getWord()) {
                $__captchaModel = Mage::helper('captcha')->getCaptcha('ajax_user_forgotpassword');
            }
            if ( $isCaptchaRequired ) {
                if ( !$__captchaModel->isCorrect($__captchaString) ) {
                    return $this->__sendResponse(
                        array(
                             'success'      => 0,
                             'errorMessage' => Mage::helper('captcha')->__('Incorrect CAPTCHA.')
                        )
                    );
                }
            }
        }

        $email = (string)$this->getRequest()->getPost('email');
        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                $this->_getSession()->setForgottenEmail($email);
                $this->__sendResponse(
                    array(
                        'success'      => 0,
                        'errorMessage' => $this->__('Invalid email address.')
                    )
                );
            }
            
            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email)
            ;
            if ($customer->getId()) {
                try {
                    if ( !method_exists($customer, 'changeResetPasswordLinkToken') ) {
                        /**
                         * CE 1.4.1.1, EE 1.11.x
                         */
                        
                        $newPassword = $customer->generatePassword();
                        $customer->changePassword($newPassword, false);
                        $customer->sendPasswordReminderEmail();
                    }
                    else {
                        /**
                         * CE 1.4.2.x and above, EE 1.12.0.x and above
                         */
                        
                        $newResetPasswordLinkToken = Mage::helper('customer')->generateResetPasswordLinkToken();
                        $customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
                        $customer->sendPasswordResetConfirmationEmail();
                    }
                }
                catch (Exception $__E) {
                    $this->__sendResponse(
                        array(
                            'success'      => 0,
                            'errorMessage' => $__E->getMessage()
                        )
                    );
                }
            }
            
            $this
                ->__addToResponse(
                    array(
                        'success'        => 1,
                        'successMessage' => Mage::helper('customer')->__('If there is an account associated with %s you will receive an email with a link to reset your password.', Mage::helper('customer')->htmlEscape($email))
                    )
                )
                ->__setResponseLanding( Mage::helper('ajaxlogin/data')->getConfigLanding(AW_Ajaxlogin_Helper_Data::XML_CONFIG_PATH_RECOVERYFORM_LANDING) )
                ->__sendResponse()
            ;
        }
        else {
            $this->__sendResponse(
                array(
                    'success'      => 0,
                    'errorMessage' => $this->__('Please enter your email.')
                )
            );
        }
        
        return $this;
    }

    /**
     * Refreshes captcha and returns JSON encoded URL to image (AJAX action)
     * Example: {'imgSrc': 'http://example.com/media/captcha/67842gh187612ngf8s.png'}
     *
     * @return null
     */
    public function refreshCaptchaAction()
    {
        if (!Mage::helper('ajaxlogin')->isModuleOutputEnabled('Mage_Captcha')) {
            $this->getResponse()->setBody('');
            return $this;
        }
        $formId = $this->getRequest()->getPost('formId');
        $captchaWidth = $this->getRequest()->getPost('width', null);
        $captchaHeight = $this->getRequest()->getPost('height', null);
        $captchaModel = Mage::helper('captcha')->getCaptcha($formId);
        $this->getLayout()->createBlock('ajaxlogin/captcha')
            ->setFormId($formId)
            ->setImgWidth($captchaWidth)
            ->setImgHeight($captchaHeight)
            ->setIsAjax(true)
            ->toHtml()
        ;
        $this->getResponse()->setBody(json_encode(array('imgSrc' => $captchaModel->getImgSrc())));
        $this->setFlag('', self::FLAG_NO_POST_DISPATCH, true);
    }
    
    
    /**
     * 
     */
    protected function __getResponseObject() {
        if ( !$this->__responseObject ) {
            $this->__responseObject = new Varien_Object();
        }
        
        return $this->__responseObject;
    }
    
    
    /**
     * 
     */
    protected function __sendResponse($dataToAdd = null) {
        if ( $dataToAdd ) {
            $this->__addToResponse($dataToAdd);
        }
        
        header('Content-Type: text/javascript');
        $this->getResponse()->setBody(
            Zend_Json_Encoder::encode($this->__getResponseObject()->getData())
        );
        
        return $this;
    }
    
    
    /**
     * 
     */
    protected function __addToResponse($data) {
        if ( is_array($data) ) {
            foreach ( $data as $__key => $__value ) {
                $this->__getResponseObject()->setData($__key, $__value);
            }
        }
        
        return $this;
    }
    
    
    /**
     * 
     */
    protected function __forceReloading() {
        $this->__addToResponse( array('__forceReloading' => 1) );
        
        return $this;
    }
    
    
    /**
     * 
     */
    protected function __cancelReloading() {
        $this->__addToResponse( array('__cancelReloading' => 1) );
        
        return $this;
    }
    
    
    /**
     * 
     */
    protected function __forceUpdating() {
        $this->__addToResponse( array('__forceUpdating' => 1) );
        
        return $this;
    }
    
    
    /**
     * 
     */
    protected function __forceLanding($location) {
        $this->__addToResponse( array('__forceLanding' => $location) );
        
        return $this;
    }
    
    
    /**
     * 
     */
    protected function __setResponseLanding($landing) {
        # Dependencies
        $__forceReloading  = $this->__getResponseObject()->getData('__forceReloading');
        $__cancelReloading = $this->__getResponseObject()->getData('__cancelReloading');
        $__forceUpdating   = $this->__getResponseObject()->getData('__forceUpdating');
        $__forceLanding    = $this->__getResponseObject()->getData('__forceLanding');
        
        # Current location
        $__location = $this->__fetchLocation();
        
        # Landing page
        $__landing = (string)$landing;
        if ( $__forceReloading ) {
            if ( !$__landing ) $__landing = $__location;
        }
        if ( $__forceLanding ) {
            $__landing = $__forceLanding;
        }
        
        # Do landing
        if ( ($__landing) and (!$__cancelReloading) and (($__landing != $__location) or ($__forceReloading)) ) {
            $this->__addToResponse( array('landing' => $__landing) );
        }
        
        # Do updating
        if ( (!$__landing) or ($__landing == $__location) or ($__forceUpdating) ) {
            $this->getLayout()->getUpdate()->addHandle('default');
            if ( Mage::getSingleton('customer/session')->isLoggedIn() ) {
                $this->getLayout()->getUpdate()->addHandle('customer_logged_in');
            }
            else {
                $this->getLayout()->getUpdate()->addHandle('customer_logged_out');
            }
            $this->loadLayoutUpdates();
            $this->generateLayoutXml();
            $this->generateLayoutBlocks();

            $__updates = array();
            
            $__block = $this->getLayout()->getBlock('header');
            if ( $__block ) {
                $__updates['header_container'] = array(
                    'selection'        => '.header-container',
                    'inner'            => true,
                    'update'           => $__block->toHtml(),
                    'update_selection' => '.header-container'
                );
            }
            
            $__block = $this->getLayout()->getBlock('cart_sidebar');
            if ( $__block ) {
                $__updates['cart_sidebar'] = array(
                    'selection' => '.col-right .block-cart',
                    'update'    => $__block->toHtml()
                );
            }
            
            $__block = $this->getLayout()->getBlock('wishlist_sidebar');
            $__updates['wishlist_sidebar'] = array(
                'selection' => '.col-right .block-wishlist',
                'force'     => true,
                'after'     => '.block-cart',
                'update'    => $__block ? $__block->toHtml() : ''
            );

            $this->__addToResponse( array('pageUpdates' => $__updates) );
        }
        
        return $this;
    }
    
    
    /**
     * 
     */
    protected function __fetchLocation() {
        $__location = $this->_getRefererUrl();
        if ( !$__location ) {
            $__location = $this->getRequest()->getPost('location');
        }
        if ( !$__location ) {
            $__location = $this->getRequest()->getParam('location');
        }
        
        return $__location;
    }
    
    
    /**
     * 
     */
    protected function __relatedToCustomerAccountRoutine($location) {
        $__customerAccountRoutines = array(
            Mage::getUrl('customer'),
            Mage::getUrl('sales/order/history'),
            Mage::getUrl('sales/billing_agreement'),
            Mage::getUrl('sales/recurring_profile'),
            Mage::getUrl('review/customer'),
            Mage::getUrl('tag/customer'),
            Mage::getUrl('wishlist'),
            Mage::getUrl('oauth'),
            Mage::getUrl('newsletter/manage'),
            Mage::getUrl('downloadable/customer'),
            Mage::getUrl('advancednewsletter/manage/')
        );
        
        $__belong = false;
        foreach ( $__customerAccountRoutines as $__routine ) {
            if ( strpos($location, $__routine) !== false ) {
                $__belong = true;
                false;
            }
        }
        
        return $__belong;
    }
    
    
    /**
     * 
     */
    protected function __relatedToShoppingCartRoutine($location) {
        $__shoppingCartRoutine = Mage::getUrl('checkout/cart/');
        return ( strpos($location, $__shoppingCartRoutine) !== false ) ? true : false;
    }
    
    
    /**
     * 
     */
    protected function __relatedToCheckoutRoutine($location) {
        $__checkoutRoutine = Mage::getUrl('checkout/onepage/');
        return ( strpos($location, $__checkoutRoutine) !== false ) ? true : false;
    }
    
    
    /**
     * 
     */
    protected function __getCustomerData() {
        $__customerData = null;
        
        $__customer = $this->_getSession()->getCustomer();
        if ( $__customer ) {
            $__customerData = array(
                'firstname'  => $__customer->getData('firstname'),
                'lastname'   => $__customer->getData('lastname'),
                'email'      => $__customer->getData('email'),
                'gender'     => $__customer->getData('gender'),
                'taxvat'     => $__customer->getData('taxvat')
            );
            
            if ( $__customer->getPrimaryBillingAddress() ) {
                foreach  ( $__customer->getPrimaryBillingAddress()->getData() as $__key => $__value ) {
                    $__customerData[$__key] = $__value;
                }
            }
            
            if ( isset($__customerData['vat_id']) and ( $__customerData['vat_id'] ) ) {
                $__customerData['taxvat'] = $__customerData['vat_id'];
            }
        }
        
        return $__customerData;
    }
    
    
    /**
     * 
     */
    protected function _getSession() {
        return Mage::getSingleton('customer/session');
    }
    
    
    /**
     * Tribute to Magento 1.4.1.1
     */
    protected function _filterPostData($data) {
        return $this->_filterDates($data, array('dob'));
    }
}