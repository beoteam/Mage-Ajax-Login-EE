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



global $apiConfig;
$apiConfig = array(
    // True if objects should be returned by the service classes.
    // False if associative arrays should be returned (default behavior).
    'use_objects' => false,
  
    // The application_name is included in the User-Agent HTTP header.
    'application_name' => '',

    // OAuth2 Settings, you can get these keys at https://code.google.com/apis/console
    'oauth2_client_id' => '',
    'oauth2_client_secret' => '',
    'oauth2_redirect_uri' => '',

    // The developer key, you get this at https://code.google.com/apis/console
    'developer_key' => '',
  
    // Site name to show in the Google's OAuth 1 authentication screen.
    'site_name' => 'www.example.org',

    // Which Authentication, Storage and HTTP IO classes to use.
    'authClass'    => 'Google_OAuth2',
    'ioClass'      => 'Google_CurlIO',
    'cacheClass'   => 'Google_FileCache',

    // Don't change these unless you're working against a special development or testing environment.
    'basePath' => 'https://www.googleapis.com',

    // IO Class dependent configuration, you only have to configure the values
    // for the class that was configured as the ioClass above
    'ioFileCache_directory'  =>
        (function_exists('sys_get_temp_dir') ?
            sys_get_temp_dir() . '/Google_Client' :
        '/tmp/Google_Client'),

    // Definition of service specific values like scopes, oauth token URLs, etc
    'services' => array(
      'analytics' => array('scope' => 'https://www.googleapis.com/auth/analytics.readonly'),
      'calendar' => array(
          'scope' => array(
              "https://www.googleapis.com/auth/calendar",
              "https://www.googleapis.com/auth/calendar.readonly",
          )
      ),
      'books' => array('scope' => 'https://www.googleapis.com/auth/books'),
      'latitude' => array(
          'scope' => array(
              'https://www.googleapis.com/auth/latitude.all.best',
              'https://www.googleapis.com/auth/latitude.all.city',
          )
      ),
      'moderator' => array('scope' => 'https://www.googleapis.com/auth/moderator'),
      'oauth2' => array(
          'scope' => array(
              'https://www.googleapis.com/auth/userinfo.profile',
              'https://www.googleapis.com/auth/userinfo.email',
          )
      ),
      'plus' => array('scope' => 'https://www.googleapis.com/auth/plus.login'),
      'siteVerification' => array('scope' => 'https://www.googleapis.com/auth/siteverification'),
      'tasks' => array('scope' => 'https://www.googleapis.com/auth/tasks'),
      'urlshortener' => array('scope' => 'https://www.googleapis.com/auth/urlshortener')
    )
);
abstract class Google_Auth {
  abstract public function authenticate($service);
  abstract public function sign(Google_HttpRequest $request);
  abstract public function createAuthUrl($scope);
  abstract public function getAccessToken();
  abstract public function setAccessToken($accessToken);
  abstract public function setDeveloperKey($developerKey);
  abstract public function refreshToken($refreshToken);
  abstract public function revokeToken();
}

abstract class Google_Verifier {
  abstract public function verify($data, $signature);
}

class Google_PemVerifier extends Google_Verifier {
  private $publicKey;
  function __construct($pem) {
    if (!function_exists('openssl_x509_read')) {
      throw new Google_Exception('Google API PHP client needs the openssl PHP extension');
    }
    $this->publicKey = openssl_x509_read($pem);
    if (!$this->publicKey) {
      throw new Google_AuthException("Unable to parse PEM: $pem");
    }
  }
  function __destruct() {
    if ($this->publicKey) {
      openssl_x509_free($this->publicKey);
    }
  }
  function verify($data, $signature) {
    $status = openssl_verify($data, $signature, $this->publicKey, "sha256");
    if ($status === -1) {
      throw new Google_AuthException('Signature verification error: ' . openssl_error_string());
    }
    return $status === 1;
  }
}


class Google_LoginTicket {
  const USER_ATTR = "id";
  private $envelope;
  private $payload;
  public function __construct($envelope, $payload) {
    $this->envelope = $envelope;
    $this->payload = $payload;
  }
  public function getUserId() {
    if (array_key_exists(self::USER_ATTR, $this->payload)) {
      return $this->payload[self::USER_ATTR];
    }
    throw new Google_AuthException("No user_id in token");
  }
  public function getAttributes() {
    return array("envelope" => $this->envelope, "payload" => $this->payload);
  }
}


class Google_Utils {
  public static function urlSafeB64Encode($data) {
    $b64 = base64_encode($data);
    $b64 = str_replace(array('+', '/', '\r', '\n', '='),
                       array('-', '_'),
                       $b64);
    return $b64;
  }
  public static function urlSafeB64Decode($b64) {
    $b64 = str_replace(array('-', '_'),
                       array('+', '/'),
                       $b64);
    return base64_decode($b64);
  }
  static public function getStrLen($str) {
    $strlenVar = strlen($str);
    $d = $ret = 0;
    for ($count = 0; $count < $strlenVar; ++ $count) {
      $ordinalValue = ord($str{$ret});
      switch (true) {
        case (($ordinalValue >= 0x20) && ($ordinalValue <= 0x7F)):
          $ret ++;
          break;
        case (($ordinalValue & 0xE0) == 0xC0):
          $ret += 2;
          break;
        case (($ordinalValue & 0xF0) == 0xE0):
          $ret += 3;
          break;
        case (($ordinalValue & 0xF8) == 0xF0):
          $ret += 4;
          break;
        case (($ordinalValue & 0xFC) == 0xF8):
          $ret += 5;
          break;
        case (($ordinalValue & 0xFE) == 0xFC):
          $ret += 6;
          break;
        default:
          $ret ++;
      }
    }
    return $ret;
  }
  public static function normalize($arr) {
    if (!is_array($arr)) {
      return array();
    }
    $normalized = array();
    foreach ($arr as $key => $val) {
      $normalized[strtolower($key)] = $val;
    }
    return $normalized;
  }
}


