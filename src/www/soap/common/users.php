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
    'Returns the users that exist with their Codendi user name'
);

} else {
    
function checkUsersExistence($sessionKey, $users) {
    if (session_continue($sessionKey)){
        
        $existingUsers = array();
        
        $um = UserManager::instance();
        foreach ($users as $userIdentifier) {
            try {
                $userObj = $um->getUserByIdentifier($userIdentifier);
        	    if ($userObj !== null && ($userObj->isActive() || $userObj->isRestricted())) {
        	        $userInfo = array();
        	        $userInfo['identifier'] = $userIdentifier;
        	        $userInfo['username'] = $userObj->getUserName();
        	        $userInfo['id'] = $userObj->getId();
        	        $existingUsers[] = $userInfo;
        	    }
            } catch (Exception $e) {
                throw new SoapFault('0', $e->getMessage(), 'checkUsersExistence');        
            }
        }
        
        return $existingUsers;
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session ', 'checkUsersExistence');
    }
}


$server->addFunction(
        array(
            'checkUsersExistence',
            ));

}

?>
