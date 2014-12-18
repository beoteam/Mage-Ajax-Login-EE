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


if ( !@class_exists('Zend_Loader') ) {
class Zend_Loader
{
    public static function loadClass($class, $dirs = null)
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return;
        }
        if ((null !== $dirs) && !is_string($dirs) && !is_array($dirs)) {
            throw new Zend_Exception('Directory argument must be a string or an array');
        }
        $className = ltrim($class, '\\');
        $file      = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $file      = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $file .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        if (!empty($dirs)) {
            $dirPath = dirname($file);
            if (is_string($dirs)) {
                $dirs = explode(PATH_SEPARATOR, $dirs);
            }
            foreach ($dirs as $key => $dir) {
                if ($dir == '.') {
                    $dirs[$key] = $dirPath;
                } else {
                    $dir = rtrim($dir, '\\/');
                    $dirs[$key] = $dir . DIRECTORY_SEPARATOR . $dirPath;
                }
            }
            $file = basename($file);
            self::loadFile($file, $dirs, true);
        } else {
            self::loadFile($file, null, true);
        }
        if (!class_exists($class, false) && !interface_exists($class, false)) {
            throw new Zend_Exception("File \"$file\" does not exist or class \"$class\" was not found in the file");
        }
    }
    public static function loadFile($filename, $dirs = null, $once = false)
    {
        self::_securityCheck($filename);
        $incPath = false;
        if (!empty($dirs) && (is_array($dirs) || is_string($dirs))) {
            if (is_array($dirs)) {
                $dirs = implode(PATH_SEPARATOR, $dirs);
            }
            $incPath = get_include_path();
            set_include_path($dirs . PATH_SEPARATOR . $incPath);
        }
        if ($once) {
            include_once $filename;
        } else {
            include $filename;
        }
        if ($incPath) {
            set_include_path($incPath);
        }
        return true;
    }
    public static function isReadable($filename)
    {
        if (is_readable($filename)) {
            return true;
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN'
            && preg_match('/^[a-z]:/i', $filename)
        ) {
            return false;
        }
        foreach (self::explodeIncludePath() as $path) {
            if ($path == '.') {
                if (is_readable($filename)) {
                    return true;
                }
                continue;
            }
            $file = $path . '/' . $filename;
            if (is_readable($file)) {
                return true;
            }
        }
        return false;
    }
    public static function explodeIncludePath($path = null)
    {
        if (null === $path) {
            $path = get_include_path();
        }
        if (PATH_SEPARATOR == ':') {
            $paths = preg_split('#:(?!//)#', $path);
        } else {
            $paths = explode(PATH_SEPARATOR, $path);
        }
        return $paths;
    }
    public static function autoload($class)
    {
        trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated as of 1.8.0 and will be removed with 2.0.0; use Zend_Loader_Autoloader instead', E_USER_NOTICE);
        try {
            @self::loadClass($class);
            return $class;
        } catch (Exception $e) {
            return false;
        }
    }
    public static function registerAutoload($class = 'Zend_Loader', $enabled = true)
    {
        trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated as of 1.8.0 and will be removed with 2.0.0; use Zend_Loader_Autoloader instead', E_USER_NOTICE);
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->setFallbackAutoloader(true);
        if ('Zend_Loader' != $class) {
            self::loadClass($class);
            $methods = get_class_methods($class);
            if (!in_array('autoload', (array) $methods)) {
                throw new Zend_Exception("The class \"$class\" does not have an autoload() method");
            }
            $callback = array($class, 'autoload');
            if ($enabled) {
                $autoloader->pushAutoloader($callback);
            } else {
                $autoloader->removeAutoloader($callback);
            }
        }
    }
    protected static function _securityCheck($filename)
    {
        if (preg_match('/[^a-z0-9\\/\\\\_.:-]/i', $filename)) {
            throw new Zend_Exception('Security check: Illegal character in filename');
        }
    }
    protected static function _includeFile($filespec, $once = false)
    {
        if ($once) {
            return include_once $filespec;
        } else {
            return include $filespec ;
        }
    }
}

}


if ( !@class_exists('Zend_Exception') ) {
class Zend_Exception extends Exception
{
    private $_previous = null;
    public function __construct($msg = '', $code = 0, Exception $previous = null)
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            parent::__construct($msg, (int) $code);
            $this->_previous = $previous;
        } else {
            parent::__construct($msg, (int) $code, $previous);
        }
    }
    public function __call($method, array $args)
    {
        if ('getprevious' == strtolower($method)) {
            return $this->_getPrevious();
        }
        return null;
    }
    public function __toString()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            if (null !== ($e = $this->getPrevious())) {
                return $e->__toString() 
                       . "\n\nNext " 
                       . parent::__toString();
            }
        }
        return parent::__toString();
    }
    protected function _getPrevious()
    {
        return $this->_previous;
    }
}

}


if ( !@interface_exists('Zend_Validate_Interface') ) {
interface Zend_Validate_Interface
{
    public function isValid($value);
    public function getMessages();
}

}


if ( !@class_exists('Zend_Validate_Abstract') ) {
abstract class Zend_Validate_Abstract implements Zend_Validate_Interface
{
    protected $_value;
    protected $_messageVariables = array();
    protected $_messageTemplates = array();
    protected $_messages = array();
    protected $_obscureValue = false;
    protected $_errors = array();
    protected $_translator;
    protected static $_defaultTranslator;
    protected $_translatorDisabled = false;
    protected static $_messageLength = -1;
    public function getMessages()
    {
        return $this->_messages;
    }
    public function getMessageVariables()
    {
        return array_keys($this->_messageVariables);
    }
    public function getMessageTemplates()
    {
        return $this->_messageTemplates;
    }
    public function setMessage($messageString, $messageKey = null)
    {
        if ($messageKey === null) {
            $keys = array_keys($this->_messageTemplates);
            foreach($keys as $key) {
                $this->setMessage($messageString, $key);
            }
            return $this;
        }
        if (!isset($this->_messageTemplates[$messageKey])) {
            throw new Zend_Validate_Exception("No message template exists for key '$messageKey'");
        }
        $this->_messageTemplates[$messageKey] = $messageString;
        return $this;
    }
    public function setMessages(array $messages)
    {
        foreach ($messages as $key => $message) {
            $this->setMessage($message, $key);
        }
        return $this;
    }
    public function __get($property)
    {
        if ($property == 'value') {
            return $this->_value;
        }
        if (array_key_exists($property, $this->_messageVariables)) {
            return $this->{$this->_messageVariables[$property]};
        }
        throw new Zend_Validate_Exception("No property exists by the name '$property'");
    }
    protected function _createMessage($messageKey, $value)
    {
        if (!isset($this->_messageTemplates[$messageKey])) {
            return null;
        }
        $message = $this->_messageTemplates[$messageKey];
        if (null !== ($translator = $this->getTranslator())) {
            if ($translator->isTranslated($messageKey)) {
                $message = $translator->translate($messageKey);
            } else {
                $message = $translator->translate($message);
            }
        }
        if (is_object($value)) {
            if (!in_array('__toString', get_class_methods($value))) {
                $value = get_class($value) . ' object';
            } else {
                $value = $value->__toString();
            }
        } else {
            $value = (string)$value;
        }
        if ($this->getObscureValue()) {
            $value = str_repeat('*', strlen($value));
        }
        $message = str_replace('%value%', (string) $value, $message);
        foreach ($this->_messageVariables as $ident => $property) {
            $message = str_replace("%$ident%", (string) $this->$property, $message);
        }
        $length = self::getMessageLength();
        if (($length > -1) && (strlen($message) > $length)) {
            $message = substr($message, 0, (self::getMessageLength() - 3)) . '...';
        }
        return $message;
    }
    protected function _error($messageKey, $value = null)
    {
        if ($messageKey === null) {
            $keys = array_keys($this->_messageTemplates);
            $messageKey = current($keys);
        }
        if ($value === null) {
            $value = $this->_value;
        }
        $this->_errors[]              = $messageKey;
        $this->_messages[$messageKey] = $this->_createMessage($messageKey, $value);
    }
    protected function _setValue($value)
    {
        $this->_value    = $value;
        $this->_messages = array();
        $this->_errors   = array();
    }
    public function getErrors()
    {
        return $this->_errors;
    }
    public function setObscureValue($flag)
    {
        $this->_obscureValue = (bool) $flag;
        return $this;
    }
    public function getObscureValue()
    {
        return $this->_obscureValue;
    }
    public function setTranslator($translator = null)
    {
        if ((null === $translator) || ($translator instanceof Zend_Translate_Adapter)) {
            $this->_translator = $translator;
        } elseif ($translator instanceof Zend_Translate) {
            $this->_translator = $translator->getAdapter();
        } else {
            throw new Zend_Validate_Exception('Invalid translator specified');
        }
        return $this;
    }
    public function getTranslator()
    {
        if ($this->translatorIsDisabled()) {
            return null;
        }
        if (null === $this->_translator) {
            return self::getDefaultTranslator();
        }
        return $this->_translator;
    }
    public function hasTranslator()
    {
        return (bool)$this->_translator;
    }
    public static function setDefaultTranslator($translator = null)
    {
        if ((null === $translator) || ($translator instanceof Zend_Translate_Adapter)) {
            self::$_defaultTranslator = $translator;
        } elseif ($translator instanceof Zend_Translate) {
            self::$_defaultTranslator = $translator->getAdapter();
        } else {
            throw new Zend_Validate_Exception('Invalid translator specified');
        }
    }
    public static function getDefaultTranslator()
    {
        if (null === self::$_defaultTranslator) {
            if (Zend_Registry::isRegistered('Zend_Translate')) {
                $translator = Zend_Registry::get('Zend_Translate');
                if ($translator instanceof Zend_Translate_Adapter) {
                    return $translator;
                } elseif ($translator instanceof Zend_Translate) {
                    return $translator->getAdapter();
                }
            }
        }
        return self::$_defaultTranslator;
    }
    public static function hasDefaultTranslator()
    {
        return (bool)self::$_defaultTranslator;
    }
    public function setDisableTranslator($flag)
    {
        $this->_translatorDisabled = (bool) $flag;
        return $this;
    }
    public function translatorIsDisabled()
    {
        return $this->_translatorDisabled;
    }
    public static function getMessageLength()
    {
        return self::$_messageLength;
    }
    public static function setMessageLength($length = -1)
    {
        self::$_messageLength = $length;
    }
}

}


if ( !@class_exists('Zend_Validate_Ip') ) {
class Zend_Validate_Ip extends Zend_Validate_Abstract
{
    const INVALID        = 'ipInvalid';
    const NOT_IP_ADDRESS = 'notIpAddress';
    protected $_messageTemplates = array(
        self::INVALID        => "Invalid type given. String expected",
        self::NOT_IP_ADDRESS => "'%value%' does not appear to be a valid IP address",
    );
    protected $_options = array(
        'allowipv6' => true,
        'allowipv4' => true
    );
    public function __construct($options = array())
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } else if (!is_array($options)) {
            $options = func_get_args();
            $temp['allowipv6'] = array_shift($options);
            if (!empty($options)) {
                $temp['allowipv4'] = array_shift($options);
            }
            $options = $temp;
        }
        $options += $this->_options;
        $this->setOptions($options);
    }
    public function getOptions()
    {
        return $this->_options;
    }
    public function setOptions($options)
    {
        if (array_key_exists('allowipv6', $options)) {
            $this->_options['allowipv6'] = (boolean) $options['allowipv6'];
        }
        if (array_key_exists('allowipv4', $options)) {
            $this->_options['allowipv4'] = (boolean) $options['allowipv4'];
        }
        if (!$this->_options['allowipv4'] && !$this->_options['allowipv6']) {
            throw new Zend_Validate_Exception('Nothing to validate. Check your options');
        }
        return $this;
    }
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->_error(self::INVALID);
            return false;
        }
        $this->_setValue($value);
        if (($this->_options['allowipv4'] && !$this->_options['allowipv6'] && !$this->_validateIPv4($value)) ||
            (!$this->_options['allowipv4'] && $this->_options['allowipv6'] && !$this->_validateIPv6($value)) ||
            ($this->_options['allowipv4'] && $this->_options['allowipv6'] && !$this->_validateIPv4($value) && !$this->_validateIPv6($value))) {
            $this->_error(self::NOT_IP_ADDRESS);
            return false;
        }
        return true;
    }
    protected function _validateIPv4($value) {
        $ip2long = ip2long($value);
        if($ip2long === false) {
            return false;
        }
        return $value == long2ip($ip2long);
    }
    protected function _validateIPv6($value) {
        if (strlen($value) < 3) {
            return $value == '::';
        }
        if (strpos($value, '.')) {
            $lastcolon = strrpos($value, ':');
            if (!($lastcolon && $this->_validateIPv4(substr($value, $lastcolon + 1)))) {
                return false;
            }
            $value = substr($value, 0, $lastcolon) . ':0:0';
        }
        if (strpos($value, '::') === false) {
            return preg_match('/\A(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}\z/i', $value);
        }
        $colonCount = substr_count($value, ':');
        if ($colonCount < 8) {
            return preg_match('/\A(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?\z/i', $value);
        }
        if ($colonCount == 8) {
            return preg_match('/\A(?:::)?(?:[a-f0-9]{1,4}:){6}[a-f0-9]{1,4}(?:::)?\z/i', $value);
        }
        return false;
    }
}

}