class Google_OAuth2 extends Google_Auth {
  public $clientId;
  public $clientSecret;
  public $developerKey;
  public $token;
  public $redirectUri;
  public $state;
  public $accessType = 'offline';
  public $approvalPrompt = 'force';
  public $requestVisibleActions;
  /** @var Google_AssertionCredentials $assertionCredentials */
  public $assertionCredentials;
  const OAUTH2_REVOKE_URI = 'https://accounts.google.com/o/oauth2/revoke';
  const OAUTH2_TOKEN_URI = 'https://accounts.google.com/o/oauth2/token';
  const OAUTH2_AUTH_URL = 'https://accounts.google.com/o/oauth2/auth';
  const OAUTH2_FEDERATED_SIGNON_CERTS_URL = 'https://www.googleapis.com/oauth2/v1/certs';
  const CLOCK_SKEW_SECS = 300; // five minutes in seconds
  const AUTH_TOKEN_LIFETIME_SECS = 300; // five minutes in seconds
  const MAX_TOKEN_LIFETIME_SECS = 86400; // one day in seconds
  public function __construct() {
    global $apiConfig;
    if (! empty($apiConfig['developer_key'])) {
      $this->developerKey = $apiConfig['developer_key'];
    }
    if (! empty($apiConfig['oauth2_client_id'])) {
      $this->clientId = $apiConfig['oauth2_client_id'];
    }
    if (! empty($apiConfig['oauth2_client_secret'])) {
      $this->clientSecret = $apiConfig['oauth2_client_secret'];
    }
    if (! empty($apiConfig['oauth2_redirect_uri'])) {
      $this->redirectUri = $apiConfig['oauth2_redirect_uri'];
    }
    if (! empty($apiConfig['oauth2_access_type'])) {
      $this->accessType = $apiConfig['oauth2_access_type'];
    }
    if (! empty($apiConfig['oauth2_approval_prompt'])) {
      $this->approvalPrompt = $apiConfig['oauth2_approval_prompt'];
    }
  }
  public function authenticate($service, $code = null) {
    if (!$code && isset($_GET['code'])) {
      $code = $_GET['code'];
    }
    if ($code) {
      $request = Google_Client::$io->makeRequest(new Google_HttpRequest(self::OAUTH2_TOKEN_URI, 'POST', array(), array(
          'code' => $code,
          'grant_type' => 'authorization_code',
          'redirect_uri' => $this->redirectUri,
          'client_id' => $this->clientId,
          'client_secret' => $this->clientSecret
      )));
      if ($request->getResponseHttpCode() == 200) {
        $this->setAccessToken($request->getResponseBody());
        $this->token['created'] = time();
        return $this->getAccessToken();
      } else {
        $response = $request->getResponseBody();
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse != null && $decodedResponse['error']) {
          $response = $decodedResponse['error'];
        }
        throw new Google_AuthException("Error fetching OAuth2 access token, message: '$response'", $request->getResponseHttpCode());
      }
    }
    $authUrl = $this->createAuthUrl($service['scope']);
    header('Location: ' . $authUrl);
    return true;
  }
  public function createAuthUrl($scope) {
    $params = array(
        'response_type=code',
        'redirect_uri=' . urlencode($this->redirectUri),
        'client_id=' . urlencode($this->clientId),
        'scope=' . urlencode($scope),
        'access_type=' . urlencode($this->accessType),
        'approval_prompt=' . urlencode($this->approvalPrompt),
    );
    if(strpos($scope, 'plus.login') && count($this->requestVisibleActions) > 0) {
        $params[] = 'request_visible_actions=' .
            urlencode($this->requestVisibleActions);
    }
    if (isset($this->state)) {
      $params[] = 'state=' . urlencode($this->state);
    }
    $params = implode('&', $params);
    return self::OAUTH2_AUTH_URL . "?$params";
  }
  public function setAccessToken($token) {
    $token = json_decode($token, true);
    if ($token == null) {
      throw new Google_AuthException('Could not json decode the token');
    }
    if (! isset($token['access_token'])) {
      throw new Google_AuthException("Invalid token format");
    }
    $this->token = $token;
  }
  public function getAccessToken() {
    return json_encode($this->token);
  }
  public function setDeveloperKey($developerKey) {
    $this->developerKey = $developerKey;
  }
  public function setState($state) {
    $this->state = $state;
  }
  public function setAccessType($accessType) {
    $this->accessType = $accessType;
  }
  public function setApprovalPrompt($approvalPrompt) {
    $this->approvalPrompt = $approvalPrompt;
  }
  public function setAssertionCredentials(Google_AssertionCredentials $creds) {
    $this->assertionCredentials = $creds;
  }
  public function sign(Google_HttpRequest $request) {
    if ($this->developerKey) {
      $requestUrl = $request->getUrl();
      $requestUrl .= (strpos($request->getUrl(), '?') === false) ? '?' : '&';
      $requestUrl .=  'key=' . urlencode($this->developerKey);
      $request->setUrl($requestUrl);
    }
    if (null == $this->token && null == $this->assertionCredentials) {
      return $request;
    }
    if ($this->isAccessTokenExpired()) {
      if ($this->assertionCredentials) {
        $this->refreshTokenWithAssertion();
      } else {
        if (! array_key_exists('refresh_token', $this->token)) {
            throw new Google_AuthException("The OAuth 2.0 access token has expired, "
                . "and a refresh token is not available. Refresh tokens are not "
                . "returned for responses that were auto-approved.");
        }
        $this->refreshToken($this->token['refresh_token']);
      }
    }
    $request->setRequestHeaders(
        array('Authorization' => 'Bearer ' . $this->token['access_token'])
    );
    return $request;
  }
  public function refreshToken($refreshToken) {
    $this->refreshTokenRequest(array(
        'client_id' => $this->clientId,
        'client_secret' => $this->clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token'
    ));
  }
  public function refreshTokenWithAssertion($assertionCredentials = null) {
    if (!$assertionCredentials) {
      $assertionCredentials = $this->assertionCredentials;
    }
    $this->refreshTokenRequest(array(
        'grant_type' => 'assertion',
        'assertion_type' => $assertionCredentials->assertionType,
        'assertion' => $assertionCredentials->generateAssertion(),
    ));
  }
  private function refreshTokenRequest($params) {
    $http = new Google_HttpRequest(self::OAUTH2_TOKEN_URI, 'POST', array(), $params);
    $request = Google_Client::$io->makeRequest($http);
    $code = $request->getResponseHttpCode();
    $body = $request->getResponseBody();
    if (200 == $code) {
      $token = json_decode($body, true);
      if ($token == null) {
        throw new Google_AuthException("Could not json decode the access token");
      }
      if (! isset($token['access_token']) || ! isset($token['expires_in'])) {
        throw new Google_AuthException("Invalid token format");
      }
      $this->token['access_token'] = $token['access_token'];
      $this->token['expires_in'] = $token['expires_in'];
      $this->token['created'] = time();
    } else {
      throw new Google_AuthException("Error refreshing the OAuth2 token, message: '$body'", $code);
    }
  }
  public function revokeToken($token = null) {
    if (!$token) {
      $token = $this->token['access_token'];
    }
    $request = new Google_HttpRequest(self::OAUTH2_REVOKE_URI, 'POST', array(), "token=$token");
    $response = Google_Client::$io->makeRequest($request);
    $code = $response->getResponseHttpCode();
    if ($code == 200) {
      $this->token = null;
      return true;
    }
    return false;
  }
  public function isAccessTokenExpired() {
    if (null == $this->token) {
      return true;
    }
    $expired = ($this->token['created']
        + ($this->token['expires_in'] - 30)) < time();
    return $expired;
  }
  private function getFederatedSignOnCerts() {
    $request = Google_Client::$io->makeRequest(new Google_HttpRequest(
        self::OAUTH2_FEDERATED_SIGNON_CERTS_URL));
    if ($request->getResponseHttpCode() == 200) {
      $certs = json_decode($request->getResponseBody(), true);
      if ($certs) {
        return $certs;
      }
    }
    throw new Google_AuthException(
        "Failed to retrieve verification certificates: '" .
            $request->getResponseBody() . "'.",
        $request->getResponseHttpCode());
  }
  public function verifyIdToken($id_token = null, $audience = null) {
    if (!$id_token) {
      $id_token = $this->token['id_token'];
    }
    $certs = $this->getFederatedSignonCerts();
    if (!$audience) {
      $audience = $this->clientId;
    }
    return $this->verifySignedJwtWithCerts($id_token, $certs, $audience);
  }
  function verifySignedJwtWithCerts($jwt, $certs, $required_audience) {
    $segments = explode(".", $jwt);
    if (count($segments) != 3) {
      throw new Google_AuthException("Wrong number of segments in token: $jwt");
    }
    $signed = $segments[0] . "." . $segments[1];
    $signature = Google_Utils::urlSafeB64Decode($segments[2]);
    $envelope = json_decode(Google_Utils::urlSafeB64Decode($segments[0]), true);
    if (!$envelope) {
      throw new Google_AuthException("Can't parse token envelope: " . $segments[0]);
    }
    $json_body = Google_Utils::urlSafeB64Decode($segments[1]);
    $payload = json_decode($json_body, true);
    if (!$payload) {
      throw new Google_AuthException("Can't parse token payload: " . $segments[1]);
    }
    $verified = false;
    foreach ($certs as $keyName => $pem) {
      $public_key = new Google_PemVerifier($pem);
      if ($public_key->verify($signed, $signature)) {
        $verified = true;
        break;
      }
    }
    if (!$verified) {
      throw new Google_AuthException("Invalid token signature: $jwt");
    }
    $iat = 0;
    if (array_key_exists("iat", $payload)) {
      $iat = $payload["iat"];
    }
    if (!$iat) {
      throw new Google_AuthException("No issue time in token: $json_body");
    }
    $earliest = $iat - self::CLOCK_SKEW_SECS;
    $now = time();
    $exp = 0;
    if (array_key_exists("exp", $payload)) {
      $exp = $payload["exp"];
    }
    if (!$exp) {
      throw new Google_AuthException("No expiration time in token: $json_body");
    }
    if ($exp >= $now + self::MAX_TOKEN_LIFETIME_SECS) {
      throw new Google_AuthException(
          "Expiration time too far in future: $json_body");
    }
    $latest = $exp + self::CLOCK_SKEW_SECS;
    if ($now < $earliest) {
      throw new Google_AuthException(
          "Token used too early, $now < $earliest: $json_body");
    }
    if ($now > $latest) {
      throw new Google_AuthException(
          "Token used too late, $now > $latest: $json_body");
    }
    $aud = $payload["aud"];
    if ($aud != $required_audience) {
      throw new Google_AuthException("Wrong recipient, $aud != $required_audience: $json_body");
    }
    return new Google_LoginTicket($envelope, $payload);
  }
}

