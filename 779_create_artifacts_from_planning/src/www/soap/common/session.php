<?php

require_once('session.php');
require_once('common/include/CookieManager.class.php');
require_once('common/user/User_SOAPServer.class.php');
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
        'passwd'    => 'xsd:string'
    ),
    array('return'   => 'tns:Session'), // output parameters
    $uri, // namespace
    $uri.'#login', // soapaction
    'rpc', // style
    'encoded', // use
    'Login Tuleap Server with given login and password.
     Returns a soap fault if the login failed.' // documentation
);

$server->register('loginAs', // method name
    array('admin_session_hash' => 'xsd:string', // input parameters
          'loginname'    => 'xsd:string'
    ),
    array('return'   => 'xsd:string'), // output parameters
    $uri, // namespace
    $uri.'#loginAs', // soapaction
    'rpc', // style
    'encoded', // use
    'Login Tuleap Server with given admin_session_name and login.
     Returns a soap fault if the login failed.' // documentation
);

$server->register('retrieveSession',
    array('session_hash' => 'xsd:string'
    ),
    array('return'   => 'tns:Session'),
    $uri,
    $uri.'#retrieveSession',
    'rpc',
    'encoded',
    'Retrieve a valid session with a given session_hash and version.
     Returns a soap fault if the session is not valid.'
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
    'Logout the session identified by the given sessionKey From Codendi Server.
     Returns a soap fault if the sessionKey is not a valid session key.'
);

} else {
	
/**
 * login : login the Codendi server
 *
 * @global $Language
 *
 * @param string $loginname the user name (login)
 * @param string $passwd the password associated with the loginname $loginname
 * @return array the SOAPSession if the loginname and the password are matching and if the version of the API is matching, a soap fault otherwise
 */
function login($loginname, $passwd) {
    global $Language;
    
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
}

/**
 * loginAs: open session for another user
 *
 * @global $Language
 *
 * @param string $admin_session_hash
 * @param string $username the user name (login)
 * 
 * @return string the user session_hash 
 */
function loginAs($admin_session_hash, $username) {
    $server = new User_SOAPServer(UserManager::instance());
    return $server->loginAs($admin_session_hash, $username);
}

/**
 * retrieveSession : retrieve a valid Codendi session
 *
 * @global $Language
 *
 * @param string $session_hash the session hash that identify the session to retrieve
 * @return array the SOAPSession if the session_hash identify a valid session, or a soap fault otherwise
 */
function retrieveSession($session_hash) {
    global $Language;
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
 * logout : logout the Codendi server
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
            'getAPIVersion',
            'loginAs'
            ));
}

?>