if ( !@class_exists('Zend_Validate_Hostname') ) {
class Zend_Validate_Hostname extends Zend_Validate_Abstract
{
    const CANNOT_DECODE_PUNYCODE  = 'hostnameCannotDecodePunycode';
    const INVALID                 = 'hostnameInvalid';
    const INVALID_DASH            = 'hostnameDashCharacter';
    const INVALID_HOSTNAME        = 'hostnameInvalidHostname';
    const INVALID_HOSTNAME_SCHEMA = 'hostnameInvalidHostnameSchema';
    const INVALID_LOCAL_NAME      = 'hostnameInvalidLocalName';
    const INVALID_URI             = 'hostnameInvalidUri';
    const IP_ADDRESS_NOT_ALLOWED  = 'hostnameIpAddressNotAllowed';
    const LOCAL_NAME_NOT_ALLOWED  = 'hostnameLocalNameNotAllowed';
    const UNDECIPHERABLE_TLD      = 'hostnameUndecipherableTld';
    const UNKNOWN_TLD             = 'hostnameUnknownTld';
    protected $_messageTemplates = array(
        self::CANNOT_DECODE_PUNYCODE  => "'%value%' appears to be a DNS hostname but the given punycode notation cannot be decoded",
        self::INVALID                 => "Invalid type given. String expected",
        self::INVALID_DASH            => "'%value%' appears to be a DNS hostname but contains a dash in an invalid position",
        self::INVALID_HOSTNAME        => "'%value%' does not match the expected structure for a DNS hostname",
        self::INVALID_HOSTNAME_SCHEMA => "'%value%' appears to be a DNS hostname but cannot match against hostname schema for TLD '%tld%'",
        self::INVALID_LOCAL_NAME      => "'%value%' does not appear to be a valid local network name",
        self::INVALID_URI             => "'%value%' does not appear to be a valid URI hostname",
        self::IP_ADDRESS_NOT_ALLOWED  => "'%value%' appears to be an IP address, but IP addresses are not allowed",
        self::LOCAL_NAME_NOT_ALLOWED  => "'%value%' appears to be a local network name but local network names are not allowed",
        self::UNDECIPHERABLE_TLD      => "'%value%' appears to be a DNS hostname but cannot extract TLD part",
        self::UNKNOWN_TLD             => "'%value%' appears to be a DNS hostname but cannot match TLD against known list",
    );
    protected $_messageVariables = array(
        'tld' => '_tld'
    );
    const ALLOW_DNS   = 1;
    const ALLOW_IP    = 2;
    const ALLOW_LOCAL = 4;
    const ALLOW_ALL   = 7;
    const ALLOW_URI = 8;
    protected $_validTlds = array(
        'ac', 'ad', 'ae', 'aero', 'af', 'ag', 'ai', 'al', 'am', 'an', 'ao', 'aq', 'ar', 'arpa',
        'as', 'asia', 'at', 'au', 'aw', 'ax', 'az', 'ba', 'bb', 'bd', 'be', 'bf', 'bg', 'bh', 'bi',
        'biz', 'bj', 'bm', 'bn', 'bo', 'br', 'bs', 'bt', 'bv', 'bw', 'by', 'bz', 'ca', 'cat', 'cc',
        'cd', 'cf', 'cg', 'ch', 'ci', 'ck', 'cl', 'cm', 'cn', 'co', 'com', 'coop', 'cr', 'cu',
        'cv', 'cx', 'cy', 'cz', 'de', 'dj', 'dk', 'dm', 'do', 'dz', 'ec', 'edu', 'ee', 'eg', 'er',
        'es', 'et', 'eu', 'fi', 'fj', 'fk', 'fm', 'fo', 'fr', 'ga', 'gb', 'gd', 'ge', 'gf', 'gg',
        'gh', 'gi', 'gl', 'gm', 'gn', 'gov', 'gp', 'gq', 'gr', 'gs', 'gt', 'gu', 'gw', 'gy', 'hk',
        'hm', 'hn', 'hr', 'ht', 'hu', 'id', 'ie', 'il', 'im', 'in', 'info', 'int', 'io', 'iq',
        'ir', 'is', 'it', 'je', 'jm', 'jo', 'jobs', 'jp', 'ke', 'kg', 'kh', 'ki', 'km', 'kn', 'kp',
        'kr', 'kw', 'ky', 'kz', 'la', 'lb', 'lc', 'li', 'lk', 'lr', 'ls', 'lt', 'lu', 'lv', 'ly',
        'ma', 'mc', 'md', 'me', 'mg', 'mh', 'mil', 'mk', 'ml', 'mm', 'mn', 'mo', 'mobi', 'mp',
        'mq', 'mr', 'ms', 'mt', 'mu', 'museum', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'name', 'nc',
        'ne', 'net', 'nf', 'ng', 'ni', 'nl', 'no', 'np', 'nr', 'nu', 'nz', 'om', 'org', 'pa', 'pe',
        'pf', 'pg', 'ph', 'pk', 'pl', 'pm', 'pn', 'pr', 'pro', 'ps', 'pt', 'pw', 'py', 'qa', 're',
        'ro', 'rs', 'ru', 'rw', 'sa', 'sb', 'sc', 'sd', 'se', 'sg', 'sh', 'si', 'sj', 'sk', 'sl',
        'sm', 'sn', 'so', 'sr', 'st', 'su', 'sv', 'sy', 'sz', 'tc', 'td', 'tel', 'tf', 'tg', 'th',
        'tj', 'tk', 'tl', 'tm', 'tn', 'to', 'tp', 'tr', 'travel', 'tt', 'tv', 'tw', 'tz', 'ua',
        'ug', 'uk', 'um', 'us', 'uy', 'uz', 'va', 'vc', 've', 'vg', 'vi', 'vn', 'vu', 'wf', 'ws',
        'ye', 'yt', 'yu', 'za', 'zm', 'zw'
    );
    protected $_tld;
    protected $_validIdns = array(
        'AC'  => array(1 => '/^[\x{002d}0-9a-zà-öø-ÿāăąćĉċčďđēėęěĝġģĥħīįĵķĺļľŀłńņňŋőœŕŗřśŝşšţťŧūŭůűųŵŷźżž]{1,63}$/iu'),
        'AR'  => array(1 => '/^[\x{002d}0-9a-zà-ãç-êìíñ-õü]{1,63}$/iu'),
        'AS'  => array(1 => '/^[\x{002d}0-9a-zà-öø-ÿāăąćĉċčďđēĕėęěĝğġģĥħĩīĭįıĵķĸĺļľłńņňŋōŏőœŕŗřśŝşšţťŧũūŭůűųŵŷźż]{1,63}$/iu'),
        'AT'  => array(1 => '/^[\x{002d}0-9a-zà-öø-ÿœšž]{1,63}$/iu'),
        'BIZ' => 'Zend/Validate/Hostname/Biz.php',
        'BR'  => array(1 => '/^[\x{002d}0-9a-zà-ãçéíó-õúü]{1,63}$/iu'),
        'BV'  => array(1 => '/^[\x{002d}0-9a-zàáä-éêñ-ôöøüčđńŋšŧž]{1,63}$/iu'),
        'CAT' => array(1 => '/^[\x{002d}0-9a-z·àç-éíïòóúü]{1,63}$/iu'),
        'CH'  => array(1 => '/^[\x{002d}0-9a-zà-öø-ÿœ]{1,63}$/iu'),
        'CL'  => array(1 => '/^[\x{002d}0-9a-záéíñóúü]{1,63}$/iu'),
        'CN'  => 'Zend/Validate/Hostname/Cn.php',
        'COM' => 'Zend/Validate/Hostname/Com.php',
        'DE'  => array(1 => '/^[\x{002d}0-9a-zà-öø-ÿăąāćĉčċďđĕěėęēğĝġģĥħĭĩįīıĵķĺľļłńňņŋŏőōœĸŕřŗśŝšşťţŧŭůűũųūŵŷźžż]{1,63}$/iu'),
        'DK'  => array(1 => '/^[\x{002d}0-9a-zäéöü]{1,63}$/iu'),
        'ES'  => array(1 => '/^[\x{002d}0-9a-zàáçèéíïñòóúü·]{1,63}$/iu'),
        'EU'  => array(1 => '/^[\x{002d}0-9a-zà-öø-ÿ]{1,63}$/iu',
            2 => '/^[\x{002d}0-9a-zāăąćĉċčďđēĕėęěĝğġģĥħĩīĭįıĵķĺļľŀłńņňŉŋōŏőœŕŗřśŝšťŧũūŭůűųŵŷźżž]{1,63}$/iu',
            3 => '/^[\x{002d}0-9a-zșț]{1,63}$/iu',
            4 => '/^[\x{002d}0-9a-zΐάέήίΰαβγδεζηθικλμνξοπρςστυφχψωϊϋόύώ]{1,63}$/iu',
            5 => '/^[\x{002d}0-9a-zабвгдежзийклмнопрстуфхцчшщъыьэюя]{1,63}$/iu',
            6 => '/^[\x{002d}0-9a-zἀ-ἇἐ-ἕἠ-ἧἰ-ἷὀ-ὅὐ-ὗὠ-ὧὰ-ώᾀ-ᾇᾐ-ᾗᾠ-ᾧᾰ-ᾴᾶᾷῂῃῄῆῇῐ-ΐῖῗῠ-ῧῲῳῴῶῷ]{1,63}$/iu'),
        'FI'  => array(1 => '/^[\x{002d}0-9a-zäåö]{1,63}$/iu'),
        'GR'  => array(1 => '/^[\x{002d}0-9a-zΆΈΉΊΌΎ-ΡΣ-ώἀ-ἕἘ-Ἕἠ-ὅὈ-Ὅὐ-ὗὙὛὝὟ-ώᾀ-ᾴᾶ-ᾼῂῃῄῆ-ῌῐ-ΐῖ-Ίῠ-Ῥῲῳῴῶ-ῼ]{1,63}$/iu'),
        'HK'  => 'Zend/Validate/Hostname/Cn.php',
        'HU'  => array(1 => '/^[\x{002d}0-9a-záéíóöúüőű]{1,63}$/iu'),
        'INFO'=> array(1 => '/^[\x{002d}0-9a-zäåæéöøü]{1,63}$/iu',
            2 => '/^[\x{002d}0-9a-záéíóöúüőű]{1,63}$/iu',
            3 => '/^[\x{002d}0-9a-záæéíðóöúýþ]{1,63}$/iu',
            4 => '/^[\x{AC00}-\x{D7A3}]{1,17}$/iu',
            5 => '/^[\x{002d}0-9a-zāčēģīķļņōŗšūž]{1,63}$/iu',
            6 => '/^[\x{002d}0-9a-ząčėęįšūųž]{1,63}$/iu',
            7 => '/^[\x{002d}0-9a-zóąćęłńśźż]{1,63}$/iu',
            8 => '/^[\x{002d}0-9a-záéíñóúü]{1,63}$/iu'),
        'IO'  => array(1 => '/^[\x{002d}0-9a-zà-öø-ÿăąāćĉčċďđĕěėęēğĝġģĥħĭĩįīıĵķĺľļłńňņŋŏőōœĸŕřŗśŝšşťţŧŭůűũųūŵŷźžż]{1,63}$/iu'),
        'IS'  => array(1 => '/^[\x{002d}0-9a-záéýúíóþæöð]{1,63}$/iu'),
        'JP'  => 'Zend/Validate/Hostname/Jp.php',
        'KR'  => array(1 => '/^[\x{AC00}-\x{D7A3}]{1,17}$/iu'),
        'LI'  => array(1 => '/^[\x{002d}0-9a-zà-öø-ÿœ]{1,63}$/iu'),
        'LT'  => array(1 => '/^[\x{002d}0-9ąčęėįšųūž]{1,63}$/iu'),
        'MD'  => array(1 => '/^[\x{002d}0-9ăâîşţ]{1,63}$/iu'),
        'MUSEUM' => array(1 => '/^[\x{002d}0-9a-zà-öø-ÿāăąćċčďđēėęěğġģħīįıķĺļľłńņňŋōőœŕŗřśşšţťŧūůűųŵŷźżžǎǐǒǔ\x{01E5}\x{01E7}\x{01E9}\x{01EF}ə\x{0292}ẁẃẅỳ]{1,63}$/iu'),
        'NET' => 'Zend/Validate/Hostname/Com.php',
        'NO'  => array(1 => '/^[\x{002d}0-9a-zàáä-éêñ-ôöøüčđńŋšŧž]{1,63}$/iu'),
        'NU'  => 'Zend/Validate/Hostname/Com.php',
        'ORG' => array(1 => '/^[\x{002d}0-9a-záéíñóúü]{1,63}$/iu',
            2 => '/^[\x{002d}0-9a-zóąćęłńśźż]{1,63}$/iu',
            3 => '/^[\x{002d}0-9a-záäåæéëíðóöøúüýþ]{1,63}$/iu',
            4 => '/^[\x{002d}0-9a-záéíóöúüőű]{1,63}$/iu',
            5 => '/^[\x{002d}0-9a-ząčėęįšūųž]{1,63}$/iu',
            6 => '/^[\x{AC00}-\x{D7A3}]{1,17}$/iu',
            7 => '/^[\x{002d}0-9a-zāčēģīķļņōŗšūž]{1,63}$/iu'),
        'PE'  => array(1 => '/^[\x{002d}0-9a-zñáéíóúü]{1,63}$/iu'),
        'PL'  => array(1 => '/^[\x{002d}0-9a-zāčēģīķļņōŗšūž]{1,63}$/iu',
            2 => '/^[\x{002d}а-ик-ш\x{0450}ѓѕјљњќџ]{1,63}$/iu',
            3 => '/^[\x{002d}0-9a-zâîăşţ]{1,63}$/iu',
            4 => '/^[\x{002d}0-9а-яё\x{04C2}]{1,63}$/iu',
            5 => '/^[\x{002d}0-9a-zàáâèéêìíîòóôùúûċġħż]{1,63}$/iu',
            6 => '/^[\x{002d}0-9a-zàäåæéêòóôöøü]{1,63}$/iu',
            7 => '/^[\x{002d}0-9a-zóąćęłńśźż]{1,63}$/iu',
            8 => '/^[\x{002d}0-9a-zàáâãçéêíòóôõúü]{1,63}$/iu',
            9 => '/^[\x{002d}0-9a-zâîăşţ]{1,63}$/iu',
            10=> '/^[\x{002d}0-9a-záäéíóôúýčďĺľňŕšťž]{1,63}$/iu',
            11=> '/^[\x{002d}0-9a-zçë]{1,63}$/iu',
            12=> '/^[\x{002d}0-9а-ик-шђјљњћџ]{1,63}$/iu',
            13=> '/^[\x{002d}0-9a-zćčđšž]{1,63}$/iu',
            14=> '/^[\x{002d}0-9a-zâçöûüğış]{1,63}$/iu',
            15=> '/^[\x{002d}0-9a-záéíñóúü]{1,63}$/iu',
            16=> '/^[\x{002d}0-9a-zäõöüšž]{1,63}$/iu',
            17=> '/^[\x{002d}0-9a-zĉĝĥĵŝŭ]{1,63}$/iu',
            18=> '/^[\x{002d}0-9a-zâäéëîô]{1,63}$/iu',
            19=> '/^[\x{002d}0-9a-zàáâäåæçèéêëìíîïðñòôöøùúûüýćčłńřśš]{1,63}$/iu',
            20=> '/^[\x{002d}0-9a-zäåæõöøüšž]{1,63}$/iu',
            21=> '/^[\x{002d}0-9a-zàáçèéìíòóùú]{1,63}$/iu',
            22=> '/^[\x{002d}0-9a-zàáéíóöúüőű]{1,63}$/iu',
            23=> '/^[\x{002d}0-9ΐά-ώ]{1,63}$/iu',
            24=> '/^[\x{002d}0-9a-zàáâåæçèéêëðóôöøüþœ]{1,63}$/iu',
            25=> '/^[\x{002d}0-9a-záäéíóöúüýčďěňřšťůž]{1,63}$/iu',
            26=> '/^[\x{002d}0-9a-z·àçèéíïòóúü]{1,63}$/iu',
            27=> '/^[\x{002d}0-9а-ъьюя\x{0450}\x{045D}]{1,63}$/iu',
            28=> '/^[\x{002d}0-9а-яёіў]{1,63}$/iu',
            29=> '/^[\x{002d}0-9a-ząčėęįšūųž]{1,63}$/iu',
            30=> '/^[\x{002d}0-9a-záäåæéëíðóöøúüýþ]{1,63}$/iu',
            31=> '/^[\x{002d}0-9a-zàâæçèéêëîïñôùûüÿœ]{1,63}$/iu',
            32=> '/^[\x{002d}0-9а-щъыьэюяёєіїґ]{1,63}$/iu',
            33=> '/^[\x{002d}0-9א-ת]{1,63}$/iu'),
        'PR'  => array(1 => '/^[\x{002d}0-9a-záéíóúñäëïüöâêîôûàèùæçœãõ]{1,63}$/iu'),
        'PT'  => array(1 => '/^[\x{002d}0-9a-záàâãçéêíóôõú]{1,63}$/iu'),
        'RU'  => array(1 => '/^[\x{002d}0-9а-яё]{1,63}$/iu'),
        'SA'  => array(1 => '/^[\x{002d}.0-9\x{0621}-\x{063A}\x{0641}-\x{064A}\x{0660}-\x{0669}]{1,63}$/iu'),
        'SE'  => array(1 => '/^[\x{002d}0-9a-zäåéöü]{1,63}$/iu'),
        'SH'  => array(1 => '/^[\x{002d}0-9a-zà-öø-ÿăąāćĉčċďđĕěėęēğĝġģĥħĭĩįīıĵķĺľļłńňņŋŏőōœĸŕřŗśŝšşťţŧŭůűũųūŵŷźžż]{1,63}$/iu'),
        'SJ'  => array(1 => '/^[\x{002d}0-9a-zàáä-éêñ-ôöøüčđńŋšŧž]{1,63}$/iu'),
        'TH'  => array(1 => '/^[\x{002d}0-9a-z\x{0E01}-\x{0E3A}\x{0E40}-\x{0E4D}\x{0E50}-\x{0E59}]{1,63}$/iu'),
        'TM'  => array(1 => '/^[\x{002d}0-9a-zà-öø-ÿāăąćĉċčďđēėęěĝġģĥħīįĵķĺļľŀłńņňŋőœŕŗřśŝşšţťŧūŭůűųŵŷźżž]{1,63}$/iu'),
        'TW'  => 'Zend/Validate/Hostname/Cn.php',
        'TR'  => array(1 => '/^[\x{002d}0-9a-zğıüşöç]{1,63}$/iu'),
        'VE'  => array(1 => '/^[\x{002d}0-9a-záéíóúüñ]{1,63}$/iu'),
        'VN'  => array(1 => '/^[ÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝàáâãèéêìíòóôõùúýĂăĐđĨĩŨũƠơƯư\x{1EA0}-\x{1EF9}]{1,63}$/iu'),
        'ایران' => array(1 => '/^[\x{0621}-\x{0624}\x{0626}-\x{063A}\x{0641}\x{0642}\x{0644}-\x{0648}\x{067E}\x{0686}\x{0698}\x{06A9}\x{06AF}\x{06CC}\x{06F0}-\x{06F9}]{1,30}$/iu'),
        '中国' => 'Zend/Validate/Hostname/Cn.php',
        '公司' => 'Zend/Validate/Hostname/Cn.php',
        '网络' => 'Zend/Validate/Hostname/Cn.php'
    );
    protected $_idnLength = array(
        'BIZ' => array(5 => 17, 11 => 15, 12 => 20),
        'CN'  => array(1 => 20),
        'COM' => array(3 => 17, 5 => 20),
        'HK'  => array(1 => 15),
        'INFO'=> array(4 => 17),
        'KR'  => array(1 => 17),
        'NET' => array(3 => 17, 5 => 20),
        'ORG' => array(6 => 17),
        'TW'  => array(1 => 20),
        'ایران' => array(1 => 30),
        '中国' => array(1 => 20),
        '公司' => array(1 => 20),
        '网络' => array(1 => 20),
    );
    protected $_options = array(
        'allow' => self::ALLOW_DNS,
        'idn'   => true,
        'tld'   => true,
        'ip'    => null
    );
    public function __construct($options = array())
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } else if (!is_array($options)) {
            $options = func_get_args();
            $temp['allow'] = array_shift($options);
            if (!empty($options)) {
                $temp['idn'] = array_shift($options);
            }
            if (!empty($options)) {
                $temp['tld'] = array_shift($options);
            }
            if (!empty($options)) {
                $temp['ip'] = array_shift($options);
            }
            $options = $temp;
        }
        $options += $this->_options;
        $this->setOptions($options);
    }
    public function getOptions()
    {
        return $this->_options;
    }
    public function setOptions($options)
    {
        if (array_key_exists('allow', $options)) {
            $this->setAllow($options['allow']);
        }
        if (array_key_exists('idn', $options)) {
            $this->setValidateIdn($options['idn']);
        }
        if (array_key_exists('tld', $options)) {
            $this->setValidateTld($options['tld']);
        }
        if (array_key_exists('ip', $options)) {
            $this->setIpValidator($options['ip']);
        }
        return $this;
    }
    public function getIpValidator()
    {
        return $this->_options['ip'];
    }
    public function setIpValidator(Zend_Validate_Ip $ipValidator = null)
    {
        if ($ipValidator === null) {
            $ipValidator = new Zend_Validate_Ip();
        }
        $this->_options['ip'] = $ipValidator;
        return $this;
    }
    public function getAllow()
    {
        return $this->_options['allow'];
    }
    public function setAllow($allow)
    {
        $this->_options['allow'] = $allow;
        return $this;
    }
    public function getValidateIdn()
    {
        return $this->_options['idn'];
    }
    public function setValidateIdn ($allowed)
    {
        $this->_options['idn'] = (bool) $allowed;
        return $this;
    }
    public function getValidateTld()
    {
        return $this->_options['tld'];
    }
    public function setValidateTld ($allowed)
    {
        $this->_options['tld'] = (bool) $allowed;
        return $this;
    }
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->_error(self::INVALID);
            return false;
        }
        $this->_setValue($value);
        if (preg_match('/^[0-9.a-e:.]*$/i', $value) &&
            $this->_options['ip']->setTranslator($this->getTranslator())->isValid($value)) {
            if (!($this->_options['allow'] & self::ALLOW_IP)) {
                $this->_error(self::IP_ADDRESS_NOT_ALLOWED);
                return false;
            } else {
                return true;
            }
        }
        $domainParts = explode('.', $value);
        if ((count($domainParts) > 1) && (strlen($value) >= 4) && (strlen($value) <= 254)) {
            $status = false;
            $origenc = iconv_get_encoding('internal_encoding');
            iconv_set_encoding('internal_encoding', 'UTF-8');
            do {
                $matches = array();
                if (preg_match('/([^.]{2,10})$/i', end($domainParts), $matches) ||
                    (end($domainParts) == 'ایران') || (end($domainParts) == '中国') ||
                    (end($domainParts) == '公司') || (end($domainParts) == '网络')) {
                    reset($domainParts);
                    $this->_tld = strtolower($matches[1]);
                    if ($this->_options['tld']) {
                        if (!in_array($this->_tld, $this->_validTlds)) {
                            $this->_error(self::UNKNOWN_TLD);
                            $status = false;
                            break;
                        }
                    }
                    $regexChars = array(0 => '/^[a-z0-9\x2d]{1,63}$/i');
                    if ($this->_options['idn'] &&  isset($this->_validIdns[strtoupper($this->_tld)])) {
                        if (is_string($this->_validIdns[strtoupper($this->_tld)])) {
                            if ( $this->_validIdns[strtoupper($this->_tld)] == 'Zend/Validate/Hostname/Com.php' ) $regexChars += array(
    1  => '/^[\x{002d}0-9\x{0400}-\x{052f}]{1,63}$/iu',
    2  => '/^[\x{002d}0-9\x{0370}-\x{03ff}]{1,63}$/iu',
    3  => '/^[\x{002d}0-9a-z\x{ac00}-\x{d7a3}]{1,17}$/iu',
    4  => '/^[\x{002d}0-9a-zВ·Г -Г¶Гё-ГїДЃДѓД…Д‡Д‰Д‹ДЌДЏД‘Д“Д•Д—Д™Д›ДќДџДЎДЈДҐД§Д©Д«Д­ДЇД±ДµД·ДёДєДјДѕЕ‚Е„Е†Е€Е‹ЕЌЕЏЕ‘Е“Е•Е—Е™Е›ЕќЕџЕЎЕЈЕҐЕ§Е©Е«Е­ЕЇЕ±ЕіЕµЕ·ЕєЕјЕѕ]{1,63}$/iu',
    5  => '/^[\x{002d}0-9A-Za-z\x{3400}-\x{3401}\x{3404}-\x{3406}\x{340C}\x{3416}\x{341C}' .
'\x{3421}\x{3424}\x{3428}-\x{3429}\x{342B}-\x{342E}\x{3430}-\x{3434}\x{3436}' .
'\x{3438}-\x{343C}\x{343E}\x{3441}-\x{3445}\x{3447}\x{3449}-\x{3451}\x{3453}' .
'\x{3457}-\x{345F}\x{3463}-\x{3467}\x{346E}-\x{3471}\x{3473}-\x{3477}\x{3479}-\x{348E}\x{3491}-\x{3497}' .
'\x{3499}-\x{34A1}\x{34A4}-\x{34AD}\x{34AF}-\x{34B0}\x{34B2}-\x{34BF}\x{34C2}-\x{34C5}\x{34C7}-\x{34CC}' .
'\x{34CE}-\x{34D1}\x{34D3}-\x{34D8}\x{34DA}-\x{34E4}\x{34E7}-\x{34E9}\x{34EC}-\x{34EF}\x{34F1}-\x{34FE}' .
'\x{3500}-\x{3507}\x{350A}-\x{3513}\x{3515}\x{3517}-\x{351A}\x{351C}-\x{351E}\x{3520}-\x{352A}' .
'\x{352C}-\x{3552}\x{3554}-\x{355C}\x{355E}-\x{3567}\x{3569}-\x{3573}\x{3575}-\x{357C}\x{3580}-\x{3588}' .
'\x{358F}-\x{3598}\x{359E}-\x{35AB}\x{35B4}-\x{35CD}\x{35D0}\x{35D3}-\x{35DC}\x{35E2}-\x{35ED}' .
'\x{35F0}-\x{35F6}\x{35FB}-\x{3602}\x{3605}-\x{360E}\x{3610}-\x{3611}\x{3613}-\x{3616}\x{3619}-\x{362D}' .
'\x{362F}-\x{3634}\x{3636}-\x{363B}\x{363F}-\x{3645}\x{3647}-\x{364B}\x{364D}-\x{3653}\x{3655}' .
'\x{3659}-\x{365E}\x{3660}-\x{3665}\x{3667}-\x{367C}\x{367E}\x{3680}-\x{3685}\x{3687}' .
'\x{3689}-\x{3690}\x{3692}-\x{3698}\x{369A}\x{369C}-\x{36AE}\x{36B0}-\x{36BF}\x{36C1}-\x{36C5}' .
'\x{36C9}-\x{36CA}\x{36CD}-\x{36DE}\x{36E1}-\x{36E2}\x{36E5}-\x{36FE}\x{3701}-\x{3713}\x{3715}-\x{371E}' .
'\x{3720}-\x{372C}\x{372E}-\x{3745}\x{3747}-\x{3748}\x{374A}\x{374C}-\x{3759}\x{375B}-\x{3760}' .
'\x{3762}-\x{3767}\x{3769}-\x{3772}\x{3774}-\x{378C}\x{378F}-\x{379C}\x{379F}\x{37A1}-\x{37AD}' .
'\x{37AF}-\x{37B7}\x{37B9}-\x{37C1}\x{37C3}-\x{37C5}\x{37C7}-\x{37D4}\x{37D6}-\x{37E0}\x{37E2}' .
'\x{37E5}-\x{37ED}\x{37EF}-\x{37F6}\x{37F8}-\x{3802}\x{3804}-\x{381D}\x{3820}-\x{3822}\x{3825}-\x{382A}' .
'\x{382D}-\x{382F}\x{3831}-\x{3832}\x{3834}-\x{384C}\x{384E}-\x{3860}\x{3862}-\x{3863}\x{3865}-\x{386B}' .
'\x{386D}-\x{3886}\x{3888}-\x{38A1}\x{38A3}\x{38A5}-\x{38AA}\x{38AC}\x{38AE}-\x{38B0}' .
'\x{38B2}-\x{38B6}\x{38B8}\x{38BA}-\x{38BE}\x{38C0}-\x{38C9}\x{38CB}-\x{38D4}\x{38D8}-\x{38E0}' .
'\x{38E2}-\x{38E6}\x{38EB}-\x{38ED}\x{38EF}-\x{38F2}\x{38F5}-\x{38F7}\x{38FA}-\x{38FF}\x{3901}-\x{392A}' .
'\x{392C}\x{392E}-\x{393B}\x{393E}-\x{3956}\x{395A}-\x{3969}\x{396B}-\x{397A}\x{397C}-\x{3987}' .
'\x{3989}-\x{3998}\x{399A}-\x{39B0}\x{39B2}\x{39B4}-\x{39D0}\x{39D2}-\x{39DA}\x{39DE}-\x{39DF}' .
'\x{39E1}-\x{39EF}\x{39F1}-\x{3A17}\x{3A19}-\x{3A2A}\x{3A2D}-\x{3A40}\x{3A43}-\x{3A4E}\x{3A50}' .
'\x{3A52}-\x{3A5E}\x{3A60}-\x{3A6D}\x{3A6F}-\x{3A77}\x{3A79}-\x{3A82}\x{3A84}-\x{3A85}\x{3A87}-\x{3A89}' .
'\x{3A8B}-\x{3A8F}\x{3A91}-\x{3A93}\x{3A95}-\x{3A96}\x{3A9A}\x{3A9C}-\x{3AA6}\x{3AA8}-\x{3AA9}' .
'\x{3AAB}-\x{3AB1}\x{3AB4}-\x{3ABC}\x{3ABE}-\x{3AC5}\x{3ACA}-\x{3ACB}\x{3ACD}-\x{3AD5}\x{3AD7}-\x{3AE1}' .
'\x{3AE4}-\x{3AE7}\x{3AE9}-\x{3AEC}\x{3AEE}-\x{3AFD}\x{3B01}-\x{3B10}\x{3B12}-\x{3B15}\x{3B17}-\x{3B1E}' .
'\x{3B20}-\x{3B23}\x{3B25}-\x{3B27}\x{3B29}-\x{3B36}\x{3B38}-\x{3B39}\x{3B3B}-\x{3B3C}\x{3B3F}' .
'\x{3B41}-\x{3B44}\x{3B47}-\x{3B4C}\x{3B4E}\x{3B51}-\x{3B55}\x{3B58}-\x{3B62}\x{3B68}-\x{3B72}' .
'\x{3B78}-\x{3B88}\x{3B8B}-\x{3B9F}\x{3BA1}\x{3BA3}-\x{3BBA}\x{3BBC}\x{3BBF}-\x{3BD0}' .
'\x{3BD3}-\x{3BE6}\x{3BEA}-\x{3BFB}\x{3BFE}-\x{3C12}\x{3C14}-\x{3C1B}\x{3C1D}-\x{3C37}\x{3C39}-\x{3C4F}' .
'\x{3C52}\x{3C54}-\x{3C5C}\x{3C5E}-\x{3C68}\x{3C6A}-\x{3C76}\x{3C78}-\x{3C8F}\x{3C91}-\x{3CA8}' .
'\x{3CAA}-\x{3CAD}\x{3CAF}-\x{3CBE}\x{3CC0}-\x{3CC8}\x{3CCA}-\x{3CD3}\x{3CD6}-\x{3CE0}\x{3CE4}-\x{3CEE}' .
'\x{3CF3}-\x{3D0A}\x{3D0E}-\x{3D1E}\x{3D20}-\x{3D21}\x{3D25}-\x{3D38}\x{3D3B}-\x{3D46}\x{3D4A}-\x{3D59}' .
'\x{3D5D}-\x{3D7B}\x{3D7D}-\x{3D81}\x{3D84}-\x{3D88}\x{3D8C}-\x{3D8F}\x{3D91}-\x{3D98}\x{3D9A}-\x{3D9C}' .
'\x{3D9E}-\x{3DA1}\x{3DA3}-\x{3DB0}\x{3DB2}-\x{3DB5}\x{3DB9}-\x{3DBC}\x{3DBE}-\x{3DCB}\x{3DCD}-\x{3DDB}' .
'\x{3DDF}-\x{3DE8}\x{3DEB}-\x{3DF0}\x{3DF3}-\x{3DF9}\x{3DFB}-\x{3DFC}\x{3DFE}-\x{3E05}\x{3E08}-\x{3E33}' .
'\x{3E35}-\x{3E3E}\x{3E40}-\x{3E47}\x{3E49}-\x{3E67}\x{3E6B}-\x{3E6F}\x{3E71}-\x{3E85}\x{3E87}-\x{3E8C}' .
'\x{3E8E}-\x{3E98}\x{3E9A}-\x{3EA1}\x{3EA3}-\x{3EAE}\x{3EB0}-\x{3EB5}\x{3EB7}-\x{3EBA}\x{3EBD}' .
'\x{3EBF}-\x{3EC4}\x{3EC7}-\x{3ECE}\x{3ED1}-\x{3ED7}\x{3ED9}-\x{3EDA}\x{3EDD}-\x{3EE3}\x{3EE7}-\x{3EE8}' .
'\x{3EEB}-\x{3EF2}\x{3EF5}-\x{3EFF}\x{3F01}-\x{3F02}\x{3F04}-\x{3F07}\x{3F09}-\x{3F44}\x{3F46}-\x{3F4E}' .
'\x{3F50}-\x{3F53}\x{3F55}-\x{3F72}\x{3F74}-\x{3F75}\x{3F77}-\x{3F7B}\x{3F7D}-\x{3FB0}\x{3FB6}-\x{3FBF}' .
'\x{3FC1}-\x{3FCF}\x{3FD1}-\x{3FD3}\x{3FD5}-\x{3FDF}\x{3FE1}-\x{400B}\x{400D}-\x{401C}\x{401E}-\x{4024}' .
'\x{4027}-\x{403F}\x{4041}-\x{4060}\x{4062}-\x{4069}\x{406B}-\x{408A}\x{408C}-\x{40A7}\x{40A9}-\x{40B4}' .
'\x{40B6}-\x{40C2}\x{40C7}-\x{40CF}\x{40D1}-\x{40DE}\x{40E0}-\x{40E7}\x{40E9}-\x{40EE}\x{40F0}-\x{40FB}' .
'\x{40FD}-\x{4109}\x{410B}-\x{4115}\x{4118}-\x{411D}\x{411F}-\x{4122}\x{4124}-\x{4133}\x{4136}-\x{4138}' .
'\x{413A}-\x{4148}\x{414A}-\x{4169}\x{416C}-\x{4185}\x{4188}-\x{418B}\x{418D}-\x{41AD}\x{41AF}-\x{41B3}' .
'\x{41B5}-\x{41C3}\x{41C5}-\x{41C9}\x{41CB}-\x{41F2}\x{41F5}-\x{41FE}\x{4200}-\x{4227}\x{422A}-\x{4246}' .
'\x{4248}-\x{4263}\x{4265}-\x{428B}\x{428D}-\x{42A1}\x{42A3}-\x{42C4}\x{42C8}-\x{42DC}\x{42DE}-\x{430A}' .
'\x{430C}-\x{4335}\x{4337}\x{4342}-\x{435F}\x{4361}-\x{439A}\x{439C}-\x{439D}\x{439F}-\x{43A4}' .
'\x{43A6}-\x{43EC}\x{43EF}-\x{4405}\x{4407}-\x{4429}\x{442B}-\x{4455}\x{4457}-\x{4468}\x{446A}-\x{446D}' .
'\x{446F}-\x{4476}\x{4479}-\x{447D}\x{447F}-\x{4486}\x{4488}-\x{4490}\x{4492}-\x{4498}\x{449A}-\x{44AD}' .
'\x{44B0}-\x{44BD}\x{44C1}-\x{44D3}\x{44D6}-\x{44E7}\x{44EA}\x{44EC}-\x{44FA}\x{44FC}-\x{4541}' .
'\x{4543}-\x{454F}\x{4551}-\x{4562}\x{4564}-\x{4575}\x{4577}-\x{45AB}\x{45AD}-\x{45BD}\x{45BF}-\x{45D5}' .
'\x{45D7}-\x{45EC}\x{45EE}-\x{45F2}\x{45F4}-\x{45FA}\x{45FC}-\x{461A}\x{461C}-\x{461D}\x{461F}-\x{4631}' .
'\x{4633}-\x{4649}\x{464C}\x{464E}-\x{4652}\x{4654}-\x{466A}\x{466C}-\x{4675}\x{4677}-\x{467A}' .
'\x{467C}-\x{4694}\x{4696}-\x{46A3}\x{46A5}-\x{46AB}\x{46AD}-\x{46D2}\x{46D4}-\x{4723}\x{4729}-\x{4732}' .
'\x{4734}-\x{4758}\x{475A}\x{475C}-\x{478B}\x{478D}\x{4791}-\x{47B1}\x{47B3}-\x{47F1}' .
'\x{47F3}-\x{480B}\x{480D}-\x{4815}\x{4817}-\x{4839}\x{483B}-\x{4870}\x{4872}-\x{487A}\x{487C}-\x{487F}' .
'\x{4883}-\x{488E}\x{4890}-\x{4896}\x{4899}-\x{48A2}\x{48A4}-\x{48B9}\x{48BB}-\x{48C8}\x{48CA}-\x{48D1}' .
'\x{48D3}-\x{48E5}\x{48E7}-\x{48F2}\x{48F4}-\x{48FF}\x{4901}-\x{4922}\x{4924}-\x{4928}\x{492A}-\x{4931}' .
'\x{4933}-\x{495B}\x{495D}-\x{4978}\x{497A}\x{497D}\x{4982}-\x{4983}\x{4985}-\x{49A8}' .
'\x{49AA}-\x{49AF}\x{49B1}-\x{49B7}\x{49B9}-\x{49BD}\x{49C1}-\x{49C7}\x{49C9}-\x{49CE}\x{49D0}-\x{49E8}' .
'\x{49EA}\x{49EC}\x{49EE}-\x{4A19}\x{4A1B}-\x{4A43}\x{4A45}-\x{4A4D}\x{4A4F}-\x{4A9E}' .
'\x{4AA0}-\x{4AA9}\x{4AAB}-\x{4B4E}\x{4B50}-\x{4B5B}\x{4B5D}-\x{4B69}\x{4B6B}-\x{4BC2}\x{4BC6}-\x{4BE8}' .
'\x{4BEA}-\x{4BFA}\x{4BFC}-\x{4C06}\x{4C08}-\x{4C2D}\x{4C2F}-\x{4C32}\x{4C34}-\x{4C35}\x{4C37}-\x{4C69}' .
'\x{4C6B}-\x{4C73}\x{4C75}-\x{4C86}\x{4C88}-\x{4C97}\x{4C99}-\x{4C9C}\x{4C9F}-\x{4CA3}\x{4CA5}-\x{4CB5}' .
'\x{4CB7}-\x{4CF8}\x{4CFA}-\x{4D27}\x{4D29}-\x{4DAC}\x{4DAE}-\x{4DB1}\x{4DB3}-\x{4DB5}\x{4E00}-\x{4E54}' .
'\x{4E56}-\x{4E89}\x{4E8B}-\x{4EEC}\x{4EEE}-\x{4FAC}\x{4FAE}-\x{503C}\x{503E}-\x{51E5}\x{51E7}-\x{5270}' .
'\x{5272}-\x{56A1}\x{56A3}-\x{5840}\x{5842}-\x{58B5}\x{58B7}-\x{58CB}\x{58CD}-\x{5BC8}\x{5BCA}-\x{5C01}' .
'\x{5C03}-\x{5C25}\x{5C27}-\x{5D5B}\x{5D5D}-\x{5F08}\x{5F0A}-\x{61F3}\x{61F5}-\x{63BA}\x{63BC}-\x{6441}' .
'\x{6443}-\x{657C}\x{657E}-\x{663E}\x{6640}-\x{66FC}\x{66FE}-\x{6728}\x{672A}-\x{6766}\x{6768}-\x{67A8}' .
'\x{67AA}-\x{685B}\x{685D}-\x{685E}\x{6860}-\x{68B9}\x{68BB}-\x{6AC8}\x{6ACA}-\x{6BB0}\x{6BB2}-\x{6C16}' .
'\x{6C18}-\x{6D9B}\x{6D9D}-\x{6E12}\x{6E14}-\x{6E8B}\x{6E8D}-\x{704D}\x{704F}-\x{7113}\x{7115}-\x{713B}' .
'\x{713D}-\x{7154}\x{7156}-\x{729F}\x{72A1}-\x{731E}\x{7320}-\x{7362}\x{7364}-\x{7533}\x{7535}-\x{7551}' .
'\x{7553}-\x{7572}\x{7574}-\x{75E8}\x{75EA}-\x{7679}\x{767B}-\x{783E}\x{7840}-\x{7A62}\x{7A64}-\x{7AC2}' .
'\x{7AC4}-\x{7B06}\x{7B08}-\x{7B79}\x{7B7B}-\x{7BCE}\x{7BD0}-\x{7D99}\x{7D9B}-\x{7E49}\x{7E4C}-\x{8132}' .
'\x{8134}\x{8136}-\x{81D2}\x{81D4}-\x{8216}\x{8218}-\x{822D}\x{822F}-\x{83B4}\x{83B6}-\x{841F}' .
'\x{8421}-\x{86CC}\x{86CE}-\x{874A}\x{874C}-\x{877E}\x{8780}-\x{8A32}\x{8A34}-\x{8B71}\x{8B73}-\x{8B8E}' .
'\x{8B90}-\x{8DE4}\x{8DE6}-\x{8E9A}\x{8E9C}-\x{8EE1}\x{8EE4}-\x{8F0B}\x{8F0D}-\x{8FB9}\x{8FBB}-\x{9038}' .
'\x{903A}-\x{9196}\x{9198}-\x{91A3}\x{91A5}-\x{91B7}\x{91B9}-\x{91C7}\x{91C9}-\x{91E0}\x{91E2}-\x{91FB}' .
'\x{91FD}-\x{922B}\x{922D}-\x{9270}\x{9272}-\x{9420}\x{9422}-\x{9664}\x{9666}-\x{9679}\x{967B}-\x{9770}' .
'\x{9772}-\x{982B}\x{982D}-\x{98ED}\x{98EF}-\x{99C4}\x{99C6}-\x{9A11}\x{9A14}-\x{9A27}\x{9A29}-\x{9D0D}' .
'\x{9D0F}-\x{9D2B}\x{9D2D}-\x{9D8E}\x{9D90}-\x{9DC5}\x{9DC7}-\x{9E77}\x{9E79}-\x{9EB8}\x{9EBB}-\x{9F20}' .
'\x{9F22}-\x{9F61}\x{9F63}-\x{9FA5}\x{FA28}]{1,20}$/iu',
    6 => '/^[\x{002d}0-9A-Za-z]{1,63}$/iu',
    7 => '/^[\x{00A1}-\x{00FF}]{1,63}$/iu',
    8 => '/^[\x{0100}-\x{017f}]{1,63}$/iu',
    9 => '/^[\x{0180}-\x{024f}]{1,63}$/iu',
    10 => '/^[\x{0250}-\x{02af}]{1,63}$/iu',
    11 => '/^[\x{02b0}-\x{02ff}]{1,63}$/iu',
    12 => '/^[\x{0300}-\x{036f}]{1,63}$/iu',
    13 => '/^[\x{0370}-\x{03ff}]{1,63}$/iu',
    14 => '/^[\x{0400}-\x{04ff}]{1,63}$/iu',
    15 => '/^[\x{0500}-\x{052f}]{1,63}$/iu',
    16 => '/^[\x{0530}-\x{058F}]{1,63}$/iu',
    17 => '/^[\x{0590}-\x{05FF}]{1,63}$/iu',
    18 => '/^[\x{0600}-\x{06FF}]{1,63}$/iu',
    19 => '/^[\x{0700}-\x{074F}]{1,63}$/iu',
    20 => '/^[\x{0780}-\x{07BF}]{1,63}$/iu',
    21 => '/^[\x{0900}-\x{097F}]{1,63}$/iu',
    22 => '/^[\x{0980}-\x{09FF}]{1,63}$/iu',
    23 => '/^[\x{0A00}-\x{0A7F}]{1,63}$/iu',
    24 => '/^[\x{0A80}-\x{0AFF}]{1,63}$/iu',
    25 => '/^[\x{0B00}-\x{0B7F}]{1,63}$/iu',
    26 => '/^[\x{0B80}-\x{0BFF}]{1,63}$/iu',
    27 => '/^[\x{0C00}-\x{0C7F}]{1,63}$/iu',
    28 => '/^[\x{0C80}-\x{0CFF}]{1,63}$/iu',
    29 => '/^[\x{0D00}-\x{0D7F}]{1,63}$/iu',
    30 => '/^[\x{0D80}-\x{0DFF}]{1,63}$/iu',
    31 => '/^[\x{0E00}-\x{0E7F}]{1,63}$/iu',
    32 => '/^[\x{0E80}-\x{0EFF}]{1,63}$/iu',
    33 => '/^[\x{0F00}-\x{0FFF}]{1,63}$/iu',
    34 => '/^[\x{1000}-\x{109F}]{1,63}$/iu',
    35 => '/^[\x{10A0}-\x{10FF}]{1,63}$/iu',
    36 => '/^[\x{1100}-\x{11FF}]{1,63}$/iu',
    37 => '/^[\x{1200}-\x{137F}]{1,63}$/iu',
    38 => '/^[\x{13A0}-\x{13FF}]{1,63}$/iu',
    39 => '/^[\x{1400}-\x{167F}]{1,63}$/iu',
    40 => '/^[\x{1680}-\x{169F}]{1,63}$/iu',
    41 => '/^[\x{16A0}-\x{16FF}]{1,63}$/iu',
    42 => '/^[\x{1700}-\x{171F}]{1,63}$/iu',
    43 => '/^[\x{1720}-\x{173F}]{1,63}$/iu',
    44 => '/^[\x{1740}-\x{175F}]{1,63}$/iu',
    45 => '/^[\x{1760}-\x{177F}]{1,63}$/iu',
    46 => '/^[\x{1780}-\x{17FF}]{1,63}$/iu',
    47 => '/^[\x{1800}-\x{18AF}]{1,63}$/iu',
    48 => '/^[\x{1E00}-\x{1EFF}]{1,63}$/iu',
    49 => '/^[\x{1F00}-\x{1FFF}]{1,63}$/iu',
    50 => '/^[\x{2070}-\x{209F}]{1,63}$/iu',
    51 => '/^[\x{2100}-\x{214F}]{1,63}$/iu',
    52 => '/^[\x{2150}-\x{218F}]{1,63}$/iu',
    53 => '/^[\x{2460}-\x{24FF}]{1,63}$/iu',
    54 => '/^[\x{2E80}-\x{2EFF}]{1,63}$/iu',
    55 => '/^[\x{2F00}-\x{2FDF}]{1,63}$/iu',
    56 => '/^[\x{2FF0}-\x{2FFF}]{1,63}$/iu',
    57 => '/^[\x{3040}-\x{309F}]{1,63}$/iu',
    58 => '/^[\x{30A0}-\x{30FF}]{1,63}$/iu',
    59 => '/^[\x{3100}-\x{312F}]{1,63}$/iu',
    60 => '/^[\x{3130}-\x{318F}]{1,63}$/iu',
    61 => '/^[\x{3190}-\x{319F}]{1,63}$/iu',
    62 => '/^[\x{31A0}-\x{31BF}]{1,63}$/iu',
    63 => '/^[\x{31F0}-\x{31FF}]{1,63}$/iu',
    64 => '/^[\x{3200}-\x{32FF}]{1,63}$/iu',
    65 => '/^[\x{3300}-\x{33FF}]{1,63}$/iu',
    66 => '/^[\x{3400}-\x{4DBF}]{1,63}$/iu',
    67 => '/^[\x{4E00}-\x{9FFF}]{1,63}$/iu',
    68 => '/^[\x{A000}-\x{A48F}]{1,63}$/iu',
    69 => '/^[\x{A490}-\x{A4CF}]{1,63}$/iu',
    70 => '/^[\x{AC00}-\x{D7AF}]{1,63}$/iu',
    71 => '/^[\x{D800}-\x{DB7F}]{1,63}$/iu',
    72 => '/^[\x{DC00}-\x{DFFF}]{1,63}$/iu',
    73 => '/^[\x{F900}-\x{FAFF}]{1,63}$/iu',
    74 => '/^[\x{FB00}-\x{FB4F}]{1,63}$/iu',
    75 => '/^[\x{FB50}-\x{FDFF}]{1,63}$/iu',
    76 => '/^[\x{FE20}-\x{FE2F}]{1,63}$/iu',
    77 => '/^[\x{FE70}-\x{FEFF}]{1,63}$/iu',
    78 => '/^[\x{FF00}-\x{FFEF}]{1,63}$/iu',
    79 => '/^[\x{20000}-\x{2A6DF}]{1,63}$/iu',
    80 => '/^[\x{2F800}-\x{2FA1F}]{1,63}$/iu'
);
                        } else {
                            $regexChars += $this->_validIdns[strtoupper($this->_tld)];
                        }
                    }
                    $check = 0;
                    foreach ($domainParts as $domainPart) {
                        if (strpos($domainPart, 'xn--') === 0) {
                            $domainPart = $this->decodePunycode(substr($domainPart, 4));
                            if ($domainPart === false) {
                                return false;
                            }
                        }
                        if ((strpos($domainPart, '-') === 0)
                            || ((strlen($domainPart) > 2) && (strpos($domainPart, '-', 2) == 2) && (strpos($domainPart, '-', 3) == 3))
                            || (strpos($domainPart, '-') === (strlen($domainPart) - 1))) {
                                $this->_error(self::INVALID_DASH);
                            $status = false;
                            break 2;
                        }
                        $checked = false;
                        foreach($regexChars as $regexKey => $regexChar) {
                            $status = @preg_match($regexChar, $domainPart);
                            if ($status > 0) {
                                $length = 63;
                                if (array_key_exists(strtoupper($this->_tld), $this->_idnLength)
                                    && (array_key_exists($regexKey, $this->_idnLength[strtoupper($this->_tld)]))) {
                                    $length = $this->_idnLength[strtoupper($this->_tld)];
                                }
                                if (iconv_strlen($domainPart, 'UTF-8') > $length) {
                                    $this->_error(self::INVALID_HOSTNAME);
                                } else {
                                    $checked = true;
                                    break;
                                }
                            }
                        }
                        if ($checked) {
                            ++$check;
                        }
                    }
                    if ($check !== count($domainParts)) {
                        $this->_error(self::INVALID_HOSTNAME_SCHEMA);
                        $status = false;
                    }
                } else {
                    $this->_error(self::UNDECIPHERABLE_TLD);
                    $status = false;
                }
            } while (false);
            iconv_set_encoding('internal_encoding', $origenc);
            if ($status && ($this->_options['allow'] & self::ALLOW_DNS)) {
                return true;
            }
        } else if ($this->_options['allow'] & self::ALLOW_DNS) {
            $this->_error(self::INVALID_HOSTNAME);
        }
        if ($this->_options['allow'] & self::ALLOW_URI) {
            if (preg_match("/^([a-zA-Z0-9-._~!$&\'()*+,;=]|%[[:xdigit:]]{2}){1,254}$/i", $value)) {
                return true;
            } else {
                $this->_error(self::INVALID_URI);
            }
        }
        $regexLocal = '/^(([a-zA-Z0-9\x2d]{1,63}\x2e)*[a-zA-Z0-9\x2d]{1,63}){1,254}$/';
        $status = @preg_match($regexLocal, $value);
        $allowLocal = $this->_options['allow'] & self::ALLOW_LOCAL;
        if ($status && $allowLocal) {
            return true;
        }
        if (!$status) {
            $this->_error(self::INVALID_LOCAL_NAME);
        }
        if ($status && !$allowLocal) {
            $this->_error(self::LOCAL_NAME_NOT_ALLOWED);
        }
        return false;
    }
    protected function decodePunycode($encoded)
    {
        $found = preg_match('/([^a-z0-9\x2d]{1,10})$/i', $encoded);
        if (empty($encoded) || ($found > 0)) {
            $this->_error(self::CANNOT_DECODE_PUNYCODE);
            return false;
        }
        $separator = strrpos($encoded, '-');
        if ($separator > 0) {
            for ($x = 0; $x < $separator; ++$x) {
                $decoded[] = ord($encoded[$x]);
            }
        } else {
            $this->_error(self::CANNOT_DECODE_PUNYCODE);
            return false;
        }
        $lengthd = count($decoded);
        $lengthe = strlen($encoded);
        $init  = true;
        $base  = 72;
        $index = 0;
        $char  = 0x80;
        for ($indexe = ($separator) ? ($separator + 1) : 0; $indexe < $lengthe; ++$lengthd) {
            for ($old_index = $index, $pos = 1, $key = 36; 1 ; $key += 36) {
                $hex   = ord($encoded[$indexe++]);
                $digit = ($hex - 48 < 10) ? $hex - 22
                       : (($hex - 65 < 26) ? $hex - 65
                       : (($hex - 97 < 26) ? $hex - 97
                       : 36));
                $index += $digit * $pos;
                $tag    = ($key <= $base) ? 1 : (($key >= $base + 26) ? 26 : ($key - $base));
                if ($digit < $tag) {
                    break;
                }
                $pos = (int) ($pos * (36 - $tag));
            }
            $delta   = intval($init ? (($index - $old_index) / 700) : (($index - $old_index) / 2));
            $delta  += intval($delta / ($lengthd + 1));
            for ($key = 0; $delta > 910 / 2; $key += 36) {
                $delta = intval($delta / 35);
            }
            $base   = intval($key + 36 * $delta / ($delta + 38));
            $init   = false;
            $char  += (int) ($index / ($lengthd + 1));
            $index %= ($lengthd + 1);
            if ($lengthd > 0) {
                for ($i = $lengthd; $i > $index; $i--) {
                    $decoded[$i] = $decoded[($i - 1)];
                }
            }
            $decoded[$index++] = $char;
        }
        foreach ($decoded as $key => $value) {
            if ($value < 128) {
                $decoded[$key] = chr($value);
            } elseif ($value < (1 << 11)) {
                $decoded[$key]  = chr(192 + ($value >> 6));
                $decoded[$key] .= chr(128 + ($value & 63));
            } elseif ($value < (1 << 16)) {
                $decoded[$key]  = chr(224 + ($value >> 12));
                $decoded[$key] .= chr(128 + (($value >> 6) & 63));
                $decoded[$key] .= chr(128 + ($value & 63));
            } elseif ($value < (1 << 21)) {
                $decoded[$key]  = chr(240 + ($value >> 18));
                $decoded[$key] .= chr(128 + (($value >> 12) & 63));
                $decoded[$key] .= chr(128 + (($value >> 6) & 63));
                $decoded[$key] .= chr(128 + ($value & 63));
            } else {
                $this->_error(self::CANNOT_DECODE_PUNYCODE);
                return false;
            }
        }
        return implode($decoded);
    }
}

}