class Google_HttpRequest {
  const USER_AGENT_SUFFIX = "google-api-php-client/0.6.5";
  private $batchHeaders = array(
    'Content-Type' => 'application/http',
    'Content-Transfer-Encoding' => 'binary',
    'MIME-Version' => '1.0',
    'Content-Length' => ''
  );
  protected $url;
  protected $requestMethod;
  protected $requestHeaders;
  protected $postBody;
  protected $userAgent;
  protected $responseHttpCode;
  protected $responseHeaders;
  protected $responseBody;
  public $accessKey;
  public function __construct($url, $method = 'GET', $headers = array(), $postBody = null) {
    $this->setUrl($url);
    $this->setRequestMethod($method);
    $this->setRequestHeaders($headers);
    $this->setPostBody($postBody);
    global $apiConfig;
    if (empty($apiConfig['application_name'])) {
      $this->userAgent = self::USER_AGENT_SUFFIX;
    } else {
      $this->userAgent = $apiConfig['application_name'] . " " . self::USER_AGENT_SUFFIX;
    }
  }
  public function getBaseUrl() {
    if ($pos = strpos($this->url, '?')) {
      return substr($this->url, 0, $pos);
    }
    return $this->url;
  }
  public function getQueryParams() {
    if ($pos = strpos($this->url, '?')) {
      $queryStr = substr($this->url, $pos + 1);
      $params = array();
      parse_str($queryStr, $params);
      return $params;
    }
    return array();
  }
  public function getResponseHttpCode() {
    return (int) $this->responseHttpCode;
  }
  public function setResponseHttpCode($responseHttpCode) {
    $this->responseHttpCode = $responseHttpCode;
  }
  public function getResponseHeaders() {
    return $this->responseHeaders;
  }
  public function getResponseBody() {
    return $this->responseBody;
  }
  public function setResponseHeaders($headers) {
    $headers = Google_Utils::normalize($headers);
    if ($this->responseHeaders) {
      $headers = array_merge($this->responseHeaders, $headers);
    }
    $this->responseHeaders = $headers;
  }
  public function getResponseHeader($key) {
    return isset($this->responseHeaders[$key])
        ? $this->responseHeaders[$key]
        : false;
  }
  public function setResponseBody($responseBody) {
    $this->responseBody = $responseBody;
  }
  public function getUrl() {
    return $this->url;
  }
  public function getRequestMethod() {
    return $this->requestMethod;
  }
  public function getRequestHeaders() {
    return $this->requestHeaders;
  }
  public function getRequestHeader($key) {
    return isset($this->requestHeaders[$key])
        ? $this->requestHeaders[$key]
        : false;
  }
  public function getPostBody() {
    return $this->postBody;
  }
  public function setUrl($url) {
    if (substr($url, 0, 4) == 'http') {
      $this->url = $url;
    } else {
      if (substr($url, 0, 1) !== '/') {
        $url = '/' . $url;
      }
      global $apiConfig;
      $this->url = $apiConfig['basePath'] . $url;
    }
  }
  public function setRequestMethod($method) {
    $this->requestMethod = strtoupper($method);
  }
  public function setRequestHeaders($headers) {
    $headers = Google_Utils::normalize($headers);
    if ($this->requestHeaders) {
      $headers = array_merge($this->requestHeaders, $headers);
    }
    $this->requestHeaders = $headers;
  }
  public function setPostBody($postBody) {
    $this->postBody = $postBody;
  }
  public function setUserAgent($userAgent) {
    $this->userAgent = $userAgent;
  }
  public function getUserAgent() {
    return $this->userAgent;
  }
  public function getCacheKey() {
    $key = $this->getUrl();
    if (isset($this->accessKey)) {
      $key .= $this->accessKey;
    }
    if (isset($this->requestHeaders['authorization'])) {
      $key .= $this->requestHeaders['authorization'];
    }
    return md5($key);
  }
  public function getParsedCacheControl() {
    $parsed = array();
    $rawCacheControl = $this->getResponseHeader('cache-control');
    if ($rawCacheControl) {
      $rawCacheControl = str_replace(', ', '&', $rawCacheControl);
      parse_str($rawCacheControl, $parsed);
    }
    return $parsed;
  }
  public function toBatchString($id) {
    $str = '';
    foreach($this->batchHeaders as $key => $val) {
      $str .= $key . ': ' . $val . "\n";
    }
    $str .= "Content-ID: $id\n";
    $str .= "\n";
    $path = parse_url($this->getUrl(), PHP_URL_PATH);
    $str .= $this->getRequestMethod() . ' ' . $path . " HTTP/1.1\n";
    foreach($this->getRequestHeaders() as $key => $val) {
      $str .= $key . ': ' . $val . "\n";
    }
    if ($this->getPostBody()) {
      $str .= "\n";
      $str .= $this->getPostBody();
    }
    return $str;
  }
}

class Google_REST {
  static public function execute(Google_HttpRequest $req) {
    $httpRequest = Google_Client::$io->makeRequest($req);
    $decodedResponse = self::decodeHttpResponse($httpRequest);
    $ret = isset($decodedResponse['data'])
        ? $decodedResponse['data'] : $decodedResponse;
    return $ret;
  }
  public static function decodeHttpResponse($response) {
    $code = $response->getResponseHttpCode();
    $body = $response->getResponseBody();
    $decoded = null;
    if ((intVal($code)) >= 300) {
      $decoded = json_decode($body, true);
      $err = 'Error calling ' . $response->getRequestMethod() . ' ' . $response->getUrl();
      if ($decoded != null && isset($decoded['error']['message'])  && isset($decoded['error']['code'])) {
        $err .= ": ({$decoded['error']['code']}) {$decoded['error']['message']}";
      } else {
        $err .= ": ($code) $body";
      }
      throw new Google_ServiceException($err, $code, null, $decoded['error']['errors']);
    }
    if ($code != '204') {
      $decoded = json_decode($body, true);
      if ($decoded === null || $decoded === "") {
        throw new Google_ServiceException("Invalid json in service response: $body");
      }
    }
    return $decoded;
  }
  static function createRequestUri($servicePath, $restPath, $params) {
    $requestUrl = $servicePath . $restPath;
    $uriTemplateVars = array();
    $queryVars = array();
    foreach ($params as $paramName => $paramSpec) {
      if (! isset($paramSpec['location'])) {
        $paramSpec['location'] = $paramSpec['restParameterType'];
      }
      if ($paramSpec['type'] == 'boolean') {
        $paramSpec['value'] = ($paramSpec['value']) ? 'true' : 'false';
      }
      if ($paramSpec['location'] == 'path') {
        $uriTemplateVars[$paramName] = $paramSpec['value'];
      } else {
        if (isset($paramSpec['repeated']) && is_array($paramSpec['value'])) {
          foreach ($paramSpec['value'] as $value) {
            $queryVars[] = $paramName . '=' . rawurlencode($value);
          }
        } else {
          $queryVars[] = $paramName . '=' . rawurlencode($paramSpec['value']);
        }
      }
    }
    if (count($uriTemplateVars)) {
      $uriTemplateParser = new URI_Template_Parser($requestUrl);
      $requestUrl = $uriTemplateParser->expand($uriTemplateVars);
    }
    $requestUrl = str_replace('%40', '@', $requestUrl);
    if (count($queryVars)) {
      $requestUrl .= '?' . implode($queryVars, '&');
    }
    return $requestUrl;
  }
}

abstract class Google_IO {
  const CONNECTION_ESTABLISHED = "HTTP/1.0 200 Connection established\r\n\r\n";
  const FORM_URLENCODED = 'application/x-www-form-urlencoded';
  abstract function authenticatedRequest(Google_HttpRequest $request);
  abstract function makeRequest(Google_HttpRequest $request);
  abstract function setOptions($options);
  protected function setCachedRequest(Google_HttpRequest $request) {
    if (Google_CacheParser::isResponseCacheable($request)) {
      Google_Client::$cache->set($request->getCacheKey(), $request);
      return true;
    }
    return false;
  }
  protected function getCachedRequest(Google_HttpRequest $request) {
    if (false == Google_CacheParser::isRequestCacheable($request)) {
      false;
    }
    return Google_Client::$cache->get($request->getCacheKey());
  }
  protected function processEntityRequest(Google_HttpRequest $request) {
    $postBody = $request->getPostBody();
    $contentType = $request->getRequestHeader("content-type");
    if (false == $contentType) {
      $contentType = self::FORM_URLENCODED;
      $request->setRequestHeaders(array('content-type' => $contentType));
    }
    if ($contentType == self::FORM_URLENCODED && is_array($postBody)) {
      $postBody = http_build_query($postBody, '', '&');
      $request->setPostBody($postBody);
    }
    if (!$postBody || is_string($postBody)) {
      $postsLength = strlen($postBody);
      $request->setRequestHeaders(array('content-length' => $postsLength));
    }
    return $request;
  }
  protected function checkMustRevaliadateCachedRequest($cached, $request) {
    if (Google_CacheParser::mustRevalidate($cached)) {
      $addHeaders = array();
      if ($cached->getResponseHeader('etag')) {
        $addHeaders['If-None-Match'] = $cached->getResponseHeader('etag');
      } elseif ($cached->getResponseHeader('date')) {
        $addHeaders['If-Modified-Since'] = $cached->getResponseHeader('date');
      }
      $request->setRequestHeaders($addHeaders);
      return true;
    } else {
      return false;
    }
  }
  protected function updateCachedRequest($cached, $responseHeaders) {
    if (isset($responseHeaders['connection'])) {
      $hopByHop = array_merge(
        self::$HOP_BY_HOP,
        explode(',', $responseHeaders['connection'])
      );
      $endToEnd = array();
      foreach($hopByHop as $key) {
        if (isset($responseHeaders[$key])) {
          $endToEnd[$key] = $responseHeaders[$key];
        }
      }
      $cached->setResponseHeaders($endToEnd);
    }
  }
}

