<?php

require_once('session.php'); 
require_once('common/include/CookieManager.class.php');

define('invalid_session_fault', '3001');
define('login_fault', '3002');

if (defined('NUSOAP')) {
	
//
// Type definition
//
$server->wsdl->addComplexType(
    'Session',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'user_id' => array('name' => 'user_id', 'type' => 'xsd:int'),
        'session_hash' => array('name' => 'session_hash', 'type' => 'xsd:string')
    )
);

//
// Functions definition
//
$server->register('login', // method name
    array('loginname' => 'xsd:string', // input parameters
        'passwd'    => 'xsd:string',
    	'version'	=> 'xsd:string'
    ),
    array('return'   => 'tns:Session'), // output parameters
    $uri, // namespace
    $uri.'#login', // soapaction
    'rpc', // style
    'encoded', // use
    'Login CodeX Server with given login, password and version.
     Returns a soap fault if the login failed, or if the version mismatch.' // documentation
);

$server->register('retrieveSession',
    array('session_hash' => 'xsd:string',
    	  'version'		 => 'xsd:string'
    ),
    array('return'   => 'tns:Session'),
    $uri,
    $uri.'#retrieveSession',
    'rpc',
    'encoded',
    'Retrieve a valid session with a given session_hash and version.
     Returns a soap fault if the session is not valid or if the version mismatch.'
);

$server->register('getAPIVersion',
    array(),
    array('return' => 'xsd:string'),
    $uri,
    $uri.'#getAPIVersion',
    'rpc',
    'encoded',
    'Returns the current version of this Web Service API.'
);

$server->register('logout',
    array('sessionKey' => 'xsd:string'),
    array(),
    $uri,
    $uri.'#logout',
    'rpc',
    'encoded',
    'Logout the session identified by the given sessionKey From CodeX Server.
     Returns a soap fault if the sessionKey is not a valid session key.'
);

} else {
	
/**
 * login : login the CodeX server
 *
 * @global $Language
 *
 * @param string $loginname the user name (login)
 * @param string $passwd the password associated with the loginname $loginname
 * @param string $version the version of the API we want to login
 * @return array the SOAPSession if the loginname and the password are matching and if the version of the API is matching, a soap fault otherwise
 */
function login($loginname, $passwd, $version) {
    global $Language;
    
    if (isCompatible($version)) {
        $user = UserManager::instance()->login($loginname, $passwd);
	    if ($user->isLoggedIn()) {
	        $return = array(
	            'user_id'      => $user->getId(),
	            'session_hash' => $user->getSessionHash()
	        );
	        return $return;
	    } else {
	        return new SoapFault(login_fault, $loginname.' : '.$Language->getText('include_session', 'invalid_pwd'), 'login');
	    }
    } else {
        return new SoapFault(login_fault, 'Version of Web Service API mismatch: given '.$version.' while expected '.constant("CODENDI_WS_API_VERSION"), 'login');
    }
}

/**
 * retrieveSession : retrieve a valid CodeX session
 *
 * @global $Language
 *
 * @param string $session_hash the session hash that identify the session to retrieve
 * @param string $version the version of the API we want to login
 * @return array the SOAPSession if the session_hash identify a valid session, or a soap fault otherwise
 */
function retrieveSession($session_hash, $version) {
    global $Language;
    if (isCompatible($version)) {
	    if (session_continue($session_hash)) {
            $user = UserManager::instance()->getCurrentUser();
	        $return = array(
	            'user_id'      => $user->getId(),
	            'session_hash' => $user->getSessionHash()
	        );
	        return $return;
	    } else {
	        return new SoapFault(invalid_session_fault, 'Invalid Session.', 'retrieveSession');
	    }
    } else {
        return new SoapFault(login_fault, 'Version of Web Service API mismatch: given '.$version.' while expected '.constant("CODENDI_WS_API_VERSION"), 'login');
    }
}

/**
 * getAPIVersion
 *
 * Returns the current version of this API (to enable clients to check compatibility with). 
 * 
 * @return string the version of this Codendi WS API
 */
function getAPIVersion() {
    return constant('CODENDI_WS_API_VERSION');
}

/**
 * Returns yes if the client version is compatible with this WS API version
 * 
 * Note: for now, compatible means equal, but we can imagine more complex rules (3.6 compatible with 3.6.1)
 * 
 * @param string $client_version the API version the client want to connect to
 * @return boolean true if $client_version is compatible with this API version, false otherwise 
 */
function isCompatible($client_version) {
    return constant('CODENDI_WS_API_VERSION') === $client_version;
}

/**
 * logout : logout the CodeX server
 * 
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 */
function logout($sessionKey) {
    global $session_hash;
    if (session_continue($sessionKey)){
        UserManager::instance()->logout();
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'logout');
    }
}

$server->addFunction(
        array(
            'login',
            'retrieveSession',
            'logout',
            'getAPIVersion'
            ));


}

?>