if ( !@class_exists('Zend_Uri') ) {
abstract class Zend_Uri
{
    protected $_scheme = '';
    static protected $_config = array(
        'allow_unwise' => false
    );
    public function __toString()
    {
        try {
            return $this->getUri();
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }
    }
    public static function check($uri)
    {
        try {
            $uri = self::factory($uri);
        } catch (Exception $e) {
            return false;
        }
        return $uri->valid();
    }
    public static function factory($uri = 'http', $className = null)
    {
        $uri            = explode(':', $uri, 2);
        $scheme         = strtolower($uri[0]);
        $schemeSpecific = isset($uri[1]) === true ? $uri[1] : '';
        if (strlen($scheme) === 0) {
            throw new Zend_Uri_Exception('An empty string was supplied for the scheme');
        }
        if (ctype_alnum($scheme) === false) {
            throw new Zend_Uri_Exception('Illegal scheme supplied, only alphanumeric characters are permitted');
        }
        if ($className === null) {
            switch ($scheme) {
                case 'http':
                case 'https':
                    $className = 'Zend_Uri_Http';
                    break;
                case 'mailto':
                default:
                    throw new Zend_Uri_Exception("Scheme \"$scheme\" is not supported");
                    break;
            }
        }
        try {
            Zend_Loader::loadClass($className);
        } catch (Exception $e) {
            throw new Zend_Uri_Exception("\"$className\" not found");
        }
        $schemeHandler = new $className($scheme, $schemeSpecific);
        if (! $schemeHandler instanceof Zend_Uri) {
            throw new Zend_Uri_Exception("\"$className\" is not an instance of Zend_Uri");
        }
        return $schemeHandler;
    }
    public function getScheme()
    {
        if (empty($this->_scheme) === false) {
            return $this->_scheme;
        } else {
            return false;
        }
    }
    static public function setConfig($config)
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        } elseif (!is_array($config)) {
            throw new Zend_Uri_Exception("Config must be an array or an instance of Zend_Config.");
        }
        foreach ($config as $k => $v) {
            self::$_config[$k] = $v;
        }
    }
    abstract protected function __construct($scheme, $schemeSpecific = '');
    abstract public function getUri();
    abstract public function valid();
}

}