class Google_CacheParser {
  public static $CACHEABLE_HTTP_METHODS = array('GET', 'HEAD');
  public static $CACHEABLE_STATUS_CODES = array('200', '203', '300', '301');
  private function __construct() {}
  public static function isRequestCacheable (Google_HttpRequest $resp) {
    $method = $resp->getRequestMethod();
    if (! in_array($method, self::$CACHEABLE_HTTP_METHODS)) {
      return false;
    }
    if ($resp->getRequestHeader("authorization")) {
      return false;
    }
    return true;
  }
  public static function isResponseCacheable (Google_HttpRequest $resp) {
    if (false == self::isRequestCacheable($resp)) {
      return false;
    }
    $code = $resp->getResponseHttpCode();
    if (! in_array($code, self::$CACHEABLE_STATUS_CODES)) {
      return false;
    }
    $etag = $resp->getResponseHeader("etag");
    if (self::isExpired($resp) && $etag == false) {
      return false;
    }
    $cacheControl = $resp->getParsedCacheControl();
    if (isset($cacheControl['no-store'])) {
      return false;
    }
    $pragma = $resp->getResponseHeader('pragma');
    if ($pragma == 'no-cache' || strpos($pragma, 'no-cache') !== false) {
      return false;
    }
    $vary = $resp->getResponseHeader('vary');
    if ($vary) {
      return false;
    }
    return true;
  }
  public static function isExpired(Google_HttpRequest $resp) {
    $parsedExpires = false;
    $responseHeaders = $resp->getResponseHeaders();
    if (isset($responseHeaders['expires'])) {
      $rawExpires = $responseHeaders['expires'];
      if (empty($rawExpires) || (is_numeric($rawExpires) && $rawExpires <= 0)) {
        return true;
      }
      $parsedExpires = strtotime($rawExpires);
      if (false == $parsedExpires || $parsedExpires <= 0) {
        return true;
      }
    }
    $freshnessLifetime = false;
    $cacheControl = $resp->getParsedCacheControl();
    if (isset($cacheControl['max-age'])) {
      $freshnessLifetime = $cacheControl['max-age'];
    }
    $rawDate = $resp->getResponseHeader('date');
    $parsedDate = strtotime($rawDate);
    if (empty($rawDate) || false == $parsedDate) {
      $parsedDate = time();
    }
    if (false == $freshnessLifetime && isset($responseHeaders['expires'])) {
      $freshnessLifetime = $parsedExpires - $parsedDate;
    }
    if (false == $freshnessLifetime) {
      return true;
    }
    $age = max(0, time() - $parsedDate);
    if (isset($responseHeaders['age'])) {
      $age = max($age, strtotime($responseHeaders['age']));
    }
    return $freshnessLifetime <= $age;
  }
  public static function mustRevalidate(Google_HttpRequest $response) {
    return self::isExpired($response);
  }
}


class Google_CurlIO extends Google_IO {
  private static $ENTITY_HTTP_METHODS = array("POST" => null, "PUT" => null);
  private static $HOP_BY_HOP = array(
      'connection', 'keep-alive', 'proxy-authenticate', 'proxy-authorization',
      'te', 'trailers', 'transfer-encoding', 'upgrade');
  private $curlParams = array (
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => 0,
      CURLOPT_FAILONERROR => false,
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_HEADER => true,
      CURLOPT_VERBOSE => false,
  );
  public function __construct() {
    if (! function_exists('curl_init')) {
      throw new Exception(
        'Google CurlIO client requires the CURL PHP extension');
    }
  }
  public function authenticatedRequest(Google_HttpRequest $request) {
    $request = Google_Client::$auth->sign($request);
    return $this->makeRequest($request);
  }
  public function makeRequest(Google_HttpRequest $request) {
    $cached = $this->getCachedRequest($request);
    if ($cached !== false) {
      if (!$this->checkMustRevaliadateCachedRequest($cached, $request)) {
        return $cached;
      }
    }
    if (array_key_exists($request->getRequestMethod(),
          self::$ENTITY_HTTP_METHODS)) {
      $request = $this->processEntityRequest($request);
    }
    $ch = curl_init();
    curl_setopt_array($ch, $this->curlParams);
    curl_setopt($ch, CURLOPT_URL, $request->getUrl());
    if ($request->getPostBody()) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $request->getPostBody());
    }
    $requestHeaders = $request->getRequestHeaders();
    if ($requestHeaders && is_array($requestHeaders)) {
      $parsed = array();
      foreach ($requestHeaders as $k => $v) {
        $parsed[] = "$k: $v";
      }
      curl_setopt($ch, CURLOPT_HTTPHEADER, $parsed);
    }
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->getRequestMethod());
    curl_setopt($ch, CURLOPT_USERAGENT, $request->getUserAgent());
    $respData = curl_exec($ch);
    if (curl_errno($ch) == CURLE_SSL_CACERT) {
      error_log('SSL certificate problem, verify that the CA cert is OK.'
        . ' Retrying with the CA cert bundle from google-api-php-client.');
      curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacerts.pem');
      $respData = curl_exec($ch);
    }
    $respHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $respHttpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrorNum = curl_errno($ch);
    $curlError = curl_error($ch);
    curl_close($ch);
    if ($curlErrorNum != CURLE_OK) {
      throw new Google_IOException("HTTP Error: ($respHttpCode) $curlError");
    }
    list($responseHeaders, $responseBody) =
          self::parseHttpResponse($respData, $respHeaderSize);
    if ($respHttpCode == 304 && $cached) {
      $this->updateCachedRequest($cached, $responseHeaders);
      return $cached;
    }
    $request->setResponseHttpCode($respHttpCode);
    $request->setResponseHeaders($responseHeaders);
    $request->setResponseBody($responseBody);
    $this->setCachedRequest($request);
    return $request;
  }
  public function setOptions($optCurlParams) {
    foreach ($optCurlParams as $key => $val) {
      $this->curlParams[$key] = $val;
    }
  }
  private static function parseHttpResponse($respData, $headerSize) {
    if (stripos($respData, parent::CONNECTION_ESTABLISHED) !== false) {
      $respData = str_ireplace(parent::CONNECTION_ESTABLISHED, '', $respData);
    }
    if ($headerSize) {
      $responseBody = substr($respData, $headerSize);
      $responseHeaders = substr($respData, 0, $headerSize);
    } else {
      list($responseHeaders, $responseBody) = explode("\r\n\r\n", $respData, 2);
    }
    $responseHeaders = self::parseResponseHeaders($responseHeaders);
    return array($responseHeaders, $responseBody);
  }
  private static function parseResponseHeaders($rawHeaders) {
    $responseHeaders = array();
    $responseHeaderLines = explode("\r\n", $rawHeaders);
    foreach ($responseHeaderLines as $headerLine) {
      if ($headerLine && strpos($headerLine, ':') !== false) {
        list($header, $value) = explode(': ', $headerLine, 2);
        $header = strtolower($header);
        if (isset($responseHeaders[$header])) {
          $responseHeaders[$header] .= "\n" . $value;
        } else {
          $responseHeaders[$header] = $value;
        }
      }
    }
    return $responseHeaders;
  }
}


abstract class Google_Cache {
  abstract function get($key, $expiration = false);
  abstract function set($key, $value);
  abstract function delete($key);
}


class Google_FileCache extends Google_Cache {
  private $path;
  public function __construct() {
    global $apiConfig;
    $this->path = $apiConfig['ioFileCache_directory'];
  }
  private function isLocked($storageFile) {
    return file_exists($storageFile . '.lock');
  }
  private function createLock($storageFile) {
    $storageDir = dirname($storageFile);
    if (! is_dir($storageDir)) {
      if (! @mkdir($storageDir, 0755, true)) {
        if (! is_dir($storageDir)) {
          throw new Google_CacheException("Could not create storage directory: $storageDir");
        }
      }
    }
    @touch($storageFile . '.lock');
  }
  private function removeLock($storageFile) {
    @unlink($storageFile . '.lock');
  }
  private function waitForLock($storageFile) {
    $tries = 20;
    $cnt = 0;
    do {
      clearstatcache();
      usleep(250);
      $cnt ++;
    } while ($cnt <= $tries && $this->isLocked($storageFile));
    if ($this->isLocked($storageFile)) {
      $this->removeLock($storageFile);
    }
  }
  private function getCacheDir($hash) {
    return $this->path . '/' . substr($hash, 0, 2);
  }
  private function getCacheFile($hash) {
    return $this->getCacheDir($hash) . '/' . $hash;
  }
  public function get($key, $expiration = false) {
    $storageFile = $this->getCacheFile(md5($key));
    if ($this->isLocked($storageFile)) {
      $this->waitForLock($storageFile);
    }
    if (file_exists($storageFile) && is_readable($storageFile)) {
      $now = time();
      if (! $expiration || (($mtime = @filemtime($storageFile)) !== false && ($now - $mtime) < $expiration)) {
        if (($data = @file_get_contents($storageFile)) !== false) {
          $data = unserialize($data);
          return $data;
        }
      }
    }
    return false;
  }
  public function set($key, $value) {
    $storageDir = $this->getCacheDir(md5($key));
    $storageFile = $this->getCacheFile(md5($key));
    if ($this->isLocked($storageFile)) {
      $this->waitForLock($storageFile);
    }
    if (! is_dir($storageDir)) {
      if (! @mkdir($storageDir, 0755, true)) {
        throw new Google_CacheException("Could not create storage directory: $storageDir");
      }
    }
    $data = serialize($value);
    $this->createLock($storageFile);
    if (! @file_put_contents($storageFile, $data)) {
      $this->removeLock($storageFile);
      throw new Google_CacheException("Could not store data in the file");
    }
    $this->removeLock($storageFile);
  }
  public function delete($key) {
    $file = $this->getCacheFile(md5($key));
    if (! @unlink($file)) {
      throw new Google_CacheException("Cache file could not be deleted");
    }
  }
}


