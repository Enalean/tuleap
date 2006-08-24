<?php

require_once('common/include/User.class');

//
// Type definition
//
$server->wsdl->addComplexType(
    'User',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'user_id' => array('name' => 'user_id', 'type' => 'xsd:int'),
        'user_name' => array('name' => 'user_name', 'type' => 'xsd:string'),
        'realname' => array('name' => 'realname', 'type' => 'xsd:string'),
        'email' => array('name' => 'email', 'type' => 'xsd:string'),
        'status' => array('name' => 'status', 'type' => 'xsd:string'),
        'unix_status' => array('name' => 'unix_status', 'type' => 'xsd:string'),
        'add_date' => array('name' => 'add_date', 'type' => 'xsd:int'),
        'timezone' => array('name' => 'timezone', 'type' => 'xsd:string'),
        'people_resume' => array('name' => 'people_resume', 'type' => 'xsd:string'),
        'people_view_skills' => array('name' => 'people_view_skills', 'type' => 'xsd:int'),
        'language_id' => array('name' => 'language_id', 'type' => 'xsd:int')
    )
);

$server->wsdl->addComplexType(
    'ArrayOfUser',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:User[]')
    ),
    'tns:User'
);

//
// Function definition
//
$server->register('getUserById', // method name
    array('sessionKey' => 'xsd:string', // input parameters
        'user_id' => 'xsd:int'
    ),		    
    array('return'   => 'tns:User'), // output parameters
    $uri, // namespace
    $uri.'#getUserById', // soapaction
    'rpc', // style
    'encoded', // use
    'Returns the User associated with the given Id.
     Returns a soap fault if the session is not valid, or if the user id does not match a valid user.' // documentation
);

//
// Function implementation
//

/**
 * returns a soap User object corresponding to the CodeX User object
 *
 * @param Object{User} $user the user we want to convert in soap
 * @return array the soap user object
 */
function user_to_soap($user) {
    $soap_user = array(
        'user_id' => $user->getID(),
        'user_name' => $user->getName(),
        'realname' => $user->getRealName(),
        'email' => $user->getEmail(),
        'status' => $user->getStatus(),
        'unix_status' => $user->getUnixStatus(),
        'add_date' => $user->getAddDate(),
        'timezone' => $user->getTimezone(),
        'people_resume' => $user->getPeopleResume(),
        'people_view_skills' => $user->getPeopleViewSkills(),
        'language_id' => $user->getLanguageID()
    );
    return $soap_user;
}

/**
 * getUserById : returns the SOAPUser associated with the given ID
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $uid the ID of the user we want to get
 * @return array the SOAPUser associated with the given ID, or a soap fault if the user ID does not match with a valid user.
 */
function getUserById($sessionKey, $uid) {
    if (session_continue($sessionKey)){
        $user = new User($uid);
        if ($user) {
            $soap_user = user_to_soap($user);
            return new soapval('return', 'tns:User', $soap_user);
        } else {
            return new soap_fault('Server','CodeXAccountwsdl','Could not get User by Id',db_error());
        }
    } else {
        return new soap_fault(get_user_fault,'getUserById','Invalid Session ','');
    }
}

?>