if ( !@class_exists('Zend_Uri_Http') ) {
class Zend_Uri_Http extends Zend_Uri
{
    const CHAR_ALNUM    = 'A-Za-z0-9';
    const CHAR_MARK     = '-_.!~*\'()\[\]';
    const CHAR_RESERVED = ';\/?:@&=+$,';
    const CHAR_SEGMENT  = ':@&=+$,;';
    const CHAR_UNWISE   = '{}|\\\\^`';
    protected $_username = '';
    protected $_password = '';
    protected $_host = '';
    protected $_port = '';
    protected $_path = '';
    protected $_query = '';
    protected $_fragment = '';
    protected $_regex = array();
    protected function __construct($scheme, $schemeSpecific = '')
    {
        $this->_scheme = $scheme;
        $this->_regex['escaped']    = '%[[:xdigit:]]{2}';
        $this->_regex['unreserved'] = '[' . self::CHAR_ALNUM . self::CHAR_MARK . ']';
        $this->_regex['segment']    = '(?:' . $this->_regex['escaped'] . '|[' .
            self::CHAR_ALNUM . self::CHAR_MARK . self::CHAR_SEGMENT . '])*';
        $this->_regex['path']       = '(?:\/(?:' . $this->_regex['segment'] . ')?)+';
        $this->_regex['uric']       = '(?:' . $this->_regex['escaped'] . '|[' .
            self::CHAR_ALNUM . self::CHAR_MARK . self::CHAR_RESERVED .
            (self::$_config['allow_unwise'] ? self::CHAR_UNWISE : '') . '])';
        if (strlen($schemeSpecific) === 0) {
            return;
        }
        $this->_parseUri($schemeSpecific);
        if ($this->valid() === false) {
            throw new Zend_Uri_Exception('Invalid URI supplied');
        }
    }
    public static function fromString($uri)
    {
        if (is_string($uri) === false) {
            throw new Zend_Uri_Exception('$uri is not a string');
        }
        $uri            = explode(':', $uri, 2);
        $scheme         = strtolower($uri[0]);
        $schemeSpecific = isset($uri[1]) === true ? $uri[1] : '';
        if (in_array($scheme, array('http', 'https')) === false) {
            throw new Zend_Uri_Exception("Invalid scheme: '$scheme'");
        }
        $schemeHandler = new Zend_Uri_Http($scheme, $schemeSpecific);
        return $schemeHandler;
    }
    protected function _parseUri($schemeSpecific)
    {
        $pattern = '~^((//)([^/?#]*))([^?#]*)(\?([^#]*))?(#(.*))?$~';
        $status  = @preg_match($pattern, $schemeSpecific, $matches);
        if ($status === false) {
            throw new Zend_Uri_Exception('Internal error: scheme-specific decomposition failed');
        }
        if ($status === false) {
            return;
        }
        $this->_path     = isset($matches[4]) === true ? $matches[4] : '';
        $this->_query    = isset($matches[6]) === true ? $matches[6] : '';
        $this->_fragment = isset($matches[8]) === true ? $matches[8] : '';
        $combo   = isset($matches[3]) === true ? $matches[3] : '';
        $pattern = '~^(([^:@]*)(:([^@]*))?@)?([^:]+)(:(.*))?$~';
        $status  = @preg_match($pattern, $combo, $matches);
        if ($status === false) {
            throw new Zend_Uri_Exception('Internal error: authority decomposition failed');
        }
        if ($status === false) {
            return;
        }
        $this->_username = isset($matches[2]) === true ? $matches[2] : '';
        $this->_password = isset($matches[4]) === true ? $matches[4] : '';
        $this->_host     = isset($matches[5]) === true ? $matches[5] : '';
        $this->_port     = isset($matches[7]) === true ? $matches[7] : '';
    }
    public function getUri()
    {
        if ($this->valid() === false) {
            throw new Zend_Uri_Exception('One or more parts of the URI are invalid');
        }
        $password = strlen($this->_password) > 0 ? ":$this->_password" : '';
        $auth     = strlen($this->_username) > 0 ? "$this->_username$password@" : '';
        $port     = strlen($this->_port) > 0 ? ":$this->_port" : '';
        $query    = strlen($this->_query) > 0 ? "?$this->_query" : '';
        $fragment = strlen($this->_fragment) > 0 ? "#$this->_fragment" : '';
        return $this->_scheme
             . '://'
             . $auth
             . $this->_host
             . $port
             . $this->_path
             . $query
             . $fragment;
    }
    public function valid()
    {
        return $this->validateUsername()
           and $this->validatePassword()
           and $this->validateHost()
           and $this->validatePort()
           and $this->validatePath()
           and $this->validateQuery()
           and $this->validateFragment();
    }
    public function getUsername()
    {
        return strlen($this->_username) > 0 ? $this->_username : false;
    }
    public function validateUsername($username = null)
    {
        if ($username === null) {
            $username = $this->_username;
        }
        if (strlen($username) === 0) {
            return true;
        }
        $status = @preg_match('/^(?:' . $this->_regex['escaped'] . '|[' .
            self::CHAR_ALNUM . self::CHAR_MARK . ';:&=+$,' . '])+$/', $username);
        if ($status === false) {
            throw new Zend_Uri_Exception('Internal error: username validation failed');
        }
        return $status === 1;
    }
    public function setUsername($username)
    {
        if ($this->validateUsername($username) === false) {
            throw new Zend_Uri_Exception("Username \"$username\" is not a valid HTTP username");
        }
        $oldUsername     = $this->_username;
        $this->_username = $username;
        return $oldUsername;
    }
    public function getPassword()
    {
        return strlen($this->_password) > 0 ? $this->_password : false;
    }
    public function validatePassword($password = null)
    {
        if ($password === null) {
            $password = $this->_password;
        }
        if (strlen($password) === 0) {
            return true;
        }
        if (strlen($password) > 0 and strlen($this->_username) === 0) {
            return false;
        }
        $status = @preg_match('/^(?:' . $this->_regex['escaped'] . '|[' .
            self::CHAR_ALNUM . self::CHAR_MARK . ';:&=+$,' . '])+$/', $password);
        if ($status === false) {
            throw new Zend_Uri_Exception('Internal error: password validation failed.');
        }
        return $status == 1;
    }
    public function setPassword($password)
    {
        if ($this->validatePassword($password) === false) {
            throw new Zend_Uri_Exception("Password \"$password\" is not a valid HTTP password.");
        }
        $oldPassword     = $this->_password;
        $this->_password = $password;
        return $oldPassword;
    }
    public function getHost()
    {
        return strlen($this->_host) > 0 ? $this->_host : false;
    }
    public function validateHost($host = null)
    {
        if ($host === null) {
            $host = $this->_host;
        }
        if (strlen($host) === 0) {
            return false;
        }
        $validate = new Zend_Validate_Hostname(Zend_Validate_Hostname::ALLOW_ALL);
        return $validate->isValid($host);
    }
    public function setHost($host)
    {
        if ($this->validateHost($host) === false) {
            throw new Zend_Uri_Exception("Host \"$host\" is not a valid HTTP host");
        }
        $oldHost     = $this->_host;
        $this->_host = $host;
        return $oldHost;
    }
    public function getPort()
    {
        return strlen($this->_port) > 0 ? $this->_port : false;
    }
    public function validatePort($port = null)
    {
        if ($port === null) {
            $port = $this->_port;
        }
        if (strlen($port) === 0) {
            return true;
        }
        return ctype_digit((string) $port) and 1 <= $port and $port <= 65535;
    }
    public function setPort($port)
    {
        if ($this->validatePort($port) === false) {
            throw new Zend_Uri_Exception("Port \"$port\" is not a valid HTTP port.");
        }
        $oldPort     = $this->_port;
        $this->_port = $port;
        return $oldPort;
    }
    public function getPath()
    {
        return strlen($this->_path) > 0 ? $this->_path : '/';
    }
    public function validatePath($path = null)
    {
        if ($path === null) {
            $path = $this->_path;
        }
        if (strlen($path) === 0) {
            return true;
        }
        $pattern = '/^' . $this->_regex['path'] . '$/';
        $status  = @preg_match($pattern, $path);
        if ($status === false) {
            throw new Zend_Uri_Exception('Internal error: path validation failed');
        }
        return (boolean) $status;
    }
    public function setPath($path)
    {
        if ($this->validatePath($path) === false) {
            throw new Zend_Uri_Exception("Path \"$path\" is not a valid HTTP path");
        }
        $oldPath     = $this->_path;
        $this->_path = $path;
        return $oldPath;
    }
    public function getQuery()
    {
        return strlen($this->_query) > 0 ? $this->_query : false;
    }
    public function getQueryAsArray()
    {
        $query = $this->getQuery();
        $querryArray = array();
        if ($query !== false) {
            parse_str($query, $querryArray);
        }
        return $querryArray;
    }
    public function validateQuery($query = null)
    {
        if ($query === null) {
            $query = $this->_query;
        }
        if (strlen($query) === 0) {
            return true;
        }
        $pattern = '/^' . $this->_regex['uric'] . '*$/';
        $status  = @preg_match($pattern, $query);
        if ($status === false) {
            throw new Zend_Uri_Exception('Internal error: query validation failed');
        }
        return $status == 1;
    }
    public function addReplaceQueryParameters(array $queryParams)
    {
        $queryParams = array_merge($this->getQueryAsArray(), $queryParams);
        return $this->setQuery($queryParams);
    }
    public function removeQueryParameters(array $queryParamKeys)
    {
        $queryParams = array_diff_key($this->getQueryAsArray(), array_fill_keys($queryParamKeys, 0));
        return $this->setQuery($queryParams);
    }
    public function setQuery($query)
    {
        $oldQuery = $this->_query;
        if (empty($query) === true) {
            $this->_query = '';
            return $oldQuery;
        }
        if (is_array($query) === true) {
            $query = http_build_query($query, '', '&');
        } else {
            $query = (string) $query;
            if ($this->validateQuery($query) === false) {
                parse_str($query, $queryArray);
                $query = http_build_query($queryArray, '', '&');
            }
        }
        if ($this->validateQuery($query) === false) {
            throw new Zend_Uri_Exception("'$query' is not a valid query string");
        }
        $this->_query = $query;
        return $oldQuery;
    }
    public function getFragment()
    {
        return strlen($this->_fragment) > 0 ? $this->_fragment : false;
    }
    public function validateFragment($fragment = null)
    {
        if ($fragment === null) {
            $fragment = $this->_fragment;
        }
        if (strlen($fragment) === 0) {
            return true;
        }
        $pattern = '/^' . $this->_regex['uric'] . '*$/';
        $status  = @preg_match($pattern, $fragment);
        if ($status === false) {
            throw new Zend_Uri_Exception('Internal error: fragment validation failed');
        }
        return (boolean) $status;
    }
    public function setFragment($fragment)
    {
        if ($this->validateFragment($fragment) === false) {
            throw new Zend_Uri_Exception("Fragment \"$fragment\" is not a valid HTTP fragment");
        }
        $oldFragment     = $this->_fragment;
        $this->_fragment = $fragment;
        return $oldFragment;
    }
}

}


if ( !@class_exists('Zend_Uri_Exception') ) {
class Zend_Uri_Exception extends Zend_Exception
{
}

}


if ( !@interface_exists('Zend_Http_Client_Adapter_Interface') ) {
interface Zend_Http_Client_Adapter_Interface
{
    public function setConfig($config = array());
    public function connect($host, $port = 80, $secure = false);
    public function write($method, $url, $http_ver = '1.1', $headers = array(), $body = '');
    public function read();
    public function close();
}

}


if ( !@interface_exists('Zend_Http_Client_Adapter_Stream') ) {
interface Zend_Http_Client_Adapter_Stream
{
    function setOutputStream($stream);
}

}