class Google_Model {
  public function __construct( /* polymorphic */ ) {
    if (func_num_args() ==  1 && is_array(func_get_arg(0))) {
      $array = func_get_arg(0);
      $this->mapTypes($array);
    }
  }
  protected function mapTypes($array) {
    foreach ($array as $key => $val) {
      $this->$key = $val;
      $keyTypeName = "__$key" . 'Type';
      $keyDataType = "__$key" . 'DataType';
      if ($this->useObjects() && property_exists($this, $keyTypeName)) {
        if ($this->isAssociativeArray($val)) {
          if (isset($this->$keyDataType) && 'map' == $this->$keyDataType) {
            foreach($val as $arrayKey => $arrayItem) {
              $val[$arrayKey] = $this->createObjectFromName($keyTypeName, $arrayItem);
            }
            $this->$key = $val;
          } else {
            $this->$key = $this->createObjectFromName($keyTypeName, $val);
          }
        } else if (is_array($val)) {
          $arrayObject = array();
          foreach ($val as $arrayIndex => $arrayItem) {
            $arrayObject[$arrayIndex] = $this->createObjectFromName($keyTypeName, $arrayItem);
          }
          $this->$key = $arrayObject;
        }
      }
    }
  }
  protected function isAssociativeArray($array) {
    if (!is_array($array)) {
      return false;
    }
    $keys = array_keys($array);
    foreach($keys as $key) {
      if (is_string($key)) {
        return true;
      }
    }
    return false;
  }
  private function createObjectFromName($name, $item) {
    $type = $this->$name;
    return new $type($item);
  }
  protected function useObjects() {
    global $apiConfig;
    return (isset($apiConfig['use_objects']) && $apiConfig['use_objects']);
  }
  public function assertIsArray($obj, $type, $method) {
    if ($obj && !is_array($obj)) {
      throw new Google_Exception("Incorrect parameter type passed to $method(), expected an"
          . " array containing items of type $type.");
    }
  }
}


class Google_Service {
  public $version;
  public $servicePath;
  public $resource;
}


class Google_ServiceResource {
  private $stackParameters = array(
      'alt' => array('type' => 'string', 'location' => 'query'),
      'boundary' => array('type' => 'string', 'location' => 'query'),
      'fields' => array('type' => 'string', 'location' => 'query'),
      'trace' => array('type' => 'string', 'location' => 'query'),
      'userIp' => array('type' => 'string', 'location' => 'query'),
      'userip' => array('type' => 'string', 'location' => 'query'),
      'quotaUser' => array('type' => 'string', 'location' => 'query'),
      'file' => array('type' => 'complex', 'location' => 'body'),
      'data' => array('type' => 'string', 'location' => 'body'),
      'mimeType' => array('type' => 'string', 'location' => 'header'),
      'uploadType' => array('type' => 'string', 'location' => 'query'),
      'mediaUpload' => array('type' => 'complex', 'location' => 'query'),
  );
  /** @var Google_Service $service */
  private $service;
  /** @var string $serviceName */
  private $serviceName;
  /** @var string $resourceName */
  private $resourceName;
  /** @var array $methods */
  private $methods;
  public function __construct($service, $serviceName, $resourceName, $resource) {
    $this->service = $service;
    $this->serviceName = $serviceName;
    $this->resourceName = $resourceName;
    $this->methods = isset($resource['methods']) ? $resource['methods'] : array($resourceName => $resource);
  }
  public function __call($name, $arguments) {
    if (! isset($this->methods[$name])) {
      throw new Google_Exception("Unknown function: {$this->serviceName}->{$this->resourceName}->{$name}()");
    }
    $method = $this->methods[$name];
    $parameters = $arguments[0];
    $postBody = null;
    if (isset($parameters['postBody'])) {
      if (is_object($parameters['postBody'])) {
        $this->stripNull($parameters['postBody']);
      }
      if (is_array($parameters['postBody']) && 'latitude' == $this->serviceName) {
        if (!isset($parameters['postBody']['data'])) {
          $rawBody = $parameters['postBody'];
          unset($parameters['postBody']);
          $parameters['postBody']['data'] = $rawBody;
        }
      }
      $postBody = is_array($parameters['postBody']) || is_object($parameters['postBody'])
          ? json_encode($parameters['postBody'])
          : $parameters['postBody'];
      unset($parameters['postBody']);
      if (isset($parameters['optParams'])) {
        $optParams = $parameters['optParams'];
        unset($parameters['optParams']);
        $parameters = array_merge($parameters, $optParams);
      }
    }
    if (!isset($method['parameters'])) {
      $method['parameters'] = array();
    }
    $method['parameters'] = array_merge($method['parameters'], $this->stackParameters);
    foreach ($parameters as $key => $val) {
      if ($key != 'postBody' && ! isset($method['parameters'][$key])) {
        throw new Google_Exception("($name) unknown parameter: '$key'");
      }
    }
    if (isset($method['parameters'])) {
      foreach ($method['parameters'] as $paramName => $paramSpec) {
        if (isset($paramSpec['required']) && $paramSpec['required'] && ! isset($parameters[$paramName])) {
          throw new Google_Exception("($name) missing required param: '$paramName'");
        }
        if (isset($parameters[$paramName])) {
          $value = $parameters[$paramName];
          $parameters[$paramName] = $paramSpec;
          $parameters[$paramName]['value'] = $value;
          unset($parameters[$paramName]['required']);
        } else {
          unset($parameters[$paramName]);
        }
      }
    }
    if (! isset($method['id'])) {
      $method['id'] = $method['rpcMethod'];
    }
    if (! isset($method['path'])) {
      $method['path'] = $method['restPath'];
    }
    $servicePath = $this->service->servicePath;
    $contentType = false;
    if (isset($method['mediaUpload'])) {
      $media = Google_MediaFileUpload::process($postBody, $parameters);
      if ($media) {
        $contentType = isset($media['content-type']) ? $media['content-type']: null;
        $postBody = isset($media['postBody']) ? $media['postBody'] : null;
        $servicePath = $method['mediaUpload']['protocols']['simple']['path'];
        $method['path'] = '';
      }
    }
    $url = Google_REST::createRequestUri($servicePath, $method['path'], $parameters);
    $httpRequest = new Google_HttpRequest($url, $method['httpMethod'], null, $postBody);
    if ($postBody) {
      $contentTypeHeader = array();
      if (isset($contentType) && $contentType) {
        $contentTypeHeader['content-type'] = $contentType;
      } else {
        $contentTypeHeader['content-type'] = 'application/json; charset=UTF-8';
        $contentTypeHeader['content-length'] = Google_Utils::getStrLen($postBody);
      }
      $httpRequest->setRequestHeaders($contentTypeHeader);
    }
    $httpRequest = Google_Client::$auth->sign($httpRequest);
    if (Google_Client::$useBatch) {
      return $httpRequest;
    }
    if (isset($parameters['uploadType']['value'])
        && Google_MediaFileUpload::UPLOAD_RESUMABLE_TYPE == $parameters['uploadType']['value']) {
      $contentTypeHeader = array();
      if (isset($contentType) && $contentType) {
        $contentTypeHeader['content-type'] = $contentType;
      }
      $httpRequest->setRequestHeaders($contentTypeHeader);
      if ($postBody) {
        $httpRequest->setPostBody($postBody);
      }
      return $httpRequest;
    }
    return Google_REST::execute($httpRequest);
  }
  public  function useObjects() {
    global $apiConfig;
    return (isset($apiConfig['use_objects']) && $apiConfig['use_objects']);
  }
  protected function stripNull(&$o) {
    $o = (array) $o;
    foreach ($o as $k => $v) {
      if ($v === null || strstr($k, "\0*\0__")) {
        unset($o[$k]);
      }
      elseif (is_object($v) || is_array($v)) {
        $this->stripNull($o[$k]);
      }
    }
  }
}


abstract class Google_Signer {
  abstract public function sign($data);
}


