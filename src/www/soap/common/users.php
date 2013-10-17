<?php

require_once('user.php');

if (defined('NUSOAP')) {

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