if ( !@class_exists('Zend_Http_Client_Adapter_Socket') ) {
class Zend_Http_Client_Adapter_Socket implements Zend_Http_Client_Adapter_Interface, Zend_Http_Client_Adapter_Stream
{
    protected $socket = null;
    protected $connected_to = array(null, null);
    protected $out_stream = null;
    protected $config = array(
        'persistent'    => false,
        'ssltransport'  => 'ssl',
        'sslcert'       => null,
        'sslpassphrase' => null,
        'sslusecontext' => false
    );
    protected $method = null;
    protected $_context = null;
    public function __construct()
    {
    }
    public function setConfig($config = array())
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        } elseif (! is_array($config)) {
            throw new Zend_Http_Client_Adapter_Exception(
                'Array or Zend_Config object expected, got ' . gettype($config)
            );
        }
        foreach ($config as $k => $v) {
            $this->config[strtolower($k)] = $v;
        }
    }
     public function getConfig()
     {
         return $this->config;
     }
    public function setStreamContext($context)
    {
        if (is_resource($context) && get_resource_type($context) == 'stream-context') {
            $this->_context = $context;
        } elseif (is_array($context)) {
            $this->_context = stream_context_create($context);
        } else {
            throw new Zend_Http_Client_Adapter_Exception(
                "Expecting either a stream context resource or array, got " . gettype($context)
            );
        }
        return $this;
    }
    public function getStreamContext()
    {
        if (! $this->_context) {
            $this->_context = stream_context_create();
        }
        return $this->_context;
    }
    public function connect($host, $port = 80, $secure = false)
    {
        $host = ($secure ? $this->config['ssltransport'] : 'tcp') . '://' . $host;
        if (($this->connected_to[0] != $host || $this->connected_to[1] != $port)) {
            if (is_resource($this->socket)) $this->close();
        }
        if (! is_resource($this->socket) || ! $this->config['keepalive']) {
            $context = $this->getStreamContext();
            if ($secure || $this->config['sslusecontext']) {
                if ($this->config['sslcert'] !== null) {
                    if (! stream_context_set_option($context, 'ssl', 'local_cert',
                                                    $this->config['sslcert'])) {
                        throw new Zend_Http_Client_Adapter_Exception('Unable to set sslcert option');
                    }
                }
                if ($this->config['sslpassphrase'] !== null) {
                    if (! stream_context_set_option($context, 'ssl', 'passphrase',
                                                    $this->config['sslpassphrase'])) {
                        throw new Zend_Http_Client_Adapter_Exception('Unable to set sslpassphrase option');
                    }
                }
            }
            $flags = STREAM_CLIENT_CONNECT;
            if ($this->config['persistent']) $flags |= STREAM_CLIENT_PERSISTENT;
            $this->socket = @stream_socket_client($host . ':' . $port,
                                                  $errno,
                                                  $errstr,
                                                  (int) $this->config['timeout'],
                                                  $flags,
                                                  $context);
            if (! $this->socket) {
                $this->close();
                throw new Zend_Http_Client_Adapter_Exception(
                    'Unable to Connect to ' . $host . ':' . $port . '. Error #' . $errno . ': ' . $errstr);
            }
            if (! stream_set_timeout($this->socket, (int) $this->config['timeout'])) {
                throw new Zend_Http_Client_Adapter_Exception('Unable to set the connection timeout');
            }
            $this->connected_to = array($host, $port);
        }
    }
    public function write($method, $uri, $http_ver = '1.1', $headers = array(), $body = '')
    {
        if (! $this->socket) {
            throw new Zend_Http_Client_Adapter_Exception('Trying to write but we are not connected');
        }
        $host = $uri->getHost();
        $host = (strtolower($uri->getScheme()) == 'https' ? $this->config['ssltransport'] : 'tcp') . '://' . $host;
        if ($this->connected_to[0] != $host || $this->connected_to[1] != $uri->getPort()) {
            throw new Zend_Http_Client_Adapter_Exception('Trying to write but we are connected to the wrong host');
        }
        $this->method = $method;
        $path = $uri->getPath();
        if ($uri->getQuery()) $path .= '?' . $uri->getQuery();
        $request = "{$method} {$path} HTTP/{$http_ver}\r\n";
        foreach ($headers as $k => $v) {
            if (is_string($k)) $v = ucfirst($k) . ": $v";
            $request .= "$v\r\n";
        }
        if(is_resource($body)) {
            $request .= "\r\n";
        } else {
            $request .= "\r\n" . $body;
        }
        if (! @fwrite($this->socket, $request)) {
            throw new Zend_Http_Client_Adapter_Exception('Error writing request to server');
        }
        if(is_resource($body)) {
            if(stream_copy_to_stream($body, $this->socket) == 0) {
                throw new Zend_Http_Client_Adapter_Exception('Error writing request to server');
            }
        }
        return $request;
    }
    public function read()
    {
        $response = '';
        $gotStatus = false;
        $stream = !empty($this->config['stream']);
        while (($line = @fgets($this->socket)) !== false) {
            $gotStatus = $gotStatus || (strpos($line, 'HTTP') !== false);
            if ($gotStatus) {
                $response .= $line;
                if (rtrim($line) === '') break;
            }
        }
        $this->_checkSocketReadTimeout();
        $statusCode = Zend_Http_Response::extractCode($response);
        if ($statusCode == 100 || $statusCode == 101) return $this->read();
        $headers = Zend_Http_Response::extractHeaders($response);
        if ($statusCode == 304 || $statusCode == 204 ||
            $this->method == Zend_Http_Client::HEAD) {
            if (isset($headers['connection']) && $headers['connection'] == 'close') {
                $this->close();
            }
            return $response;
        }
        if (isset($headers['transfer-encoding'])) {
            if (strtolower($headers['transfer-encoding']) == 'chunked') {
                do {
                    $line  = @fgets($this->socket);
                    $this->_checkSocketReadTimeout();
                    $chunk = $line;
                    $chunksize = trim($line);
                    if (! ctype_xdigit($chunksize)) {
                        $this->close();
                        throw new Zend_Http_Client_Adapter_Exception('Invalid chunk size "' .
                            $chunksize . '" unable to read chunked body');
                    }
                    $chunksize = hexdec($chunksize);
                    $read_to = ftell($this->socket) + $chunksize;
                    do {
                        $current_pos = ftell($this->socket);
                        if ($current_pos >= $read_to) break;
                        if($this->out_stream) {
                            if(stream_copy_to_stream($this->socket, $this->out_stream, $read_to - $current_pos) == 0) {
                              $this->_checkSocketReadTimeout();
                              break;   
                             }
                        } else {
                            $line = @fread($this->socket, $read_to - $current_pos);
                            if ($line === false || strlen($line) === 0) {
                                $this->_checkSocketReadTimeout();
                                break;
                            }
                                    $chunk .= $line;
                        }
                    } while (! feof($this->socket));
                    $chunk .= @fgets($this->socket);
                    $this->_checkSocketReadTimeout();
                    if(!$this->out_stream) {
                        $response .= $chunk;
                    }
                } while ($chunksize > 0);
            } else {
                $this->close();
                throw new Zend_Http_Client_Adapter_Exception('Cannot handle "' .
                    $headers['transfer-encoding'] . '" transfer encoding');
            }
            if ($this->out_stream) {
                $response = str_ireplace("Transfer-Encoding: chunked\r\n", '', $response);
            }
        } elseif (isset($headers['content-length'])) {
            if (is_array($headers['content-length'])) {
                $contentLength = $headers['content-length'][count($headers['content-length']) - 1]; 
            } else {
                $contentLength = $headers['content-length'];
            }
            $current_pos = ftell($this->socket);
            $chunk = '';
            for ($read_to = $current_pos + $contentLength;
                 $read_to > $current_pos;
                 $current_pos = ftell($this->socket)) {
                 if($this->out_stream) {
                     if(@stream_copy_to_stream($this->socket, $this->out_stream, $read_to - $current_pos) == 0) {
                          $this->_checkSocketReadTimeout();
                          break;   
                     }
                 } else {
                    $chunk = @fread($this->socket, $read_to - $current_pos);
                    if ($chunk === false || strlen($chunk) === 0) {
                        $this->_checkSocketReadTimeout();
                        break;
                    }
                    $response .= $chunk;
                }
                if (feof($this->socket)) break;
            }
        } else {
            do {
                if($this->out_stream) {
                    if(@stream_copy_to_stream($this->socket, $this->out_stream) == 0) {
                          $this->_checkSocketReadTimeout();
                          break;   
                     }
                }  else {
                    $buff = @fread($this->socket, 8192);
                    if ($buff === false || strlen($buff) === 0) {
                        $this->_checkSocketReadTimeout();
                        break;
                    } else {
                        $response .= $buff;
                    }
                }
            } while (feof($this->socket) === false);
            $this->close();
        }
        if (isset($headers['connection']) && $headers['connection'] == 'close') {
            $this->close();
        }
        return $response;
    }
    public function close()
    {
        if (is_resource($this->socket)) @fclose($this->socket);
        $this->socket = null;
        $this->connected_to = array(null, null);
    }
    protected function _checkSocketReadTimeout()
    {
        if ($this->socket) {
            $info = stream_get_meta_data($this->socket);
            $timedout = $info['timed_out'];
            if ($timedout) {
                $this->close();
                throw new Zend_Http_Client_Adapter_Exception(
                    "Read timed out after {$this->config['timeout']} seconds",
                    Zend_Http_Client_Adapter_Exception::READ_TIMEOUT
                );
            }
        }
    }
    public function setOutputStream($stream) 
    {
        $this->out_stream = $stream;
        return $this;
    }
    public function __destruct()
    {
        if (! $this->config['persistent']) {
            if ($this->socket) $this->close();
        }
    }
}

}


if ( !@class_exists('Zend_Http_Client') ) {
class Zend_Http_Client
{
    const GET     = 'GET';
    const POST    = 'POST';
    const PUT     = 'PUT';
    const HEAD    = 'HEAD';
    const DELETE  = 'DELETE';
    const TRACE   = 'TRACE';
    const OPTIONS = 'OPTIONS';
    const CONNECT = 'CONNECT';
    const MERGE   = 'MERGE';
    const AUTH_BASIC = 'basic';
    const HTTP_1 = '1.1';
    const HTTP_0 = '1.0';
    const CONTENT_TYPE   = 'Content-Type';
    const CONTENT_LENGTH = 'Content-Length';
    const ENC_URLENCODED = 'application/x-www-form-urlencoded';
    const ENC_FORMDATA   = 'multipart/form-data';
    protected $config = array(
        'maxredirects'    => 5,
        'strictredirects' => false,
        'useragent'       => 'Zend_Http_Client',
        'timeout'         => 10,
        'adapter'         => 'Zend_Http_Client_Adapter_Socket',
        'httpversion'     => self::HTTP_1,
        'keepalive'       => false,
        'storeresponse'   => true,
        'strict'          => true,
        'output_stream'   => false,
        'encodecookies'   => true,
        'rfc3986_strict'  => false
    );
    protected $adapter = null;
    protected $uri = null;
    protected $headers = array();
    protected $method = self::GET;
    protected $paramsGet = array();
    protected $paramsPost = array();
    protected $enctype = null;
    protected $raw_post_data = null;
    protected $auth;
    protected $files = array();
    protected $cookiejar = null;
    protected $last_request = null;
    protected $last_response = null;
    protected $redirectCounter = 0;
    static protected $_fileInfoDb = null;
    public function __construct($uri = null, $config = null)
    {
        if ($uri !== null) {
            $this->setUri($uri);
        }
        if ($config !== null) {
            $this->setConfig($config);
        }
    }
    public function setUri($uri)
    {
        if (is_string($uri)) {
            $uri = Zend_Uri::factory($uri);
        }
        if (!$uri instanceof Zend_Uri_Http) {
            /** @see Zend_Http_Client_Exception */
            throw new Zend_Http_Client_Exception('Passed parameter is not a valid HTTP URI.');
        }
        if ($uri->getUsername() && $uri->getPassword()) {
            $this->setAuth($uri->getUsername(), $uri->getPassword());
        }
        if (! $uri->getPort()) {
            $uri->setPort(($uri->getScheme() == 'https' ? 443 : 80));
        }
        $this->uri = $uri;
        return $this;
    }
    public function getUri($as_string = false)
    {
        if ($as_string && $this->uri instanceof Zend_Uri_Http) {
            return $this->uri->__toString();
        } else {
            return $this->uri;
        }
    }
    public function setConfig($config = array())
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        } elseif (! is_array($config)) {
            /** @see Zend_Http_Client_Exception */
            throw new Zend_Http_Client_Exception('Array or Zend_Config object expected, got ' . gettype($config));
        }
        foreach ($config as $k => $v) {
            $this->config[strtolower($k)] = $v;
        }
        if ($this->adapter instanceof Zend_Http_Client_Adapter_Interface) {
            $this->adapter->setConfig($config);
        }
        return $this;
    }
    public function setMethod($method = self::GET)
    {
        if (! preg_match('/^[^\x00-\x1f\x7f-\xff\(\)<>@,;:\\\\"\/\[\]\?={}\s]+$/', $method)) {
            /** @see Zend_Http_Client_Exception */
            throw new Zend_Http_Client_Exception("'{$method}' is not a valid HTTP request method.");
        }
        if ($method == self::POST && $this->enctype === null) {
            $this->setEncType(self::ENC_URLENCODED);
        }
        $this->method = $method;
        return $this;
    }
    public function setHeaders($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                if (is_string($k)) {
                    $this->setHeaders($k, $v);
                } else {
                    $this->setHeaders($v, null);
                }
            }
        } else {
            if ($value === null && (strpos($name, ':') > 0)) {
                list($name, $value) = explode(':', $name, 2);
            }
            if ($this->config['strict'] && (! preg_match('/^[a-zA-Z0-9-]+$/', $name))) {
                /** @see Zend_Http_Client_Exception */
                throw new Zend_Http_Client_Exception("{$name} is not a valid HTTP header name");
            }
            $normalized_name = strtolower($name);
            if ($value === null || $value === false) {
                unset($this->headers[$normalized_name]);
            } else {
                if (is_string($value)) {
                    $value = trim($value);
                }
                $this->headers[$normalized_name] = array($name, $value);
            }
        }
        return $this;
    }
    public function getHeader($key)
    {
        $key = strtolower($key);
        if (isset($this->headers[$key])) {
            return $this->headers[$key][1];
        } else {
            return null;
        }
    }
    public function setParameterGet($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v)
                $this->_setParameter('GET', $k, $v);
        } else {
            $this->_setParameter('GET', $name, $value);
        }
        return $this;
    }
    public function setParameterPost($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v)
                $this->_setParameter('POST', $k, $v);
        } else {
            $this->_setParameter('POST', $name, $value);
        }
        return $this;
    }
    protected function _setParameter($type, $name, $value)
    {
        $parray = array();
        $type = strtolower($type);
        switch ($type) {
            case 'get':
                $parray = &$this->paramsGet;
                break;
            case 'post':
                $parray = &$this->paramsPost;
                break;
        }
        if ($value === null) {
            if (isset($parray[$name])) unset($parray[$name]);
        } else {
            $parray[$name] = $value;
        }
    }
    public function getRedirectionsCount()
    {
        return $this->redirectCounter;
    }
    public function setAuth($user, $password = '', $type = self::AUTH_BASIC)
    {
        if ($user === false || $user === null) {
            $this->auth = null;
            if ($this->uri instanceof Zend_Uri_Http) {
                $this->getUri()->setUsername('');
                $this->getUri()->setPassword('');
            }
        } else {
            if (! defined('self::AUTH_' . strtoupper($type))) {
                /** @see Zend_Http_Client_Exception */
                throw new Zend_Http_Client_Exception("Invalid or not supported authentication type: '$type'");
            }
            $this->auth = array(
                'user' => (string) $user,
                'password' => (string) $password,
                'type' => $type
            );
        }
        return $this;
    }
    public function setCookieJar($cookiejar = true)
    {
        Zend_Loader::loadClass('Zend_Http_CookieJar');
        if ($cookiejar instanceof Zend_Http_CookieJar) {
            $this->cookiejar = $cookiejar;
        } elseif ($cookiejar === true) {
            $this->cookiejar = new Zend_Http_CookieJar();
        } elseif (! $cookiejar) {
            $this->cookiejar = null;
        } else {
            /** @see Zend_Http_Client_Exception */
            throw new Zend_Http_Client_Exception('Invalid parameter type passed as CookieJar');
        }
        return $this;
    }
    public function getCookieJar()
    {
        return $this->cookiejar;
    }
    public function setCookie($cookie, $value = null)
    {
        Zend_Loader::loadClass('Zend_Http_Cookie');
        if (is_array($cookie)) {
            foreach ($cookie as $c => $v) {
                if (is_string($c)) {
                    $this->setCookie($c, $v);
                } else {
                    $this->setCookie($v);
                }
            }
            return $this;
        }
        if ($value !== null && $this->config['encodecookies']) {
            $value = urlencode($value);
        }
        if (isset($this->cookiejar)) {
            if ($cookie instanceof Zend_Http_Cookie) {
                $this->cookiejar->addCookie($cookie);
            } elseif (is_string($cookie) && $value !== null) {
                $cookie = Zend_Http_Cookie::fromString("{$cookie}={$value}",
                                                       $this->uri,
                                                       $this->config['encodecookies']);
                $this->cookiejar->addCookie($cookie);
            }
        } else {
            if ($cookie instanceof Zend_Http_Cookie) {
                $name = $cookie->getName();
                $value = $cookie->getValue();
                $cookie = $name;
            }
            if (preg_match("/[=,; \t\r\n\013\014]/", $cookie)) {
                /** @see Zend_Http_Client_Exception */
                throw new Zend_Http_Client_Exception("Cookie name cannot contain these characters: =,; \t\r\n\013\014 ({$cookie})");
            }
            $value = addslashes($value);
            if (! isset($this->headers['cookie'])) {
                $this->headers['cookie'] = array('Cookie', '');
            }
            $this->headers['cookie'][1] .= $cookie . '=' . $value . '; ';
        }
        return $this;
    }
    public function setFileUpload($filename, $formname, $data = null, $ctype = null)
    {
        if ($data === null) {
            if (($data = @file_get_contents($filename)) === false) {
                /** @see Zend_Http_Client_Exception */
                throw new Zend_Http_Client_Exception("Unable to read file '{$filename}' for upload");
            }
            if (! $ctype) {
                $ctype = $this->_detectFileMimeType($filename);
            }
        }
        $this->setEncType(self::ENC_FORMDATA);
        $this->files[] = array(
            'formname' => $formname,
            'filename' => basename($filename),
            'ctype'    => $ctype,
            'data'     => $data
        );
        return $this;
    }
    public function setEncType($enctype = self::ENC_URLENCODED)
    {
        $this->enctype = $enctype;
        return $this;
    }
    public function setRawData($data, $enctype = null)
    {
        $this->raw_post_data = $data;
        $this->setEncType($enctype);
        if (is_resource($data)) {
            $stat = @fstat($data);
            if($stat) {
                $this->setHeaders(self::CONTENT_LENGTH, $stat['size']);
            }
        }
        return $this;
    }
    public function resetParameters($clearAll = false)
    {
        $this->paramsGet     = array();
        $this->paramsPost    = array();
        $this->files         = array();
        $this->raw_post_data = null;
        if($clearAll) {
            $this->headers = array();
            $this->last_request = null;
            $this->last_response = null;
        } else {
            if (isset($this->headers[strtolower(self::CONTENT_TYPE)])) {
                unset($this->headers[strtolower(self::CONTENT_TYPE)]);
            }
            if (isset($this->headers[strtolower(self::CONTENT_LENGTH)])) {
                unset($this->headers[strtolower(self::CONTENT_LENGTH)]);
            }
        }
        return $this;
    }
    public function getLastRequest()
    {
        return $this->last_request;
    }
    public function getLastResponse()
    {
        return $this->last_response;
    }
    public function setAdapter($adapter)
    {
        if (is_string($adapter)) {
            try {
                Zend_Loader::loadClass($adapter);
            } catch (Zend_Exception $e) {
                /** @see Zend_Http_Client_Exception */
                throw new Zend_Http_Client_Exception("Unable to load adapter '$adapter': {$e->getMessage()}", 0, $e);
            }
            $adapter = new $adapter;
        }
        if (! $adapter instanceof Zend_Http_Client_Adapter_Interface) {
            /** @see Zend_Http_Client_Exception */
            throw new Zend_Http_Client_Exception('Passed adapter is not a HTTP connection adapter');
        }
        $this->adapter = $adapter;
        $config = $this->config;
        unset($config['adapter']);
        $this->adapter->setConfig($config);
    }
    public function getAdapter()
    {
        return $this->adapter;
    }
    public function setStream($streamfile = true)
    {
        $this->setConfig(array("output_stream" => $streamfile));
        return $this;
    }
    public function getStream()
    {
        return $this->config["output_stream"];
    }
    protected function _openTempStream()
    {
        $this->_stream_name = $this->config['output_stream'];
        if(!is_string($this->_stream_name)) {
            $this->_stream_name = tempnam(isset($this->config['stream_tmp_dir'])?$this->config['stream_tmp_dir']:sys_get_temp_dir(),
                 'Zend_Http_Client');
        }
        if (false === ($fp = @fopen($this->_stream_name, "w+b"))) {
                if ($this->adapter instanceof Zend_Http_Client_Adapter_Interface) {
                    $this->adapter->close();
                }
                throw new Zend_Http_Client_Exception("Could not open temp file {$this->_stream_name}");
        }
        return $fp;
    }
    public function request($method = null)
    {
        if (! $this->uri instanceof Zend_Uri_Http) {
            /** @see Zend_Http_Client_Exception */
            throw new Zend_Http_Client_Exception('No valid URI has been passed to the client');
        }
        if ($method) {
            $this->setMethod($method);
        }
        $this->redirectCounter = 0;
        $response = null;
        if ($this->adapter == null) {
            $this->setAdapter($this->config['adapter']);
        }
        do {
            $uri = clone $this->uri;
            if (! empty($this->paramsGet)) {
                $query = $uri->getQuery();
                   if (! empty($query)) {
                       $query .= '&';
                   }
                $query .= http_build_query($this->paramsGet, null, '&');
                if ($this->config['rfc3986_strict']) {
                    $query = str_replace('+', '%20', $query);
                }
                $uri->setQuery($query);
            }
            $body = $this->_prepareBody();
            $headers = $this->_prepareHeaders();
            if(is_resource($body) && !($this->adapter instanceof Zend_Http_Client_Adapter_Stream)) {
                /** @see Zend_Http_Client_Exception */
                throw new Zend_Http_Client_Exception('Adapter does not support streaming');
            }
            $this->adapter->connect($uri->getHost(), $uri->getPort(),
                ($uri->getScheme() == 'https' ? true : false));
            if($this->config['output_stream']) {
                if($this->adapter instanceof Zend_Http_Client_Adapter_Stream) {
                    $stream = $this->_openTempStream();
                    $this->adapter->setOutputStream($stream);
                } else {
                    /** @see Zend_Http_Client_Exception */
                    throw new Zend_Http_Client_Exception('Adapter does not support streaming');
                }
            }
            $this->last_request = $this->adapter->write($this->method,
                $uri, $this->config['httpversion'], $headers, $body);
            $response = $this->adapter->read();
            if (! $response) {
                /** @see Zend_Http_Client_Exception */
                throw new Zend_Http_Client_Exception('Unable to read response, or response is empty');
            }
            if($this->config['output_stream']) {
                rewind($stream);
                $this->adapter->setOutputStream(null);
                $response = Zend_Http_Response_Stream::fromStream($response, $stream);
                $response->setStreamName($this->_stream_name);
                if(!is_string($this->config['output_stream'])) {
                    $response->setCleanup(true);
                }
            } else {
                $response = Zend_Http_Response::fromString($response);
            }
            if ($this->config['storeresponse']) {
                $this->last_response = $response;
            }
            if (isset($this->cookiejar)) {
                $this->cookiejar->addCookiesFromResponse($response, $uri, $this->config['encodecookies']);
            }
            if ($response->isRedirect() && ($location = $response->getHeader('location'))) {
                if ($response->getStatus() == 303 ||
                   ((! $this->config['strictredirects']) && ($response->getStatus() == 302 ||
                       $response->getStatus() == 301))) {
                    $this->resetParameters();
                    $this->setMethod(self::GET);
                }
                if (Zend_Uri_Http::check($location)) {
                    $this->setHeaders('host', null);
                    $this->setUri($location);
                } else {
                    if (strpos($location, '?') !== false) {
                        list($location, $query) = explode('?', $location, 2);
                    } else {
                        $query = '';
                    }
                    $this->uri->setQuery($query);
                    if(strpos($location, '/') === 0) {
                        $this->uri->setPath($location);
                    } else {
                        $path = $this->uri->getPath();
                        $path = rtrim(substr($path, 0, strrpos($path, '/')), "/");
                        $this->uri->setPath($path . '/' . $location);
                    }
                }
                ++$this->redirectCounter;
            } else {
                break;
            }
        } while ($this->redirectCounter < $this->config['maxredirects']);
        return $response;
    }
    protected function _prepareHeaders()
    {
        $headers = array();
        if (! isset($this->headers['host'])) {
            $host = $this->uri->getHost();
            if (! (($this->uri->getScheme() == 'http' && $this->uri->getPort() == 80) ||
                  ($this->uri->getScheme() == 'https' && $this->uri->getPort() == 443))) {
                $host .= ':' . $this->uri->getPort();
            }
            $headers[] = "Host: {$host}";
        }
        if (! isset($this->headers['connection'])) {
            if (! $this->config['keepalive']) {
                $headers[] = "Connection: close";
            }
        }
        if (! isset($this->headers['accept-encoding'])) {
            if (function_exists('gzinflate')) {
                $headers[] = 'Accept-encoding: gzip, deflate';
            } else {
                $headers[] = 'Accept-encoding: identity';
            }
        }
        if (($this->method == self::POST || $this->method == self::PUT) &&
           (! isset($this->headers[strtolower(self::CONTENT_TYPE)]) && isset($this->enctype))) {
            $headers[] = self::CONTENT_TYPE . ': ' . $this->enctype;
        }
        if (! isset($this->headers['user-agent']) && isset($this->config['useragent'])) {
            $headers[] = "User-Agent: {$this->config['useragent']}";
        }
        if (is_array($this->auth)) {
            $auth = self::encodeAuthHeader($this->auth['user'], $this->auth['password'], $this->auth['type']);
            $headers[] = "Authorization: {$auth}";
        }
        if (isset($this->cookiejar)) {
            $cookstr = $this->cookiejar->getMatchingCookies($this->uri,
                true, Zend_Http_CookieJar::COOKIE_STRING_CONCAT);
            if ($cookstr) {
                $headers[] = "Cookie: {$cookstr}";
            }
        }
        foreach ($this->headers as $header) {
            list($name, $value) = $header;
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $headers[] = "$name: $value";
        }
        return $headers;
    }
    protected function _prepareBody()
    {
        if ($this->method == self::TRACE) {
            return '';
        }
        if (isset($this->raw_post_data) && is_resource($this->raw_post_data)) {
            return $this->raw_post_data;
        }
        if (function_exists('mb_internal_encoding') &&
           ((int) ini_get('mbstring.func_overload')) & 2) {
            $mbIntEnc = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        }
        if (isset($this->raw_post_data)) {
            $this->setHeaders(self::CONTENT_LENGTH, strlen($this->raw_post_data));
            if (isset($mbIntEnc)) {
                mb_internal_encoding($mbIntEnc);
            }
            return $this->raw_post_data;
        }
        $body = '';
        if (count ($this->files) > 0) {
            $this->setEncType(self::ENC_FORMDATA);
        }
        if (count($this->paramsPost) > 0 || count($this->files) > 0) {
            switch($this->enctype) {
                case self::ENC_FORMDATA:
                    $boundary = '---ZENDHTTPCLIENT-' . md5(microtime());
                    $this->setHeaders(self::CONTENT_TYPE, self::ENC_FORMDATA . "; boundary={$boundary}");
                    $params = self::_flattenParametersArray($this->paramsPost);
                    foreach ($params as $pp) {
                        $body .= self::encodeFormData($boundary, $pp[0], $pp[1]);
                    }
                    foreach ($this->files as $file) {
                        $fhead = array(self::CONTENT_TYPE => $file['ctype']);
                        $body .= self::encodeFormData($boundary, $file['formname'], $file['data'], $file['filename'], $fhead);
                    }
                    $body .= "--{$boundary}--\r\n";
                    break;
                case self::ENC_URLENCODED:
                    $this->setHeaders(self::CONTENT_TYPE, self::ENC_URLENCODED);
                    $body = http_build_query($this->paramsPost, '', '&');
                    break;
                default:
                    if (isset($mbIntEnc)) {
                        mb_internal_encoding($mbIntEnc);
                    }
                    /** @see Zend_Http_Client_Exception */
                    throw new Zend_Http_Client_Exception("Cannot handle content type '{$this->enctype}' automatically." .
                        " Please use Zend_Http_Client::setRawData to send this kind of content.");
                    break;
            }
        }
        if ($body || $this->method == self::POST || $this->method == self::PUT) {
            $this->setHeaders(self::CONTENT_LENGTH, strlen($body));
        }
        if (isset($mbIntEnc)) {
            mb_internal_encoding($mbIntEnc);
        }
        return $body;
    }
    protected function _getParametersRecursive($parray, $urlencode = false)
    {
        trigger_error("The " .  __METHOD__ . " method is deprecated and will be dropped in 2.0.",
            E_USER_NOTICE);
        if (! is_array($parray)) {
            return $parray;
        }
        $parameters = array();
        foreach ($parray as $name => $value) {
            if ($urlencode) {
                $name = urlencode($name);
            }
            if (is_array($value)) {
                $name .= ($urlencode ? '%5B%5D' : '[]');
                foreach ($value as $subval) {
                    if ($urlencode) {
                        $subval = urlencode($subval);
                    }
                    $parameters[] = array($name, $subval);
                }
            } else {
                if ($urlencode) {
                    $value = urlencode($value);
                }
                $parameters[] = array($name, $value);
            }
        }
        return $parameters;
    }
    protected function _detectFileMimeType($file)
    {
        $type = null;
        if (function_exists('finfo_open')) {
            if (self::$_fileInfoDb === null) {
                self::$_fileInfoDb = @finfo_open(FILEINFO_MIME);
            }
            if (self::$_fileInfoDb) {
                $type = finfo_file(self::$_fileInfoDb, $file);
            }
        } elseif (function_exists('mime_content_type')) {
            $type = mime_content_type($file);
        }
        if (! $type) {
            $type = 'application/octet-stream';
        }
        return $type;
    }
    public static function encodeFormData($boundary, $name, $value, $filename = null, $headers = array()) {
        $ret = "--{$boundary}\r\n" .
            'Content-Disposition: form-data; name="' . $name .'"';
        if ($filename) {
            $ret .= '; filename="' . $filename . '"';
        }
        $ret .= "\r\n";
        foreach ($headers as $hname => $hvalue) {
            $ret .= "{$hname}: {$hvalue}\r\n";
        }
        $ret .= "\r\n";
        $ret .= "{$value}\r\n";
        return $ret;
    }
    public static function encodeAuthHeader($user, $password, $type = self::AUTH_BASIC)
    {
        $authHeader = null;
        switch ($type) {
            case self::AUTH_BASIC:
                if (strpos($user, ':') !== false) {
                    /** @see Zend_Http_Client_Exception */
                    throw new Zend_Http_Client_Exception("The user name cannot contain ':' in 'Basic' HTTP authentication");
                }
                $authHeader = 'Basic ' . base64_encode($user . ':' . $password);
                break;
            default:
                /** @see Zend_Http_Client_Exception */
                throw new Zend_Http_Client_Exception("Not a supported HTTP authentication type: '$type'");
        }
        return $authHeader;
    }
    static protected function _flattenParametersArray($parray, $prefix = null)
    {
        if (! is_array($parray)) {
            return $parray;
        }
        $parameters = array();
        foreach($parray as $name => $value) {
            if ($prefix) {
                if (is_int($name)) {
                    $key = $prefix . '[]';
                } else {
                    $key = $prefix . "[$name]";
                }
            } else {
                $key = $name;
            }
            if (is_array($value)) {
                $parameters = array_merge($parameters, self::_flattenParametersArray($value, $key));
            } else {
                $parameters[] = array($key, $value);
            }
        }
        return $parameters;
    }
}

}


