<?php

require_once('session.php'); 

define('invalid_session_fault', '3001');

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

//
// Function implementation
//

/**
 * login : login the CodeX server
 * 
 * @param string $loginname the user name (login)
 * @param string $passwd the password associated with the loginname $loginname
 * @return array the SOAPSession if the loginname and the password are matching, a soap fault otherwise
 */
function login($loginname, $passwd) {
    list($success, $status) = session_login_valid($loginname,$passwd);
    if ($success) {
        $return = array(
            'user_id'  => session_get_userid(),
            'session_hash' => $GLOBALS['session_hash']
        );
        return new soapval('return', 'tns:Session',$return);
    } else {
        return new soap_fault(login_fault,'login','Unable to log with loginname of '.$loginname.' and password of '.$passwd, '');
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
        session_cookie('session_hash','');
    } else {
        return new soap_fault(invalid_session_fault, 'logout', 'Invalid Session','');
    }
}

?>