class Google_P12Signer extends Google_Signer {
  private $privateKey;
  function __construct($p12, $password) {
    if (!function_exists('openssl_x509_read')) {
      throw new Exception(
          'The Google PHP API library needs the openssl PHP extension');
    }
    $certs = array();
    if (!openssl_pkcs12_read($p12, $certs, $password)) {
      throw new Google_AuthException("Unable to parse the p12 file.  " .
          "Is this a .p12 file?  Is the password correct?  OpenSSL error: " .
          openssl_error_string());
    }
    if (!array_key_exists("pkey", $certs) || !$certs["pkey"]) {
      throw new Google_AuthException("No private key found in p12 file.");
    }
    $this->privateKey = openssl_pkey_get_private($certs["pkey"]);
    if (!$this->privateKey) {
      throw new Google_AuthException("Unable to load private key in ");
    }
  }
  function __destruct() {
    if ($this->privateKey) {
      openssl_pkey_free($this->privateKey);
    }
  }
  function sign($data) {
    if(version_compare(PHP_VERSION, '5.3.0') < 0) {
      throw new Google_AuthException(
        "PHP 5.3.0 or higher is required to use service accounts.");
    }
    if (!openssl_sign($data, $signature, $this->privateKey, "sha256")) {
      throw new Google_AuthException("Unable to sign data");
    }
    return $signature;
  }
}


class Google_AssertionCredentials {
  const MAX_TOKEN_LIFETIME_SECS = 3600;
  public $serviceAccountName;
  public $scopes;
  public $privateKey;
  public $privateKeyPassword;
  public $assertionType;
  public $sub;
  public $prn;
  public function __construct(
      $serviceAccountName,
      $scopes,
      $privateKey,
      $privateKeyPassword = 'notasecret',
      $assertionType = 'http://oauth.net/grant_type/jwt/1.0/bearer',
      $sub = false) {
    $this->serviceAccountName = $serviceAccountName;
    $this->scopes = is_string($scopes) ? $scopes : implode(' ', $scopes);
    $this->privateKey = $privateKey;
    $this->privateKeyPassword = $privateKeyPassword;
    $this->assertionType = $assertionType;
    $this->sub = $sub;
    $this->prn = $sub;
  }
  public function generateAssertion() {
    $now = time();
    $jwtParams = array(
          'aud' => Google_OAuth2::OAUTH2_TOKEN_URI,
          'scope' => $this->scopes,
          'iat' => $now,
          'exp' => $now + self::MAX_TOKEN_LIFETIME_SECS,
          'iss' => $this->serviceAccountName,
    );
    if ($this->sub !== false) {
      $jwtParams['sub'] = $this->sub;
    } else if ($this->prn !== false) {
      $jwtParams['prn'] = $this->prn;
    }
    return $this->makeSignedJwt($jwtParams);
  }
  private function makeSignedJwt($payload) {
    $header = array('typ' => 'JWT', 'alg' => 'RS256');
    $segments = array(
      Google_Utils::urlSafeB64Encode(json_encode($header)),
      Google_Utils::urlSafeB64Encode(json_encode($payload))
    );
    $signingInput = implode('.', $segments);
    $signer = new Google_P12Signer($this->privateKey, $this->privateKeyPassword);
    $signature = $signer->sign($signingInput);
    $segments[] = Google_Utils::urlSafeB64Encode($signature);
    return implode(".", $segments);
  }
}


class Google_BatchRequest {
  /** @var string Multipart Boundary. */
  private $boundary;
  /** @var array service requests to be executed. */
  private $requests = array();
  public function __construct($boundary = false) {
    $boundary = (false == $boundary) ? mt_rand() : $boundary;
    $this->boundary = str_replace('"', '', $boundary);
  }
  public function add(Google_HttpRequest $request, $key = false) {
    if (false == $key) {
      $key = mt_rand();
    }
    $this->requests[$key] = $request;
  }
  public function execute() {
    $body = '';
    /** @var Google_HttpRequest $req */
    foreach($this->requests as $key => $req) {
      $body .= "--{$this->boundary}\n";
      $body .= $req->toBatchString($key) . "\n";
    }
    $body = rtrim($body);
    $body .= "\n--{$this->boundary}--";
    global $apiConfig;
    $url = $apiConfig['basePath'] . '/batch';
    $httpRequest = new Google_HttpRequest($url, 'POST');
    $httpRequest->setRequestHeaders(array(
        'Content-Type' => 'multipart/mixed; boundary=' . $this->boundary));
    $httpRequest->setPostBody($body);
    $response = Google_Client::$io->makeRequest($httpRequest);
    $response = $this->parseResponse($response);
    return $response;
  }
  public function parseResponse(Google_HttpRequest $response) {
    $contentType = $response->getResponseHeader('content-type');
    $contentType = explode(';', $contentType);
    $boundary = false;
    foreach($contentType as $part) {
      $part = (explode('=', $part, 2));
      if (isset($part[0]) && 'boundary' == trim($part[0])) {
        $boundary = $part[1];
      }
    }
    $body = $response->getResponseBody();
    if ($body) {
      $body = str_replace("--$boundary--", "--$boundary", $body);
      $parts = explode("--$boundary", $body);
      $responses = array();
      foreach($parts as $part) {
        $part = trim($part);
        if (!empty($part)) {
          list($metaHeaders, $part) = explode("\r\n\r\n", $part, 2);
          $metaHeaders = Google_CurlIO::parseResponseHeaders($metaHeaders);
          $status = substr($part, 0, strpos($part, "\n"));
          $status = explode(" ", $status);
          $status = $status[1];
          list($partHeaders, $partBody) = Google_CurlIO::parseHttpResponse($part, false);
          $response = new Google_HttpRequest("");
          $response->setResponseHttpCode($status);
          $response->setResponseHeaders($partHeaders);
          $response->setResponseBody($partBody);
          $response = Google_REST::decodeHttpResponse($response);
          $responses[$metaHeaders['content-id']] = $response;
        }
      }
      return $responses;
    }
    return null;
  }
}