if ( !@class_exists('Zend_Http_Response') ) {
class Zend_Http_Response
{
    protected static $messages = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );
    protected $version;
    protected $code;
    protected $message;
    protected $headers = array();
    protected $body;
    public function __construct($code, array $headers, $body = null, $version = '1.1', $message = null)
    {
        if (self::responseCodeAsText($code) === null) {
            throw new Zend_Http_Exception("{$code} is not a valid HTTP response code");
        }
        $this->code = $code;
        foreach ($headers as $name => $value) {
            if (is_int($name)) {
                $header = explode(":", $value, 2);
                if (count($header) != 2) {
                    throw new Zend_Http_Exception("'{$value}' is not a valid HTTP header");
                }
                $name  = trim($header[0]);
                $value = trim($header[1]);
            }
            $this->headers[ucwords(strtolower($name))] = $value;
        }
        $this->body = $body;
        if (! preg_match('|^\d\.\d$|', $version)) {
            throw new Zend_Http_Exception("Invalid HTTP response version: $version");
        }
        $this->version = $version;
        if (is_string($message)) {
            $this->message = $message;
        } else {
            $this->message = self::responseCodeAsText($code);
        }
    }
    public function isError()
    {
        $restype = floor($this->code / 100);
        if ($restype == 4 || $restype == 5) {
            return true;
        }
        return false;
    }
    public function isSuccessful()
    {
        $restype = floor($this->code / 100);
        if ($restype == 2 || $restype == 1) { // Shouldn't 3xx count as success as well ???
            return true;
        }
        return false;
    }
    public function isRedirect()
    {
        $restype = floor($this->code / 100);
        if ($restype == 3) {
            return true;
        }
        return false;
    }
    public function getBody()
    {
        $body = '';
        switch (strtolower($this->getHeader('transfer-encoding'))) {
            case 'chunked':
                $body = self::decodeChunkedBody($this->body);
                break;
            default:
                $body = $this->body;
                break;
        }
        switch (strtolower($this->getHeader('content-encoding'))) {
            case 'gzip':
                $body = self::decodeGzip($body);
                break;
            case 'deflate':
                $body = self::decodeDeflate($body);
                break;
            default:
                break;
        }
        return $body;
    }
    public function getRawBody()
    {
        return $this->body;
    }
    public function getVersion()
    {
        return $this->version;
    }
    public function getStatus()
    {
        return $this->code;
    }
    public function getMessage()
    {
        return $this->message;
    }
    public function getHeaders()
    {
        return $this->headers;
    }
    public function getHeader($header)
    {
        $header = ucwords(strtolower($header));
        if (! is_string($header) || ! isset($this->headers[$header])) return null;
        return $this->headers[$header];
    }
    public function getHeadersAsString($status_line = true, $br = "\n")
    {
        $str = '';
        if ($status_line) {
            $str = "HTTP/{$this->version} {$this->code} {$this->message}{$br}";
        }
        foreach ($this->headers as $name => $value)
        {
            if (is_string($value))
                $str .= "{$name}: {$value}{$br}";
            elseif (is_array($value)) {
                foreach ($value as $subval) {
                    $str .= "{$name}: {$subval}{$br}";
                }
            }
        }
        return $str;
    }
    public function asString($br = "\n")
    {
        return $this->getHeadersAsString(true, $br) . $br . $this->getRawBody();
    }
    public function __toString()
    {
        return $this->asString();
    }
    public static function responseCodeAsText($code = null, $http11 = true)
    {
        $messages = self::$messages;
        if (! $http11) $messages[302] = 'Moved Temporarily';
        if ($code === null) {
            return $messages;
        } elseif (isset($messages[$code])) {
            return $messages[$code];
        } else {
            return 'Unknown';
        }
    }
    public static function extractCode($response_str)
    {
        preg_match("|^HTTP/[\d\.x]+ (\d+)|", $response_str, $m);
        if (isset($m[1])) {
            return (int) $m[1];
        } else {
            return false;
        }
    }
    public static function extractMessage($response_str)
    {
        preg_match("|^HTTP/[\d\.x]+ \d+ ([^\r\n]+)|", $response_str, $m);
        if (isset($m[1])) {
            return $m[1];
        } else {
            return false;
        }
    }
    public static function extractVersion($response_str)
    {
        preg_match("|^HTTP/([\d\.x]+) \d+|", $response_str, $m);
        if (isset($m[1])) {
            return $m[1];
        } else {
            return false;
        }
    }
    public static function extractHeaders($response_str)
    {
        $headers = array();
        $parts = preg_split('|(?:\r?\n){2}|m', $response_str, 2);
        if (! $parts[0]) return $headers;
        $lines = explode("\n", $parts[0]);
        unset($parts);
        $last_header = null;
        foreach($lines as $line) {
            $line = trim($line, "\r\n");
            if ($line == "") break;
            if (preg_match("|^([\w-]+):\s*(.+)|", $line, $m)) {
                unset($last_header);
                $h_name = strtolower($m[1]);
                $h_value = $m[2];
                if (isset($headers[$h_name])) {
                    if (! is_array($headers[$h_name])) {
                        $headers[$h_name] = array($headers[$h_name]);
                    }
                    $headers[$h_name][] = $h_value;
                } else {
                    $headers[$h_name] = $h_value;
                }
                $last_header = $h_name;
            } elseif (preg_match("|^\s+(.+)$|", $line, $m) && $last_header !== null) {
                if (is_array($headers[$last_header])) {
                    end($headers[$last_header]);
                    $last_header_key = key($headers[$last_header]);
                    $headers[$last_header][$last_header_key] .= $m[1];
                } else {
                    $headers[$last_header] .= $m[1];
                }
            }
        }
        return $headers;
    }
    public static function extractBody($response_str)
    {
        $parts = preg_split('|(?:\r?\n){2}|m', $response_str, 2);
        if (isset($parts[1])) {
            return $parts[1];
        }
        return '';
    }
    public static function decodeChunkedBody($body)
    {
        $decBody = '';
        if (function_exists('mb_internal_encoding') &&
           ((int) ini_get('mbstring.func_overload')) & 2) {
            $mbIntEnc = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        }
        while (trim($body)) {
            if (! preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $body, $m)) {
                throw new Zend_Http_Exception("Error parsing body - doesn't seem to be a chunked message");
            }
            $length = hexdec(trim($m[1]));
            $cut = strlen($m[0]);
            $decBody .= substr($body, $cut, $length);
            $body = substr($body, $cut + $length + 2);
        }
        if (isset($mbIntEnc)) {
            mb_internal_encoding($mbIntEnc);
        }
        return $decBody;
    }
    public static function decodeGzip($body)
    {
        if (! function_exists('gzinflate')) {
            throw new Zend_Http_Exception(
                'zlib extension is required in order to decode "gzip" encoding'
            );
        }
        return gzinflate(substr($body, 10));
    }
    public static function decodeDeflate($body)
    {
        if (! function_exists('gzuncompress')) {
            throw new Zend_Http_Exception(
                'zlib extension is required in order to decode "deflate" encoding'
            );
        }
        $zlibHeader = unpack('n', substr($body, 0, 2));
        if ($zlibHeader[1] % 31 == 0) {
            return gzuncompress($body);
        } else {
            return gzinflate($body);
        }
    }
    public static function fromString($response_str)
    {
        $code    = self::extractCode($response_str);
        $headers = self::extractHeaders($response_str);
        $body    = self::extractBody($response_str);
        $version = self::extractVersion($response_str);
        $message = self::extractMessage($response_str);
        return new Zend_Http_Response($code, $headers, $body, $version, $message);
    }
}

}


if ( !@class_exists('Zend_Oauth') ) {
class Zend_Oauth
{
    const REQUEST_SCHEME_HEADER      = 'header';
    const REQUEST_SCHEME_POSTBODY    = 'postbody';
    const REQUEST_SCHEME_QUERYSTRING = 'querystring';
    const GET                        = 'GET';
    const POST                       = 'POST';
    const PUT                        = 'PUT';
    const DELETE                     = 'DELETE';
    const HEAD                       = 'HEAD';
    protected static $httpClient = null;
    public static function setHttpClient(Zend_Http_Client $httpClient)
    {
        self::$httpClient = $httpClient;
    }
    public static function getHttpClient()
    {
        if (!isset(self::$httpClient)) {
            self::$httpClient = new Zend_Http_Client;
        } else {
            self::$httpClient->setHeaders('Authorization', null);
            self::$httpClient->resetParameters();
        }
        return self::$httpClient;
    }
    public static function clearHttpClient()
    {
        self::$httpClient = null;
    }
}

}


if ( !@class_exists('Zend_Oauth_Client') ) {
class Zend_Oauth_Client extends Zend_Http_Client
{
    public static $supportsRevisionA = false;
    protected $_config = null;
    protected $_streamingRequest = null;
    public function __construct($oauthOptions, $uri = null, $config = null)
    {
        if (!isset($config['rfc3986_strict'])) {
            $config['rfc3986_strict'] = true;
        }
        parent::__construct($uri, $config);
        $this->_config = new Zend_Oauth_Config;
        if ($oauthOptions !== null) {
            if ($oauthOptions instanceof Zend_Config) {
                $oauthOptions = $oauthOptions->toArray();
            }
            $this->_config->setOptions($oauthOptions);
        }
    }
    public function getAdapter()
    {
        return $this->adapter;
    }
    public function setAdapter($adapter)
    {
        if ($adapter == null) {
            $this->adapter = $adapter;
        } else {
              parent::setAdapter($adapter);
        }
    }
    public function setStreamingRequest($value)
    {
        $this->_streamingRequest = $value;
    }
    public function getStreamingRequest()
    {
        if ($this->_streamingRequest) {
            return true;
        } else {
            return false;
        }
    }
    protected function _prepareBody()
    {
        if($this->_streamingRequest) {
            $this->setHeaders(self::CONTENT_LENGTH,
                $this->raw_post_data->getTotalSize());
            return $this->raw_post_data;
        }
        else {
            return parent::_prepareBody();
        }
    }
    public function resetParameters($clearAll = false)
    {
        $this->_streamingRequest = false;
        return parent::resetParameters($clearAll);
    }
    public function setRawDataStream($data, $enctype = null)
    {
        $this->_streamingRequest = true;
        return $this->setRawData($data, $enctype);
    }
    public function setMethod($method = self::GET)
    {
        if ($method == self::GET) {
            $this->setRequestMethod(self::GET);
        } elseif($method == self::POST) {
            $this->setRequestMethod(self::POST);
        } elseif($method == self::PUT) {
            $this->setRequestMethod(self::PUT);
        }  elseif($method == self::DELETE) {
            $this->setRequestMethod(self::DELETE);
        }   elseif($method == self::HEAD) {
            $this->setRequestMethod(self::HEAD);
        }
        return parent::setMethod($method);
    }
    public function request($method = null)
    {
        if ($method !== null) {
            $this->setMethod($method);
        }
        $this->prepareOauth();
        return parent::request();
    }
    public function prepareOauth()
    {
        $requestScheme = $this->getRequestScheme();
        $requestMethod = $this->getRequestMethod();
        $query = null;
        if ($requestScheme == Zend_Oauth::REQUEST_SCHEME_HEADER) {
            $oauthHeaderValue = $this->getToken()->toHeader(
                $this->getUri(true),
                $this->_config,
                $this->_getSignableParametersAsQueryString()
            );
            $this->setHeaders('Authorization', $oauthHeaderValue);
        } elseif ($requestScheme == Zend_Oauth::REQUEST_SCHEME_POSTBODY) {
            if ($requestMethod == self::GET) {
                throw new Zend_Oauth_Exception(
                    'The client is configured to'
                    . ' pass OAuth parameters through a POST body but request method'
                    . ' is set to GET'
                );
            }
            $raw = $this->getToken()->toQueryString(
                $this->getUri(true),
                $this->_config,
                $this->_getSignableParametersAsQueryString()
            );
            $this->setRawData($raw, 'application/x-www-form-urlencoded');
            $this->paramsPost = array();
        } elseif ($requestScheme == Zend_Oauth::REQUEST_SCHEME_QUERYSTRING) {
            $params = array();
            $query = $this->getUri()->getQuery();
            if ($query) {
                $queryParts = explode('&', $this->getUri()->getQuery());
                foreach ($queryParts as $queryPart) {
                    $kvTuple = explode('=', $queryPart);
                    $params[urldecode($kvTuple[0])] =
                        (array_key_exists(1, $kvTuple) ? urldecode($kvTuple[1]) : NULL);
                }
            }
            if (!empty($this->paramsPost)) {
                $params = array_merge($params, $this->paramsPost);
                $query  = $this->getToken()->toQueryString(
                    $this->getUri(true), $this->_config, $params
                );
            }
            $query = $this->getToken()->toQueryString(
                $this->getUri(true), $this->_config, $params
            );
            $this->getUri()->setQuery($query);
            $this->paramsGet = array();
        } else {
            throw new Zend_Oauth_Exception('Invalid request scheme: ' . $requestScheme);
        }
    }
    protected function _getSignableParametersAsQueryString()
    {
        $params = array();
            if (!empty($this->paramsGet)) {
                $params = array_merge($params, $this->paramsGet);
                $query  = $this->getToken()->toQueryString(
                    $this->getUri(true), $this->_config, $params
                );
            }
            if (!empty($this->paramsPost)) {
                $params = array_merge($params, $this->paramsPost);
                $query  = $this->getToken()->toQueryString(
                    $this->getUri(true), $this->_config, $params
                );
            }
            return $params;
    }
    public function __call($method, array $args)
    {
        if (!method_exists($this->_config, $method)) {
            throw new Zend_Oauth_Exception('Method does not exist: ' . $method);
        }
        return call_user_func_array(array($this->_config,$method), $args);
    }
}

}


