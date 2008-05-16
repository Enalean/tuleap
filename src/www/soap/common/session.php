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
        'passwd'    => 'xsd:string'
    ),
    array('return'   => 'tns:Session'), // output parameters
    $uri, // namespace
    $uri.'#login', // soapaction
    'rpc', // style
    'encoded', // use
    'Login CodeX Server with given login and password.
     Returns a soap fault if the login failed.' // documentation
);

$server->register('retrieveSession',
    array('session_hash' => 'xsd:string'),
    array('return'   => 'tns:Session'),
    $uri,
    $uri.'#retrieveSession',
    'rpc',
    'encoded',
    'Retrieve a valid session with a given session_hash.
     Returns a soap fault if the session is not valid.'
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
 * @return array the SOAPSession if the loginname and the password are matching, a soap fault otherwise
 */
function login($loginname, $passwd) {
    global $Language;
    
    list($success, $status) = session_login_valid($loginname,$passwd);
    if ($success) {
        $return = array(
            'user_id'  => session_get_userid(),
            'session_hash' => $GLOBALS['session_hash']
        );
        return $return;
    } else {
        return new SoapFault(login_fault, $loginname.' : '.$Language->getText('include_session', 'invalid_pwd'), 'login');
    }
}

/**
 * retrieveSession : retrieve a valid CodeX session
 *
 * @global $Language
 *
 * @param string $session_hash the session hash that identify the session to retrieve
 * @return array the SOAPSession if the session_hash identify a valid session, or a soap fault otherwise
 */
function retrieveSession($session_hash) {
    global $Language;
    if (session_continue($session_hash)) {
        $return = array(
            'user_id'  => session_get_userid(),
            'session_hash' => $session_hash
        );
        return $return;
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session.', 'retrieveSession');
    }
}


/**
 * logout : logout the CodeX server
 * 
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 */
function logout($sessionKey) {
    global $session_hash;
    if (session_continue($sessionKey)){
        if (isset($session_hash)) {
            session_delete($session_hash);
        }
        $cookie_manager =& new CookieManager();
        $cookie_manager->removeCookie('session_hash');
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'logout');
    }
}

$server->addFunction(
        array(
            'login',
            'retrieveSession',
            'logout'
            ));


}

?>