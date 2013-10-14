<?php

require_once('user.php');

if (defined('NUSOAP')) {
    
//
// Type definition
//

$GLOBALS['server']->wsdl->addComplexType(
    'UserInfo',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'identifier' => array('name'=>'identifier', 'type' => 'xsd:string'),
        'username' => array('name'=>'username', 'type' => 'xsd:string'),
        'id' => array('name'=>'id', 'type' => 'xsd:string'),
        'real_name' => array('name'=>'real_name', 'type' => 'xsd:string'),
        'email' => array('name'=>'email', 'type' => 'xsd:string'),
        'ldap_id' => array('name'=>'ldap_id', 'type' => 'xsd:string'),
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfUserInfo',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:UserInfo[]')),
    'tns:UserInfo'
);

//
// Function definition
//


$server->register(
    'checkUsersExistence',
    array('sessionKey'=>'xsd:string',
        'users'=>'tns:ArrayOfstring'
    ),
    array('return'=>'tns:ArrayOfUserInfo'),
    $uri,
    $uri.'#checkUsersExistence',
    'rpc',
    'encoded',
    'Returns the users that exist with their user name'
);

$server->register(
    'getUserInfo',
    array('sessionKey' =>'xsd:string',
          'user_id'    =>'xsd:int'
    ),
    array('return' => 'tns:UserInfo'),
    $uri,
    $uri.'#getUserInfo',
    'rpc',
    'encoded',
    'Returns the user info matching the given id'
);

} else {

function getUserInfo($sessionKey, $user_id) {
    if (! session_continue($sessionKey)) {
        return new SoapFault(invalid_session_fault, 'Invalid Session ', 'getUserInfo');
    }
    
    $user_manager = UserManager::instance();
    $current_user = $user_manager->getCurrentUser();
    
    try {
        $user      = $user_manager->getUserById($user_id);
        $user_info = user_to_soap($user_id, $user, $current_user);

        if (! $user_info) {
            return new SoapFault('0', "Invalid user id: $user_id", 'getUserInfo');
        }

        return $user_info;
    } catch (Exception $e) {
        return new SoapFault('0', $e->getMessage(), 'getUserInfo');
    }
}

function checkUsersExistence($sessionKey, $users) {
    if (session_continue($sessionKey)){
        try {
            $existingUsers = array();
            $um            = UserManager::instance();
            $currentUser   = $um->getCurrentUser();

            foreach ($users as $userIdentifier) {
                $userObj  = $um->getUserByIdentifier($userIdentifier);
                $userInfo = user_to_soap($userIdentifier, $userObj, $currentUser);

                if ($userInfo) {
                    $existingUsers[] = $userInfo;
                }
            }

            return $existingUsers;
        } catch (Exception $e) {
            return new SoapFault('0', $e->getMessage(), 'checkUsersExistence');        
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session ', 'checkUsersExistence');
    }
}


$server->addFunction(
        array(
            'getUserInfo',
            'checkUsersExistence',
            ));

}

?>
