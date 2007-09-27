<?php

require_once('common/include/User.class.php');

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

$server->register('updateUser',			       // method name
    array('sessionKey' => 'xsd:string',		       // input parameters	      
        'user'       => 'tns:User'
    ),                                   
    array(),					       // output parameters
    $uri,			       // namespace
    $uri.'#updateUser',   		       // soapaction
    'rpc',					       // style
    'encoded',					       // use
    'update User'				  	       // documentation
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

function updateUser($sessionKey, $user) {
    if (session_continue($sessionKey)){
        $res_user = db_query("SELECT * FROM user WHERE user_id ='" . $user['user_id']."'");
        $row_user = db_fetch_array($res_user);
        if (!$res_user || db_numrows($res_user) < 1) {
            return new soap_fault(invalid_user_fault,'updateUser','Internal error: Cannot locate user in database.','');
        } else {
            $bool = false;
            $sql = "UPDATE user SET";
            if ($user['user_name'] != $row_user['user_name'])	{
                $sql .= " user_name = '".$user['user_name']."'"; 
                $bool = true;
            }
            if ($user['add_date'] != $row_user['add_date']) {
                $sql .= " add_date = '".$user['add_date']."'";
                $bool = true;
            }
            if ($user['timezone'] != $row_user['timezone']) {
                $sql .= " timezone = '".$user['timezone']."'";
                $bool = true;
            }
            if ($user['email'] != $row_user['email']) {
                $sql .= " email = '".$user['email']."'";
                $bool = true;
            }
            /*
            if ($user['mail_siteupdates'] != $row_user['mail_siteupdates']) { 
                $sql .= " mail_siteupdates = '".$user['mail_siteupdates']."'";
                $bool = true;
            }
            if ($user['mail_va'] != $row_user['mail_va']) {
                $sql .= " mail_va = ".$user['mail_va'];
                $bool = true;
            }
            if ($user['sticky_login'] != $row_user['sticky_login']) { 
                $sql .= " sticky_login = ".$user['sticky_login'];
                $bool = true;
            }
            if ($user['fontsize'] != $row_user['fontsize']) { 
                $sql .= " fontsize = ".$user['fontsize'];
                $bool = true;
            }
            if ($user['theme'] != $row_user['theme'])	{ 
                $sql .= " theme = '".$user['theme']."'";
                $bool = true;
            }
            if ($user['unix_box'] != $row_user['unix_box']) {
                $sql .= " unix_box = '".$user['unix_box']."'";
                $bool = true;
            }
            if ($user['authorized_keys'] != $row_user['authorized_keys']) {
                $sql .= " user_name = '".$user['user_name']."'";
                $bool = true;
            }
            if ($user['user_pw'] != $row_user['user_pw']) {
                updateUserPassword($user['user_id'], $row_user['user_pw'], $user['user_pw']);
                exit;
            }
             */
            if ($user['unix_status'] != $row_user['unix_status']) {
                $sql .= " unix_status = '".$user['unix_status']."'";
                $bool = true;
            }
            if ($user['status'] != $row_user['status']) { 
                $sql .= " status = '".$user['status']."'";
                $bool = true;
            }
            if ($user['people_resume'] != $row_user['people_resume']) { 
                $sql .= " people_resume = '".$user['people_resume']."'";
                $bool = true;
            }
            if ($user['people_view_skills'] != $row_user['people_view_skills']) { 
                $sql .= " people_view_skills = ".$user['people_view_skills'];
                $bool = true;
            }
            if ($user['language_id'] != $row_user['language_id']) {
                $sql .= " language_id = ".$user['language_id'];
                $bool = true;
            }
            if ($user['realname'] != $row_user['realname']) { 
                $sql .= " realname = '".$user['realname']."'";
                $bool = true;
            }
            $sql .= " WHERE user_id =" . $user['user_id'];
            if ($bool) {
                $res = db_query($sql);
                if (! $res) {
                    //return new soap_fault(update_user_fault,'updateUser','Internal error: Could not update User.', db_error()); 
                    return new soap_fault(update_user_fault,'updateUser','Internal error: Could not update User.', '');     
                }
            }
        }
    } else {
        return new soap_fault(invalid_session_fault,'updateUser','Invalid Session ','');
    }
}

?>