if ( !@interface_exists('Zend_Oauth_Config_ConfigInterface') ) {
interface Zend_Oauth_Config_ConfigInterface
{
    public function setOptions(array $options);
    public function setConsumerKey($key);
    public function getConsumerKey();
    public function setConsumerSecret($secret);
    public function getConsumerSecret();
    public function setSignatureMethod($method);
    public function getSignatureMethod();
    public function setRequestScheme($scheme);
    public function getRequestScheme();
    public function setVersion($version);
    public function getVersion();
    public function setCallbackUrl($url);
    public function getCallbackUrl();
    public function setRequestTokenUrl($url);
    public function getRequestTokenUrl();
    public function setRequestMethod($method);
    public function getRequestMethod();
    public function setAccessTokenUrl($url);
    public function getAccessTokenUrl();
    public function setUserAuthorizationUrl($url);
    public function getUserAuthorizationUrl();
    public function setToken(Zend_Oauth_Token $token);
    public function getToken();
}

}


if ( !@class_exists('Zend_Oauth_Config') ) {
class Zend_Oauth_Config implements Zend_Oauth_Config_ConfigInterface
{
    protected $_signatureMethod = 'HMAC-SHA1';
    protected $_requestScheme = Zend_Oauth::REQUEST_SCHEME_HEADER;
    protected $_requestMethod = Zend_Oauth::POST;
    protected $_version = '1.0';
    protected $_callbackUrl = null;
    protected $_siteUrl = null;
    protected $_requestTokenUrl = null;
    protected $_accessTokenUrl = null;
    protected $_authorizeUrl = null;
    protected $_consumerKey = null;
    protected $_consumerSecret = null;
    protected $_rsaPrivateKey = null;
    protected $_rsaPublicKey = null;
    protected $_token = null;
    public function __construct($options = null)
    {
        if ($options !== null) {
            if ($options instanceof Zend_Config) {
                $options = $options->toArray();
            }
            $this->setOptions($options);
        }
    }
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'consumerKey':
                    $this->setConsumerKey($value);
                    break;
                case 'consumerSecret':
                    $this->setConsumerSecret($value);
                    break;
                case 'signatureMethod':
                    $this->setSignatureMethod($value);
                    break;
                case 'version':
                    $this->setVersion($value);
                    break;
                case 'callbackUrl':
                    $this->setCallbackUrl($value);
                    break;
                case 'siteUrl':
                    $this->setSiteUrl($value);
                    break;
                case 'requestTokenUrl':
                    $this->setRequestTokenUrl($value);
                    break;
                case 'accessTokenUrl':
                    $this->setAccessTokenUrl($value);
                    break;
                case 'userAuthorizationUrl':
                    $this->setUserAuthorizationUrl($value);
                    break;
                case 'authorizeUrl':
                    $this->setAuthorizeUrl($value);
                    break;
                case 'requestMethod':
                    $this->setRequestMethod($value);
                    break;
                case 'rsaPrivateKey':
                    $this->setRsaPrivateKey($value);
                    break;
                case 'rsaPublicKey':
                    $this->setRsaPublicKey($value);
                    break;
            }
        }
        if (isset($options['requestScheme'])) {
            $this->setRequestScheme($options['requestScheme']);
        }
        return $this;
    }
    public function setConsumerKey($key)
    {
        $this->_consumerKey = $key;
        return $this;
    }
    public function getConsumerKey()
    {
        return $this->_consumerKey;
    }
    public function setConsumerSecret($secret)
    {
        $this->_consumerSecret = $secret;
        return $this;
    }
    public function getConsumerSecret()
    {
        if ($this->_rsaPrivateKey !== null) {
            return $this->_rsaPrivateKey;
        }
        return $this->_consumerSecret;
    }
    public function setSignatureMethod($method)
    {
        $method = strtoupper($method);
        if (!in_array($method, array(
                'HMAC-SHA1', 'HMAC-SHA256', 'RSA-SHA1', 'PLAINTEXT'
            ))
        ) {
            throw new Zend_Oauth_Exception('Unsupported signature method: '
                . $method
                . '. Supported are HMAC-SHA1, RSA-SHA1, PLAINTEXT and HMAC-SHA256');
        }
        $this->_signatureMethod = $method;;
        return $this;
    }
    public function getSignatureMethod()
    {
        return $this->_signatureMethod;
    }
    public function setRequestScheme($scheme)
    {
        $scheme = strtolower($scheme);
        if (!in_array($scheme, array(
                Zend_Oauth::REQUEST_SCHEME_HEADER,
                Zend_Oauth::REQUEST_SCHEME_POSTBODY,
                Zend_Oauth::REQUEST_SCHEME_QUERYSTRING,
            ))
        ) {
            throw new Zend_Oauth_Exception(
                '\'' . $scheme . '\' is an unsupported request scheme'
            );
        }
        if ($scheme == Zend_Oauth::REQUEST_SCHEME_POSTBODY
            && $this->getRequestMethod() == Zend_Oauth::GET
        ) {
            throw new Zend_Oauth_Exception(
                'Cannot set POSTBODY request method if HTTP method set to GET'
            );
        }
        $this->_requestScheme = $scheme;
        return $this;
    }
    public function getRequestScheme()
    {
        return $this->_requestScheme;
    }
    public function setVersion($version)
    {
        $this->_version = $version;
        return $this;
    }
    public function getVersion()
    {
        return $this->_version;
    }
    public function setCallbackUrl($url)
    {
        if (!Zend_Uri::check($url)) {
            throw new Zend_Oauth_Exception(
                '\'' . $url . '\' is not a valid URI'
            );
        }
        $this->_callbackUrl = $url;
        return $this;
    }
    public function getCallbackUrl()
    {
        return $this->_callbackUrl;
    }
    public function setSiteUrl($url)
    {
        if (!Zend_Uri::check($url)) {
            throw new Zend_Oauth_Exception(
                '\'' . $url . '\' is not a valid URI'
            );
        }
        $this->_siteUrl = $url;
        return $this;
    }
    public function getSiteUrl()
    {
        return $this->_siteUrl;
    }
    public function setRequestTokenUrl($url)
    {
        if (!Zend_Uri::check($url)) {
            throw new Zend_Oauth_Exception(
                '\'' . $url . '\' is not a valid URI'
            );
        }
        $this->_requestTokenUrl = rtrim($url, '/');
        return $this;
    }
    public function getRequestTokenUrl()
    {
        if (!$this->_requestTokenUrl && $this->_siteUrl) {
            return $this->_siteUrl . '/request_token';
        }
        return $this->_requestTokenUrl;
    }
    public function setAccessTokenUrl($url)
    {
        if (!Zend_Uri::check($url)) {
            throw new Zend_Oauth_Exception(
                '\'' . $url . '\' is not a valid URI'
            );
        }
        $this->_accessTokenUrl = rtrim($url, '/');
        return $this;
    }
    public function getAccessTokenUrl()
    {
        if (!$this->_accessTokenUrl && $this->_siteUrl) {
            return $this->_siteUrl . '/access_token';
        }
        return $this->_accessTokenUrl;
    }
    public function setUserAuthorizationUrl($url)
    {
        return $this->setAuthorizeUrl($url);
    }
    public function setAuthorizeUrl($url)
    {
        if (!Zend_Uri::check($url)) {
            throw new Zend_Oauth_Exception(
                '\'' . $url . '\' is not a valid URI'
            );
        }
        $this->_authorizeUrl = rtrim($url, '/');
        return $this;
    }
    public function getUserAuthorizationUrl()
    {
        return $this->getAuthorizeUrl();
    }
    public function getAuthorizeUrl()
    {
        if (!$this->_authorizeUrl && $this->_siteUrl) {
            return $this->_siteUrl . '/authorize';
        }
        return $this->_authorizeUrl;
    }
    public function setRequestMethod($method)
    {
        $method = strtoupper($method);
        if (!in_array($method, array(
                Zend_Oauth::GET, 
                Zend_Oauth::POST, 
                Zend_Oauth::PUT, 
                Zend_Oauth::DELETE,
            ))
        ) {
            throw new Zend_Oauth_Exception('Invalid method: ' . $method);
        }
        $this->_requestMethod = $method;
        return $this;
    }
    public function getRequestMethod()
    {
        return $this->_requestMethod;
    }
    public function setRsaPublicKey(Zend_Crypt_Rsa_Key_Public $key)
    {
        $this->_rsaPublicKey = $key;
        return $this;
    }
    public function getRsaPublicKey()
    {
        return $this->_rsaPublicKey;
    }
    public function setRsaPrivateKey(Zend_Crypt_Rsa_Key_Private $key)
    {
        $this->_rsaPrivateKey = $key;
        return $this;
    }
    public function getRsaPrivateKey()
    {
        return $this->_rsaPrivateKey;
    }
    public function setToken(Zend_Oauth_Token $token)
    {
        $this->_token = $token;
        return $this;
    }
    public function getToken()
    {
        return $this->_token;
    }
}

}


if ( !@class_exists('Zend_Oauth_Consumer') ) {
class Zend_Oauth_Consumer extends Zend_Oauth
{
    public $switcheroo = false; // replace later when this works
    protected $_requestToken = null;
    protected $_accessToken = null;
    protected $_config = null;
    public function __construct($options = null)
    {
        $this->_config = new Zend_Oauth_Config;
        if ($options !== null) {
            if ($options instanceof Zend_Config) {
                $options = $options->toArray();
            }
            $this->_config->setOptions($options);
        }
    }
    public function getRequestToken(
        array $customServiceParameters = null,
        $httpMethod = null,
        Zend_Oauth_Http_RequestToken $request = null
    ) {
        if ($request === null) {
            $request = new Zend_Oauth_Http_RequestToken($this, $customServiceParameters);
        } elseif($customServiceParameters !== null) {
            $request->setParameters($customServiceParameters);
        }
        if ($httpMethod !== null) {
            $request->setMethod($httpMethod);
        } else {
            $request->setMethod($this->getRequestMethod());
        }
        $this->_requestToken = $request->execute();
        return $this->_requestToken;
    }
    public function getRedirectUrl(
        array $customServiceParameters = null,
        Zend_Oauth_Token_Request $token = null,
        Zend_Oauth_Http_UserAuthorization $redirect = null
    ) {
        if ($redirect === null) {
            $redirect = new Zend_Oauth_Http_UserAuthorization($this, $customServiceParameters);
        } elseif($customServiceParameters !== null) {
            $redirect->setParameters($customServiceParameters);
        }
        if ($token !== null) {
            $this->_requestToken = $token;
        }
        return $redirect->getUrl();
    }
    public function redirect(
        array $customServiceParameters = null,
        Zend_Oauth_Token_Request $token = null,
        Zend_Oauth_Http_UserAuthorization $request = null
    ) {
        if ($token instanceof Zend_Oauth_Http_UserAuthorization) {
            $request = $token;
            $token = null;
        }
        $redirectUrl = $this->getRedirectUrl($customServiceParameters, $token, $request);
        header('Location: ' . $redirectUrl);
        exit(1);
    }
    public function getAccessToken(
        $queryData, 
        Zend_Oauth_Token_Request $token,
        $httpMethod = null, 
        Zend_Oauth_Http_AccessToken $request = null
    ) {
        $authorizedToken = new Zend_Oauth_Token_AuthorizedRequest($queryData);
        if (!$authorizedToken->isValid()) {
            throw new Zend_Oauth_Exception(
                'Response from Service Provider is not a valid authorized request token');
        }
        if ($request === null) {
            $request = new Zend_Oauth_Http_AccessToken($this);
        }
        if ($authorizedToken->getParam('oauth_verifier') !== null) {
            $params = array_merge($request->getParameters(), array(
                'oauth_verifier' => $authorizedToken->getParam('oauth_verifier')
            ));
            $request->setParameters($params);
        }
        if ($httpMethod !== null) {
            $request->setMethod($httpMethod);
        } else {
            $request->setMethod($this->getRequestMethod());
        }
        if (isset($token)) {
            if ($authorizedToken->getToken() !== $token->getToken()) {
                throw new Zend_Oauth_Exception(
                    'Authorized token from Service Provider does not match'
                    . ' supplied Request Token details'
                );
            }
        } else {
            throw new Zend_Oauth_Exception('Request token must be passed to method');
        }
        $this->_requestToken = $token;
        $this->_accessToken = $request->execute();
        return $this->_accessToken;
    }
    public function getLastRequestToken()
    {
        return $this->_requestToken;
    }
    public function getLastAccessToken()
    {
        return $this->_accessToken;
    }
    public function getToken()
    {
        return $this->_accessToken;
    }
    public function __call($method, array $args)
    {
        if (!method_exists($this->_config, $method)) {
            throw new Zend_Oauth_Exception('Method does not exist: '.$method);
        }
        return call_user_func_array(array($this->_config,$method), $args);
    }
}

}


if ( !@class_exists('Zend_Oauth_Exception') ) {
class Zend_Oauth_Exception extends Zend_Exception {}
}


if ( !@class_exists('Zend_Oauth_Http') ) {
class Zend_Oauth_Http
{
    protected $_parameters = array();
    protected $_consumer = null;
    protected $_preferredRequestScheme = null;
    protected $_preferredRequestMethod = Zend_Oauth::POST;
    protected $_httpUtility = null;
    public function __construct(
        Zend_Oauth_Consumer $consumer, 
        array $parameters = null,
        Zend_Oauth_Http_Utility $utility = null
    ) {
        $this->_consumer = $consumer;
        $this->_preferredRequestScheme = $this->_consumer->getRequestScheme();
        if ($parameters !== null) {
            $this->setParameters($parameters);
        }
        if ($utility !== null) {
            $this->_httpUtility = $utility;
        } else {
            $this->_httpUtility = new Zend_Oauth_Http_Utility;
        }
    }
    public function setMethod($method)
    {
        if (!in_array($method, array(Zend_Oauth::POST, Zend_Oauth::GET))) {
            throw new Zend_Oauth_Exception('invalid HTTP method: ' . $method);
        }
        $this->_preferredRequestMethod = $method;
        return $this;
    }
    public function getMethod()
    {
        return $this->_preferredRequestMethod;
    }
    public function setParameters(array $customServiceParameters)
    {
        $this->_parameters = $customServiceParameters;
        return $this;
    }
    public function getParameters()
    {
        return $this->_parameters;
    }
    public function getConsumer()
    {
        return $this->_consumer;
    }
    public function startRequestCycle(array $params)
    {
        $response = null;
        $body     = null;
        $status   = null;
        try {
            $response = $this->_attemptRequest($params);
        } catch (Zend_Http_Client_Exception $e) {
            throw new Zend_Oauth_Exception('Error in HTTP request', null, $e);
        }
        if ($response !== null) {
            $body   = $response->getBody();
            $status = $response->getStatus();
        }
        if ($response === null // Request failure/exception
            || $status == 500  // Internal Server Error
            || $status == 400  // Bad Request
            || $status == 401  // Unauthorized
            || empty($body)    // Missing token
        ) {
            $this->_assessRequestAttempt($response);
            $response = $this->startRequestCycle($params);
        }
        return $response;
    }
    public function getRequestSchemeQueryStringClient(array $params, $url)
    {
        $client = Zend_Oauth::getHttpClient();
        $client->setUri($url);
        $client->getUri()->setQuery(
            $this->_httpUtility->toEncodedQueryString($params)
        );
        $client->setMethod($this->_preferredRequestMethod);
        return $client;
    }
    protected function _assessRequestAttempt(Zend_Http_Response $response = null)
    {
        switch ($this->_preferredRequestScheme) {
            case Zend_Oauth::REQUEST_SCHEME_HEADER:
                $this->_preferredRequestScheme = Zend_Oauth::REQUEST_SCHEME_POSTBODY;
                break;
            case Zend_Oauth::REQUEST_SCHEME_POSTBODY:
                $this->_preferredRequestScheme = Zend_Oauth::REQUEST_SCHEME_QUERYSTRING;
                break;
            default:
                throw new Zend_Oauth_Exception(
                    'Could not retrieve a valid Token response from Token URL:'
                    . ($response !== null 
                        ? PHP_EOL . $response->getBody()
                        : ' No body - check for headers')
                );
        }
    }
    protected function _toAuthorizationHeader(array $params, $realm = null)
    {
        $headerValue = array();
        $headerValue[] = 'OAuth realm="' . $realm . '"';
        foreach ($params as $key => $value) {
            if (!preg_match("/^oauth_/", $key)) {
                continue;
            }
            $headerValue[] = Zend_Oauth_Http_Utility::urlEncode($key)
                           . '="'
                           . Zend_Oauth_Http_Utility::urlEncode($value)
                           . '"';
        }
        return implode(",", $headerValue);
    }
}

}


if ( !@class_exists('Zend_Oauth_Http_AccessToken') ) {
class Zend_Oauth_Http_AccessToken extends Zend_Oauth_Http
{
    protected $_httpClient = null;
    public function execute()
    {
        $params   = $this->assembleParams();
        $response = $this->startRequestCycle($params);
        $return   = new Zend_Oauth_Token_Access($response);
        return $return;
    }
    public function assembleParams()
    {
        $params = array(
            'oauth_consumer_key'     => $this->_consumer->getConsumerKey(),
            'oauth_nonce'            => $this->_httpUtility->generateNonce(),
            'oauth_signature_method' => $this->_consumer->getSignatureMethod(),
            'oauth_timestamp'        => $this->_httpUtility->generateTimestamp(),
            'oauth_token'            => $this->_consumer->getLastRequestToken()->getToken(),
            'oauth_version'          => $this->_consumer->getVersion(),
        );
        if (!empty($this->_parameters)) {
            $params = array_merge($params, $this->_parameters);
        }
        $params['oauth_signature'] = $this->_httpUtility->sign(
            $params,
            $this->_consumer->getSignatureMethod(),
            $this->_consumer->getConsumerSecret(),
            $this->_consumer->getLastRequestToken()->getTokenSecret(),
            $this->_preferredRequestMethod,
            $this->_consumer->getAccessTokenUrl()
        );
        return $params;
    }
    public function getRequestSchemeHeaderClient(array $params)
    {
        $params      = $this->_cleanParamsOfIllegalCustomParameters($params);
        $headerValue = $this->_toAuthorizationHeader($params);
        $client      = Zend_Oauth::getHttpClient();
        $client->setUri($this->_consumer->getAccessTokenUrl());
        $client->setHeaders('Authorization', $headerValue);
        $client->setMethod($this->_preferredRequestMethod);
        return $client;
    }
    public function getRequestSchemePostBodyClient(array $params)
    {
        $params = $this->_cleanParamsOfIllegalCustomParameters($params);
        $client = Zend_Oauth::getHttpClient();
        $client->setUri($this->_consumer->getAccessTokenUrl());
        $client->setMethod($this->_preferredRequestMethod);
        $client->setRawData(
            $this->_httpUtility->toEncodedQueryString($params)
        );
        $client->setHeaders(
            Zend_Http_Client::CONTENT_TYPE,
            Zend_Http_Client::ENC_URLENCODED
        );
        return $client;
    }
    public function getRequestSchemeQueryStringClient(array $params, $url)
    {
        $params = $this->_cleanParamsOfIllegalCustomParameters($params);
        return parent::getRequestSchemeQueryStringClient($params, $url);
    }
    protected function _attemptRequest(array $params)
    {
        switch ($this->_preferredRequestScheme) {
            case Zend_Oauth::REQUEST_SCHEME_HEADER:
                $httpClient = $this->getRequestSchemeHeaderClient($params);
                break;
            case Zend_Oauth::REQUEST_SCHEME_POSTBODY:
                $httpClient = $this->getRequestSchemePostBodyClient($params);
                break;
            case Zend_Oauth::REQUEST_SCHEME_QUERYSTRING:
                $httpClient = $this->getRequestSchemeQueryStringClient($params,
                    $this->_consumer->getAccessTokenUrl());
                break;
        }
        return $httpClient->request();
    }
    protected function _cleanParamsOfIllegalCustomParameters(array $params)
    {
        foreach ($params as $key=>$value) {
            if (!preg_match("/^oauth_/", $key)) {
                unset($params[$key]);
            }
        }
        return $params;
    }
}

}


if ( !@class_exists('Zend_Oauth_Http_RequestToken') ) {
class Zend_Oauth_Http_RequestToken extends Zend_Oauth_Http
{
    protected $_httpClient = null;
    public function execute()
    {
        $params   = $this->assembleParams();
        $response = $this->startRequestCycle($params);
        $return   = new Zend_Oauth_Token_Request($response);
        return $return;
    }
    public function assembleParams()
    {
        $params = array(
            'oauth_consumer_key'     => $this->_consumer->getConsumerKey(),
            'oauth_nonce'            => $this->_httpUtility->generateNonce(),
            'oauth_timestamp'        => $this->_httpUtility->generateTimestamp(),
            'oauth_signature_method' => $this->_consumer->getSignatureMethod(),
            'oauth_version'          => $this->_consumer->getVersion(),
        );
        if ($this->_consumer->getCallbackUrl()) {
            $params['oauth_callback'] = $this->_consumer->getCallbackUrl();
        } else {
            $params['oauth_callback'] = 'oob';
        }
        if (!empty($this->_parameters)) {
            $params = array_merge($params, $this->_parameters);
        }
        $params['oauth_signature'] = $this->_httpUtility->sign(
            $params,
            $this->_consumer->getSignatureMethod(),
            $this->_consumer->getConsumerSecret(),
            null,
            $this->_preferredRequestMethod,
            $this->_consumer->getRequestTokenUrl()
        );
        return $params;
    }
    public function getRequestSchemeHeaderClient(array $params)
    {
        $headerValue = $this->_httpUtility->toAuthorizationHeader(
            $params
        );
        $client = Zend_Oauth::getHttpClient();
        $client->setUri($this->_consumer->getRequestTokenUrl());
        $client->setHeaders('Authorization', $headerValue);
        $rawdata = $this->_httpUtility->toEncodedQueryString($params, true);
        if (!empty($rawdata)) {
            $client->setRawData($rawdata, 'application/x-www-form-urlencoded');
        }
        $client->setMethod($this->_preferredRequestMethod);
        return $client;
    }
    public function getRequestSchemePostBodyClient(array $params)
    {
        $client = Zend_Oauth::getHttpClient();
        $client->setUri($this->_consumer->getRequestTokenUrl());
        $client->setMethod($this->_preferredRequestMethod);
        $client->setRawData(
            $this->_httpUtility->toEncodedQueryString($params)
        );
        $client->setHeaders(
            Zend_Http_Client::CONTENT_TYPE,
            Zend_Http_Client::ENC_URLENCODED
        );
        return $client;
    }
    protected function _attemptRequest(array $params)
    {
        switch ($this->_preferredRequestScheme) {
            case Zend_Oauth::REQUEST_SCHEME_HEADER:
                $httpClient = $this->getRequestSchemeHeaderClient($params);
                break;
            case Zend_Oauth::REQUEST_SCHEME_POSTBODY:
                $httpClient = $this->getRequestSchemePostBodyClient($params);
                break;
            case Zend_Oauth::REQUEST_SCHEME_QUERYSTRING:
                $httpClient = $this->getRequestSchemeQueryStringClient($params,
                    $this->_consumer->getRequestTokenUrl());
                break;
        }
        return $httpClient->request();
    }
}

}