class URI_Template_Parser {
  public static $operators = array('+', ';', '?', '/', '.');
  public static $reserved_operators = array('|', '!', '@');
  public static $explode_modifiers = array('+', '*');
  public static $partial_modifiers = array(':', '^');
  public static $gen_delims = array(':', '/', '?', '#', '[', ']', '@');
  public static $gen_delims_pct = array('%3A', '%2F', '%3F', '%23', '%5B', '%5D', '%40');
  public static $sub_delims = array('!', '$', '&', '\'', '(', ')', '*', '+', ',', ';', '=');
  public static $sub_delims_pct = array('%21', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%3B', '%3D');
  public static $reserved;
  public static $reserved_pct;
  public function __construct($template) {
    self::$reserved = array_merge(self::$gen_delims, self::$sub_delims);
    self::$reserved_pct = array_merge(self::$gen_delims_pct, self::$sub_delims_pct);
    $this->template = $template;
  }
  public function expand($data) {
    if (! is_array($data)) {
      $data = (array)$data;
    }
    preg_match_all('/\{([^\}]*)\}/', $this->template, $em);
    foreach ($em[1] as $i => $bare_expression) {
      preg_match('/^([\+\;\?\/\.]{1})?(.*)$/', $bare_expression, $lm);
      $exp = new StdClass();
      $exp->expression = $em[0][$i];
      $exp->operator = $lm[1];
      $exp->variable_list = $lm[2];
      $exp->varspecs = explode(',', $exp->variable_list);
      $exp->vars = array();
      foreach ($exp->varspecs as $varspec) {
        preg_match('/^([a-zA-Z0-9_]+)([\*\+]{1})?([\:\^][0-9-]+)?(\=[^,]+)?$/', $varspec, $vm);
        $var = new StdClass();
        $var->name = $vm[1];
        $var->modifier = isset($vm[2]) && $vm[2] ? $vm[2] : null;
        $var->modifier = isset($vm[3]) && $vm[3] ? $vm[3] : $var->modifier;
        $var->default = isset($vm[4]) ? substr($vm[4], 1) : null;
        $exp->vars[] = $var;
      }
      $exp->reserved = false;
      $exp->prefix = '';
      $exp->delimiter = ',';
      switch ($exp->operator) {
        case '+':
          $exp->reserved = 'true';
          break;
        case ';':
          $exp->prefix = ';';
          $exp->delimiter = ';';
          break;
        case '?':
          $exp->prefix = '?';
          $exp->delimiter = '&';
          break;
        case '/':
          $exp->prefix = '/';
          $exp->delimiter = '/';
          break;
        case '.':
          $exp->prefix = '.';
          $exp->delimiter = '.';
          break;
      }
      $expressions[] = $exp;
    }
    $this->expansion = $this->template;
    foreach ($expressions as $exp) {
      $part = $exp->prefix;
      $exp->one_var_defined = false;
      foreach ($exp->vars as $var) {
        $val = '';
        if ($exp->one_var_defined && isset($data[$var->name])) {
          $part .= $exp->delimiter;
        }
        if (isset($data[$var->name])) {
          $exp->one_var_defined = true;
          $var->data = $data[$var->name];
          $val = self::val_from_var($var, $exp);
        } else {
          if ($var->default) {
            $exp->one_var_defined = true;
            $val = $var->default;
          }
        }
        $part .= $val;
      }
      if (! $exp->one_var_defined) $part = '';
      $this->expansion = str_replace($exp->expression, $part, $this->expansion);
    }
    return $this->expansion;
  }
  private function val_from_var($var, $exp) {
    $val = '';
    if (is_array($var->data)) {
      $i = 0;
      if ($exp->operator == '?' && ! $var->modifier) {
        $val .= $var->name . '=';
      }
      foreach ($var->data as $k => $v) {
        $del = $var->modifier ? $exp->delimiter : ',';
        $ek = rawurlencode($k);
        $ev = rawurlencode($v);
        if ($k !== $i) {
          if ($var->modifier == '+') {
            $val .= $var->name . '.';
          }
          if ($exp->operator == '?' && $var->modifier || $exp->operator == ';' && $var->modifier == '*' || $exp->operator == ';' && $var->modifier == '+') {
            $val .= $ek . '=';
          } else {
            $val .= $ek . $del;
          }
        } else {
          if ($var->modifier == '+') {
            if ($exp->operator == ';' && $var->modifier == '*' || $exp->operator == ';' && $var->modifier == '+' || $exp->operator == '?' && $var->modifier == '+') {
              $val .= $var->name . '=';
            } else {
              $val .= $var->name . '.';
            }
          }
        }
        $val .= $ev . $del;
        $i ++;
      }
      $val = trim($val, $del);
    } else {
      if ($exp->operator == '?') {
        $val = $var->name . (isset($var->data) ? '=' : '');
      } else if ($exp->operator == ';') {
        $val = $var->name . ($var->data ? '=' : '');
      }
      $val .= rawurlencode($var->data);
      if ($exp->operator == '+') {
        $val = str_replace(self::$reserved_pct, self::$reserved, $val);
      }
    }
    return $val;
  }
  public function match($uri) {}
  public function __toString() {
    return $this->template;
  }
}


class Google_MediaFileUpload {
  const UPLOAD_MEDIA_TYPE = 'media';
  const UPLOAD_MULTIPART_TYPE = 'multipart';
  const UPLOAD_RESUMABLE_TYPE = 'resumable';
  /** @var string $mimeType */
  public $mimeType;
  /** @var string $data */
  public $data;
  /** @var bool $resumable */
  public $resumable;
  /** @var int $chunkSize */
  public $chunkSize;
  /** @var int $size */
  public $size;
  /** @var string $resumeUri */
  public $resumeUri;
  /** @var int $progress */
  public $progress;
  public function __construct($mimeType, $data, $resumable=false, $chunkSize=false) {
    $this->mimeType = $mimeType;
    $this->data = $data;
    $this->size = strlen($this->data);
    $this->resumable = $resumable;
    if(!$chunkSize) {
      $chunkSize = 256 * 1024;
    }
    $this->chunkSize = $chunkSize;
    $this->progress = 0;
  }
  public function setFileSize($size) {
    $this->size = $size;
  }
  public static function process($meta, &$params) {
    $payload = array();
    $meta = is_string($meta) ? json_decode($meta, true) : $meta;
    $uploadType = self::getUploadType($meta, $payload, $params);
    if (!$uploadType) {
      return false;
    }
    $params['uploadType'] = array(
        'type' => 'string',
        'location' => 'query',
        'value' => $uploadType,
    );
    $mimeType = isset($params['mimeType'])
        ? $params['mimeType']['value']
        : false;
    unset($params['mimeType']);
    if (!$mimeType) {
      $mimeType = $payload['content-type'];
    }
    if (isset($params['file'])) {
      $file = $params['file']['value'];
      unset($params['file']);
      return self::processFileUpload($file, $mimeType);
    }
    $data = isset($params['data'])
        ? $params['data']['value']
        : false;
    unset($params['data']);
    if (self::UPLOAD_RESUMABLE_TYPE == $uploadType) {
      $payload['content-type'] = $mimeType;
      $payload['postBody'] = is_string($meta) ? $meta : json_encode($meta);
    } elseif (self::UPLOAD_MEDIA_TYPE == $uploadType) {
      $payload['content-type'] = $mimeType;
      $payload['postBody'] = $data;
    }
    elseif (self::UPLOAD_MULTIPART_TYPE == $uploadType) {
      $boundary = isset($params['boundary']['value']) ? $params['boundary']['value'] : mt_rand();
      $boundary = str_replace('"', '', $boundary);
      $payload['content-type'] = 'multipart/related; boundary=' . $boundary;
      $related = "--$boundary\r\n";
      $related .= "Content-Type: application/json; charset=UTF-8\r\n";
      $related .= "\r\n" . json_encode($meta) . "\r\n";
      $related .= "--$boundary\r\n";
      $related .= "Content-Type: $mimeType\r\n";
      $related .= "Content-Transfer-Encoding: base64\r\n";
      $related .= "\r\n" . base64_encode($data) . "\r\n";
      $related .= "--$boundary--";
      $payload['postBody'] = $related;
    }
    return $payload;
  }
  public static function processFileUpload($file, $mime) {
    if (!$file) return array();
    if (substr($file, 0, 1) != '@') {
      $file = '@' . $file;
    }
    $params = array('postBody' => array('file' => $file));
    if ($mime) {
      $params['content-type'] = $mime;
    }
    return $params;
  }
  public static function getUploadType($meta, &$payload, &$params) {
    if (isset($params['mediaUpload'])
        && get_class($params['mediaUpload']['value']) == 'Google_MediaFileUpload') {
      $upload = $params['mediaUpload']['value'];
      unset($params['mediaUpload']);
      $payload['content-type'] = $upload->mimeType;
      if (isset($upload->resumable) && $upload->resumable) {
        return self::UPLOAD_RESUMABLE_TYPE;
      }
    }
    if (isset($params['uploadType'])) {
      return $params['uploadType']['value'];
    }
    $data = isset($params['data']['value'])
        ? $params['data']['value'] : false;
    if (false == $data && false == isset($params['file'])) {
      return false;
    }
    if (isset($params['file'])) {
      return self::UPLOAD_MEDIA_TYPE;
    }
    if (false == $meta) {
      return self::UPLOAD_MEDIA_TYPE;
    }
    return self::UPLOAD_MULTIPART_TYPE;
  }
  public function nextChunk(Google_HttpRequest $req, $chunk=false) {
    if (false == $this->resumeUri) {
      $this->resumeUri = $this->getResumeUri($req);
    }
    if (false == $chunk) {
      $chunk = substr($this->data, $this->progress, $this->chunkSize);
    }
    $lastBytePos = $this->progress + strlen($chunk) - 1;
    $headers = array(
      'content-range' => "bytes $this->progress-$lastBytePos/$this->size",
      'content-type' => $req->getRequestHeader('content-type'),
      'content-length' => $this->chunkSize,
      'expect' => '',
    );
    $httpRequest = new Google_HttpRequest($this->resumeUri, 'PUT', $headers, $chunk);
    $response = Google_Client::$io->authenticatedRequest($httpRequest);
    $code = $response->getResponseHttpCode();
    if (308 == $code) {
      $range = explode('-', $response->getResponseHeader('range'));
      $this->progress = $range[1] + 1;
      return false;
    } else {
      return Google_REST::decodeHttpResponse($response);
    }
  }
  private function getResumeUri(Google_HttpRequest $httpRequest) {
    $result = null;
    $body = $httpRequest->getPostBody();
    if ($body) {
      $httpRequest->setRequestHeaders(array(
        'content-type' => 'application/json; charset=UTF-8',
        'content-length' => Google_Utils::getStrLen($body),
        'x-upload-content-type' => $this->mimeType,
        'x-upload-content-length' => $this->size,
        'expect' => '',
      ));
    }
    $response = Google_Client::$io->makeRequest($httpRequest);
    $location = $response->getResponseHeader('location');
    $code = $response->getResponseHttpCode();
    if (200 == $code && true == $location) {
      return $location;
    }
    throw new Google_Exception("Failed to start the resumable upload");
  }
}


class Google_Client {
  static $auth;
  static $io;
  static $cache;
  static $useBatch = false;
  /** @var array $scopes */
  protected $scopes = array();
  /** @var bool $useObjects */
  protected $useObjects = false;
  protected $services = array();
  private $authenticated = false;
  public function __construct($config = array()) {
    global $apiConfig;
    $apiConfig = array_merge($apiConfig, $config);
    self::$cache = new $apiConfig['cacheClass']();
    self::$auth = new $apiConfig['authClass']();
    self::$io = new $apiConfig['ioClass']();
  }
  public function addService($service, $version = false) {
    global $apiConfig;
    if ($this->authenticated) {
      throw new Google_Exception('Cant add services after having authenticated');
    }
    $this->services[$service] = array();
    if (isset($apiConfig['services'][$service])) {
      $this->services[$service] = array_merge($this->services[$service], $apiConfig['services'][$service]);
    }
  }
  public function authenticate($code = null) {
    $service = $this->prepareService();
    $this->authenticated = true;
    return self::$auth->authenticate($service, $code);
  }
  public function prepareService() {
    $service = array();
    $scopes = array();
    if ($this->scopes) {
      $scopes = $this->scopes;
    } else {
      foreach ($this->services as $key => $val) {
        if (isset($val['scope'])) {
          if (is_array($val['scope'])) {
            $scopes = array_merge($val['scope'], $scopes);
          } else {
            $scopes[] = $val['scope'];
          }
        } else {
          $scopes[] = 'https://www.googleapis.com/auth/' . $key;
        }
        unset($val['discoveryURI']);
        unset($val['scope']);
        $service = array_merge($service, $val);
      }
    }
    $service['scope'] = implode(' ', $scopes);
    return $service;
  }
  public function setAccessToken($accessToken) {
    if ($accessToken == null || 'null' == $accessToken) {
      $accessToken = null;
    }
    self::$auth->setAccessToken($accessToken);
  }
  public function setAuthClass($authClassName) {
    self::$auth = new $authClassName();
  }
  public function createAuthUrl() {
    $service = $this->prepareService();
    return self::$auth->createAuthUrl($service['scope']);
  }
  public function getAccessToken() {
    $token = self::$auth->getAccessToken();
    return (null == $token || 'null' == $token) ? null : $token;
  }
  public function isAccessTokenExpired() {
    return self::$auth->isAccessTokenExpired();
  }
  public function setDeveloperKey($developerKey) {
    self::$auth->setDeveloperKey($developerKey);
  }
  public function setState($state) {
    self::$auth->setState($state);
  }
  public function setAccessType($accessType) {
    self::$auth->setAccessType($accessType);
  }
  public function setApprovalPrompt($approvalPrompt) {
    self::$auth->setApprovalPrompt($approvalPrompt);
  }
  public function setApplicationName($applicationName) {
    global $apiConfig;
    $apiConfig['application_name'] = $applicationName;
  }
  public function setClientId($clientId) {
    global $apiConfig;
    $apiConfig['oauth2_client_id'] = $clientId;
    self::$auth->clientId = $clientId;
  }
  public function getClientId() {
    return self::$auth->clientId;
  }
  public function setClientSecret($clientSecret) {
    global $apiConfig;
    $apiConfig['oauth2_client_secret'] = $clientSecret;
    self::$auth->clientSecret = $clientSecret;
  }
  public function getClientSecret() {
    return self::$auth->clientSecret;
  }
  public function setRedirectUri($redirectUri) {
    global $apiConfig;
    $apiConfig['oauth2_redirect_uri'] = $redirectUri;
    self::$auth->redirectUri = $redirectUri;
  }
  public function getRedirectUri() {
    return self::$auth->redirectUri;
  }
  public function refreshToken($refreshToken) {
    self::$auth->refreshToken($refreshToken);
  }
  public function revokeToken($token = null) {
    self::$auth->revokeToken($token);
  }
  public function verifyIdToken($token = null) {
    return self::$auth->verifyIdToken($token);
  }
  public function setAssertionCredentials(Google_AssertionCredentials $creds) {
    self::$auth->setAssertionCredentials($creds);
  }
  public function setScopes($scopes) {
    $this->scopes = is_string($scopes) ? explode(" ", $scopes) : $scopes;
  }
  public function getScopes() {
     return $this->scopes;
  }
  public function setRequestVisibleActions($requestVisibleActions) {
    self::$auth->requestVisibleActions =
            join(" ", $requestVisibleActions);
  }
  public function setUseObjects($useObjects) {
    global $apiConfig;
    $apiConfig['use_objects'] = $useObjects;
  }
  public function setUseBatch($useBatch) {
    self::$useBatch = $useBatch;
  }
  public static function getAuth() {
    return Google_Client::$auth;
  }
  public static function getIo() {
    return Google_Client::$io;
  }
  public function getCache() {
    return Google_Client::$cache;
  }
}


class Google_Exception extends Exception {}
class Google_AuthException extends Google_Exception {}
class Google_CacheException extends Google_Exception {}
class Google_IOException extends Google_Exception {}
class Google_ServiceException extends Google_Exception {
  protected $errors = array();
  public function __construct($message, $code = 0, Exception $previous = null,
                              $errors = array()) {
    if(version_compare(PHP_VERSION, '5.3.0') >= 0) {
      parent::__construct($message, $code, $previous);
    } else {
      parent::__construct($message, $code);
    }
    $this->errors = $errors;
  }
  public function getErrors() {
    return $this->errors;
  }
}


class Google_UserinfoServiceResource extends Google_ServiceResource {
    public function get($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Userinfo($data);
      } else {
        return $data;
      }
    }
  }