if ( !@class_exists('Zend_Oauth_Http_UserAuthorization') ) {
class Zend_Oauth_Http_UserAuthorization extends Zend_Oauth_Http
{
    public function getUrl()
    {
        $params = $this->assembleParams();
        $uri    = Zend_Uri_Http::fromString($this->_consumer->getUserAuthorizationUrl());
        $uri->setQuery(
            $this->_httpUtility->toEncodedQueryString($params)
        );
        return $uri->getUri();
    }
    public function assembleParams()
    {
        $params = array(
            'oauth_token' => $this->_consumer->getLastRequestToken()->getToken(),
        );
        if (!Zend_Oauth_Client::$supportsRevisionA) {
            $callback = $this->_consumer->getCallbackUrl();
            if (!empty($callback)) {
                $params['oauth_callback'] = $callback;
            }
        }
        if (!empty($this->_parameters)) {
            $params = array_merge($params, $this->_parameters);
        }
        return $params;
    }
}

}


if ( !@class_exists('Zend_Oauth_Http_Utility') ) {
class Zend_Oauth_Http_Utility
{
    public function assembleParams(
        $url, 
        Zend_Oauth_Config_ConfigInterface $config,
        array $serviceProviderParams = null
    ) {
        $params = array(
            'oauth_consumer_key'     => $config->getConsumerKey(),
            'oauth_nonce'            => $this->generateNonce(),
            'oauth_signature_method' => $config->getSignatureMethod(),
            'oauth_timestamp'        => $this->generateTimestamp(),
            'oauth_version'          => $config->getVersion(),
        );
        if ($config->getToken()->getToken() != null) {
            $params['oauth_token'] = $config->getToken()->getToken();
        }
        if ($serviceProviderParams !== null) {
            $params = array_merge($params, $serviceProviderParams);
        }
        $params['oauth_signature'] = $this->sign(
            $params,
            $config->getSignatureMethod(),
            $config->getConsumerSecret(),
            $config->getToken()->getTokenSecret(),
            $config->getRequestMethod(),
            $url
        );
        return $params;
    }
    public function toEncodedQueryString(array $params, $customParamsOnly = false)
    {
        if ($customParamsOnly) {
            foreach ($params as $key=>$value) {
                if (preg_match("/^oauth_/", $key)) {
                    unset($params[$key]);
                }
            }
        }
        $encodedParams = array();
        foreach ($params as $key => $value) {
            $encodedParams[] = self::urlEncode($key) 
                             . '=' 
                             . self::urlEncode($value);
        }
        return implode('&', $encodedParams);
    }
    public function toAuthorizationHeader(array $params, $realm = null, $excludeCustomParams = true)
    {
        $headerValue = array(
            'OAuth realm="' . $realm . '"',
        );
        foreach ($params as $key => $value) {
            if ($excludeCustomParams) {
                if (!preg_match("/^oauth_/", $key)) {
                    continue;
                }
            }
            $headerValue[] = self::urlEncode($key) 
                           . '="'
                           . self::urlEncode($value) . '"';
        }
        return implode(",", $headerValue);
    }
    public function sign(
        array $params, $signatureMethod, $consumerSecret, $tokenSecret = null, $method = null, $url = null
    ) {
        $className = '';
        $hashAlgo  = null;
        $parts     = explode('-', $signatureMethod);
        if (count($parts) > 1) {
            $className = 'Zend_Oauth_Signature_' . ucfirst(strtolower($parts[0]));
            $hashAlgo  = $parts[1];
        } else {
            $className = 'Zend_Oauth_Signature_' . ucfirst(strtolower($signatureMethod));
        }
        $signatureObject = new $className($consumerSecret, $tokenSecret, $hashAlgo);
        return $signatureObject->sign($params, $method, $url);
    }
    public function parseQueryString($query)
    {
        $params = array();
        if (empty($query)) {
            return array();
        }
        $parts = explode('&', $query);
        foreach ($parts as $pair) {
            $kv = explode('=', $pair);
            $params[rawurldecode($kv[0])] = rawurldecode($kv[1]);
        }
        return $params;
    }
    public function generateNonce()
    {
        return md5(uniqid(rand(), true));
    }
    public function generateTimestamp()
    {
        return time();
    }
    public static function urlEncode($value)
    {
        $encoded = rawurlencode($value);
        $encoded = str_replace('%7E', '~', $encoded);
        return $encoded;
    }
}

}


if ( !@class_exists('Zend_Oauth_Signature_SignatureAbstract') ) {
abstract class Zend_Oauth_Signature_SignatureAbstract
{
    protected $_hashAlgorithm = null;
    protected $_key = null;
    protected $_consumerSecret = null;
    protected $_tokenSecret = '';
    public function __construct($consumerSecret, $tokenSecret = null, $hashAlgo = null)
    {
        $this->_consumerSecret = $consumerSecret;
        if (isset($tokenSecret)) {
            $this->_tokenSecret = $tokenSecret;
        }
        $this->_key = $this->_assembleKey();
        if (isset($hashAlgo)) {
            $this->_hashAlgorithm = $hashAlgo;
        }
    }
    public abstract function sign(array $params, $method = null, $url = null);
    public function normaliseBaseSignatureUrl($url)
    {
        $uri = Zend_Uri_Http::fromString($url);
        if ($uri->getScheme() == 'http' && $uri->getPort() == '80') {
            $uri->setPort('');
        } elseif ($uri->getScheme() == 'https' && $uri->getPort() == '443') {
            $uri->setPort('');
        }
        $uri->setQuery('');
        $uri->setFragment('');
        $uri->setHost(strtolower($uri->getHost()));
        return $uri->getUri(true);
    }
    protected function _assembleKey()
    {
        $parts = array($this->_consumerSecret);
        if ($this->_tokenSecret !== null) {
            $parts[] = $this->_tokenSecret;
        }
        foreach ($parts as $key => $secret) {
            $parts[$key] = Zend_Oauth_Http_Utility::urlEncode($secret);
        }
        return implode('&', $parts);
    }
    protected function _getBaseSignatureString(array $params, $method = null, $url = null)
    {
        $encodedParams = array();
        foreach ($params as $key => $value) {
            $encodedParams[Zend_Oauth_Http_Utility::urlEncode($key)] = 
                Zend_Oauth_Http_Utility::urlEncode($value);
        }
        $baseStrings = array();
        if (isset($method)) {
            $baseStrings[] = strtoupper($method);
        }
        if (isset($url)) {
            $baseStrings[] = Zend_Oauth_Http_Utility::urlEncode(
                $this->normaliseBaseSignatureUrl($url)
            );
        }
        if (isset($encodedParams['oauth_signature'])) {
            unset($encodedParams['oauth_signature']);
        }
        $baseStrings[] = Zend_Oauth_Http_Utility::urlEncode(
            $this->_toByteValueOrderedQueryString($encodedParams)
        );
        return implode('&', $baseStrings);
    }
    protected function _toByteValueOrderedQueryString(array $params)
    {
        $return = array();
        uksort($params, 'strnatcmp');
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                natsort($value);
                foreach ($value as $keyduplicate) {
                    $return[] = $key . '=' . $keyduplicate;
                }
            } else {
                $return[] = $key . '=' . $value;
            }
        }
        return implode('&', $return);
    }
}

}


if ( !@class_exists('Zend_Oauth_Signature_Hmac') ) {
class Zend_Oauth_Signature_Hmac extends Zend_Oauth_Signature_SignatureAbstract
{
    public function sign(array $params, $method = null, $url = null)
    {
        $binaryHash = Zend_Crypt_Hmac::compute(
            $this->_key,
            $this->_hashAlgorithm,
            $this->_getBaseSignatureString($params, $method, $url),
            Zend_Crypt_Hmac::BINARY
        );
        return base64_encode($binaryHash);
    }
}

}


if ( !@class_exists('Zend_Oauth_Signature_Plaintext') ) {
class Zend_Oauth_Signature_Plaintext extends Zend_Oauth_Signature_SignatureAbstract
{
    public function sign(array $params, $method = null, $url = null)
    {
        if ($this->_tokenSecret === null) {
            return $this->_consumerSecret . '&';
        }
        $return = implode('&', array($this->_consumerSecret, $this->_tokenSecret));
        return $return;
    }
}

}


if ( !@class_exists('Zend_Oauth_Signature_Rsa') ) {
class Zend_Oauth_Signature_Rsa extends Zend_Oauth_Signature_SignatureAbstract
{
    public function sign(array $params, $method = null, $url = null) 
    {
        $rsa = new Zend_Crypt_Rsa;
        $rsa->setHashAlgorithm($this->_hashAlgorithm);
        $sign = $rsa->sign(
            $this->_getBaseSignatureString($params, $method, $url),
            $this->_key,
            Zend_Crypt_Rsa::BASE64
        );
        return $sign;
    }
    protected function _assembleKey()
    {
        return $this->_consumerSecret;
    }
}

}


if ( !@class_exists('Zend_Oauth_Token') ) {
abstract class Zend_Oauth_Token
{
    /**@+
     * Token constants
     */
    const TOKEN_PARAM_KEY                = 'oauth_token';
    const TOKEN_SECRET_PARAM_KEY         = 'oauth_token_secret';
    const TOKEN_PARAM_CALLBACK_CONFIRMED = 'oauth_callback_confirmed';
    /**@-*/
    protected $_params = array();
    protected $_response = null;
    protected $_httpUtility = null;
    public function __construct(
        Zend_Http_Response $response = null,
        Zend_Oauth_Http_Utility $utility = null
    ) {
        if ($response !== null) {
            $this->_response = $response;
            $params = $this->_parseParameters($response);
            if (count($params) > 0) {
                $this->setParams($params);
            }
        }
        if ($utility !== null) {
            $this->_httpUtility = $utility;
        } else {
            $this->_httpUtility = new Zend_Oauth_Http_Utility;
        }
    }
    public function isValid()
    {
        if (isset($this->_params[self::TOKEN_PARAM_KEY])
            && !empty($this->_params[self::TOKEN_PARAM_KEY])
            && isset($this->_params[self::TOKEN_SECRET_PARAM_KEY])
        ) {
            return true;
        }
        return false;
    }
    public function getResponse()
    {
        return $this->_response;
    }
    public function setTokenSecret($secret)
    {
        $this->setParam(self::TOKEN_SECRET_PARAM_KEY, $secret);
        return $this;
    }
    public function getTokenSecret()
    {
        return $this->getParam(self::TOKEN_SECRET_PARAM_KEY);
    }
    public function setParam($key, $value)
    {
        $this->_params[$key] = trim($value, "\n");
        return $this;
    }
    public function setParams(array $params)
    {
        foreach ($params as $key=>$value) {
            $this->setParam($key, $value);
        }
        return $this;
    }
    public function getParam($key)
    {
        if (isset($this->_params[$key])) {
            return $this->_params[$key];
        }
        return null;
    }
    public function setToken($token)
    {
        $this->setParam(self::TOKEN_PARAM_KEY, $token);
        return $this;
    }
    public function getToken()
    {
        return $this->getParam(self::TOKEN_PARAM_KEY);
    }
    public function __get($key)
    {
        return $this->getParam($key);
    }
    public function __set($key, $value)
    {
        $this->setParam($key, $value);
    }
    public function toString()
    {
        return $this->_httpUtility->toEncodedQueryString($this->_params);
    }
    public function __toString()
    {
        return $this->toString();
    }
    protected function _parseParameters(Zend_Http_Response $response)
    {
        $params = array();
        $body   = $response->getBody();
        if (empty($body)) {
            return;
        }
        $parts = explode('&', $body);
        foreach ($parts as $kvpair) {
            $pair = explode('=', $kvpair);
            $params[rawurldecode($pair[0])] = rawurldecode($pair[1]);
        }
        return $params;
    }
    public function __sleep() 
    {
        return array('_params');
    }
    public function __wakeup() 
    {
        if ($this->_httpUtility === null) {
            $this->_httpUtility = new Zend_Oauth_Http_Utility;
        }
    }
}

}


if ( !@class_exists('Zend_Oauth_Token_Access') ) {
class Zend_Oauth_Token_Access extends Zend_Oauth_Token
{
    public function toHeader(
        $url, Zend_Oauth_Config_ConfigInterface $config, array $customParams = null, $realm = null
    ) {
        if (!Zend_Uri::check($url)) {
            throw new Zend_Oauth_Exception(
                '\'' . $url . '\' is not a valid URI'
            );
        }
        $params = $this->_httpUtility->assembleParams($url, $config, $customParams);
        return $this->_httpUtility->toAuthorizationHeader($params, $realm);
    }
    public function toQueryString($url, Zend_Oauth_Config_ConfigInterface $config, array $params = null)
    {
        if (!Zend_Uri::check($url)) {
            throw new Zend_Oauth_Exception(
                '\'' . $url . '\' is not a valid URI'
            );
        }
        $params = $this->_httpUtility->assembleParams($url, $config, $params);
        return $this->_httpUtility->toEncodedQueryString($params);
    }
    public function getHttpClient(array $oauthOptions, $uri = null, $config = null, $excludeCustomParamsFromHeader = true)
    {
        $client = new Zend_Oauth_Client($oauthOptions, $uri, $config, $excludeCustomParamsFromHeader);
        $client->setToken($this);
        return $client;
    }
}

}


if ( !@class_exists('Zend_Oauth_Token_AuthorizedRequest') ) {
class Zend_Oauth_Token_AuthorizedRequest extends Zend_Oauth_Token
{
    protected $_data = array();
    public function __construct(array $data = null, Zend_Oauth_Http_Utility $utility = null)
    {
        if ($data !== null) {
            $this->_data = $data;
            $params = $this->_parseData();
            if (count($params) > 0) {
                $this->setParams($params);
            }
        }
        if ($utility !== null) {
            $this->_httpUtility = $utility;
        } else {
            $this->_httpUtility = new Zend_Oauth_Http_Utility;
        }
    }
    public function getData()
    {
        return $this->_data;
    }
    public function isValid()
    {
        if (isset($this->_params[self::TOKEN_PARAM_KEY])
            && !empty($this->_params[self::TOKEN_PARAM_KEY])
        ) {
            return true;
        }
        return false;
    }
    protected function _parseData()
    {
        $params = array();
        if (empty($this->_data)) {
            return;
        }
        foreach ($this->_data as $key => $value) {
            $params[rawurldecode($key)] = rawurldecode($value);
        }
        return $params;
    }
}

}


if ( !@class_exists('Zend_Oauth_Token_Request') ) {
class Zend_Oauth_Token_Request extends Zend_Oauth_Token
{
    public function __construct(
        Zend_Http_Response $response = null,
        Zend_Oauth_Http_Utility $utility = null
    ) {
        parent::__construct($response, $utility);
        if (isset($this->_params[Zend_Oauth_Token::TOKEN_PARAM_CALLBACK_CONFIRMED])) {
            Zend_Oauth_Client::$supportsRevisionA = true;
        }
    }
}

}


if ( !@class_exists('Zend_Crypt') ) {
class Zend_Crypt
{
    const TYPE_OPENSSL = 'openssl';
    const TYPE_HASH = 'hash';
    const TYPE_MHASH = 'mhash';
    protected static $_type = null;
    protected static $_supportedAlgosOpenssl = array(
        'md2',
        'md4',
        'mdc2',
        'rmd160',
        'sha',
        'sha1',
        'sha224',
        'sha256',
        'sha384',
        'sha512'
    );
    protected static $_supportedAlgosMhash = array(
        'adler32',
        'crc32',
        'crc32b',
        'gost',
        'haval128',
        'haval160',
        'haval192',
        'haval256',
        'md4',
        'md5',
        'ripemd160',
        'sha1',
        'sha256',
        'tiger',
        'tiger128',
        'tiger160'
    );
    public static function hash($algorithm, $data, $binaryOutput = false)
    {
        $algorithm = strtolower($algorithm);
        if (function_exists($algorithm)) {
            return $algorithm($data, $binaryOutput);
        }
        self::_detectHashSupport($algorithm);
        $supportedMethod = '_digest' . ucfirst(self::$_type);
        $result = self::$supportedMethod($algorithm, $data, $binaryOutput);
        return $result;
    }
    protected static function _detectHashSupport($algorithm)
    {
        if (function_exists('hash')) {
            self::$_type = self::TYPE_HASH;
            if (in_array($algorithm, hash_algos())) {
               return;
            }
        }
        if (function_exists('mhash')) {
            self::$_type = self::TYPE_MHASH;
            if (in_array($algorithm, self::$_supportedAlgosMhash)) {
               return;
            }
        }
        if (function_exists('openssl_digest')) {
            if ($algorithm == 'ripemd160') {
                $algorithm = 'rmd160';
            }
            self::$_type = self::TYPE_OPENSSL;
            if (in_array($algorithm, self::$_supportedAlgosOpenssl)) {
               return;
            }
        }
        throw new Zend_Crypt_Exception('\'' . $algorithm . '\' is not supported by any available extension or native function');
    }
    protected static function _digestHash($algorithm, $data, $binaryOutput)
    {
        return hash($algorithm, $data, $binaryOutput);
    }
    protected static function _digestMhash($algorithm, $data, $binaryOutput)
    {
        $constant = constant('MHASH_' . strtoupper($algorithm));
        $binary = mhash($constant, $data);
        if ($binaryOutput) {
            return $binary;
        }
        return bin2hex($binary);
    }
    protected static function _digestOpenssl($algorithm, $data, $binaryOutput)
    {
        if ($algorithm == 'ripemd160') {
            $algorithm = 'rmd160';
        }
        return openssl_digest($data, $algorithm, $binaryOutput);
    }
}

}


if ( !@class_exists('Zend_Crypt_Hmac') ) {
class Zend_Crypt_Hmac extends Zend_Crypt
{
    protected static $_key = null;
    protected static $_packFormat = null;
    protected static $_hashAlgorithm = 'md5';
    protected static $_supportedMhashAlgorithms = array('adler32',' crc32', 'crc32b', 'gost',
            'haval128', 'haval160', 'haval192', 'haval256', 'md4', 'md5', 'ripemd160',
            'sha1', 'sha256', 'tiger', 'tiger128', 'tiger160');
    const STRING = 'string';
    const BINARY = 'binary';
    public static function compute($key, $hash, $data, $output = self::STRING)
    {
        if (!isset($key) || empty($key)) {
            throw new Zend_Crypt_Hmac_Exception('provided key is null or empty');
        }
        self::$_key = $key;
        self::_setHashAlgorithm($hash);
        return self::_hash($data, $output);
    }
    protected static function _setHashAlgorithm($hash)
    {
        if (!isset($hash) || empty($hash)) {
            throw new Zend_Crypt_Hmac_Exception('provided hash string is null or empty');
        }
        $hash = strtolower($hash);
        $hashSupported = false;
        if (function_exists('hash_algos') && in_array($hash, hash_algos())) {
            $hashSupported = true;
        }
        if ($hashSupported === false && function_exists('mhash') && in_array($hash, self::$_supportedAlgosMhash)) {
            $hashSupported = true;
        }
        if ($hashSupported === false) {
            throw new Zend_Crypt_Hmac_Exception('hash algorithm provided is not supported on this PHP installation; please enable the hash or mhash extensions');
        }
        self::$_hashAlgorithm = $hash;
    }
    protected static function _hash($data, $output = self::STRING, $internal = false)
    {
        if (function_exists('hash_hmac')) {
            if ($output == self::BINARY) {
                return hash_hmac(self::$_hashAlgorithm, $data, self::$_key, 1);
            }
            return hash_hmac(self::$_hashAlgorithm, $data, self::$_key);
        }
        if (function_exists('mhash')) {
            if ($output == self::BINARY) {
                return mhash(self::_getMhashDefinition(self::$_hashAlgorithm), $data, self::$_key);
            }
            $bin = mhash(self::_getMhashDefinition(self::$_hashAlgorithm), $data, self::$_key);
            return bin2hex($bin);
        }
    }
    protected static function _getMhashDefinition($hashAlgorithm)
    {
        for ($i = 0; $i <= mhash_count(); $i++)
        {
            $types[mhash_get_hash_name($i)] = $i;
        }
        return $types[strtoupper($hashAlgorithm)];
    }
}
}