class Google_UserinfoV2ServiceResource extends Google_ServiceResource { }


class Google_UserinfoV2MeServiceResource extends Google_ServiceResource {
    public function get($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Userinfo($data);
      } else {
        return $data;
      }
    }
}


class Google_Oauth2Service extends Google_Service {
  public $userinfo;
  public $userinfo_v2_me;
  public function __construct(Google_Client $client) {
    $this->servicePath = '';
    $this->version = 'v2';
    $this->serviceName = 'oauth2';
    $client->addService($this->serviceName, $this->version);
    $this->userinfo = new Google_UserinfoServiceResource($this, $this->serviceName, 'userinfo', json_decode('{"methods": {"get": {"id": "oauth2.userinfo.get", "path": "oauth2/v2/userinfo", "httpMethod": "GET", "response": {"$ref": "Userinfo"}, "scopes": ["https://www.googleapis.com/auth/plus.login", "https://www.googleapis.com/auth/plus.me", "https://www.googleapis.com/auth/userinfo.email", "https://www.googleapis.com/auth/userinfo.profile"]}}}', true));
    $this->userinfo_v2_me = new Google_UserinfoV2MeServiceResource($this, $this->serviceName, 'me', json_decode('{"methods": {"get": {"id": "oauth2.userinfo.v2.me.get", "path": "userinfo/v2/me", "httpMethod": "GET", "response": {"$ref": "Userinfo"}, "scopes": ["https://www.googleapis.com/auth/plus.login", "https://www.googleapis.com/auth/plus.me", "https://www.googleapis.com/auth/userinfo.email", "https://www.googleapis.com/auth/userinfo.profile"]}}}', true));
  }
}


class Google_Tokeninfo extends Google_Model {
  public $access_type;
  public $audience;
  public $email;
  public $expires_in;
  public $issued_to;
  public $scope;
  public $user_id;
  public $verified_email;
  public function setAccess_type( $access_type) {
    $this->access_type = $access_type;
  }
  public function getAccess_type() {
    return $this->access_type;
  }
  public function setAudience( $audience) {
    $this->audience = $audience;
  }
  public function getAudience() {
    return $this->audience;
  }
  public function setEmail( $email) {
    $this->email = $email;
  }
  public function getEmail() {
    return $this->email;
  }
  public function setExpires_in( $expires_in) {
    $this->expires_in = $expires_in;
  }
  public function getExpires_in() {
    return $this->expires_in;
  }
  public function setIssued_to( $issued_to) {
    $this->issued_to = $issued_to;
  }
  public function getIssued_to() {
    return $this->issued_to;
  }
  public function setScope( $scope) {
    $this->scope = $scope;
  }
  public function getScope() {
    return $this->scope;
  }
  public function setUser_id( $user_id) {
    $this->user_id = $user_id;
  }
  public function getUser_id() {
    return $this->user_id;
  }
  public function setVerified_email( $verified_email) {
    $this->verified_email = $verified_email;
  }
  public function getVerified_email() {
    return $this->verified_email;
  }
}


class Google_Userinfo extends Google_Model {
  public $birthday;
  public $email;
  public $family_name;
  public $gender;
  public $given_name;
  public $hd;
  public $id;
  public $link;
  public $locale;
  public $name;
  public $picture;
  public $timezone;
  public $verified_email;
  public function setBirthday( $birthday) {
    $this->birthday = $birthday;
  }
  public function getBirthday() {
    return $this->birthday;
  }
  public function setEmail( $email) {
    $this->email = $email;
  }
  public function getEmail() {
    return $this->email;
  }
  public function setFamily_name( $family_name) {
    $this->family_name = $family_name;
  }
  public function getFamily_name() {
    return $this->family_name;
  }
  public function setGender( $gender) {
    $this->gender = $gender;
  }
  public function getGender() {
    return $this->gender;
  }
  public function setGiven_name( $given_name) {
    $this->given_name = $given_name;
  }
  public function getGiven_name() {
    return $this->given_name;
  }
  public function setHd( $hd) {
    $this->hd = $hd;
  }
  public function getHd() {
    return $this->hd;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setLink( $link) {
    $this->link = $link;
  }
  public function getLink() {
    return $this->link;
  }
  public function setLocale( $locale) {
    $this->locale = $locale;
  }
  public function getLocale() {
    return $this->locale;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setPicture( $picture) {
    $this->picture = $picture;
  }
  public function getPicture() {
    return $this->picture;
  }
  public function setTimezone( $timezone) {
    $this->timezone = $timezone;
  }
  public function getTimezone() {
    return $this->timezone;
  }
  public function setVerified_email( $verified_email) {
    $this->verified_email = $verified_email;
  }
  public function getVerified_email() {
    return $this->verified_email;
  }
}