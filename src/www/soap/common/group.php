<?php

require_once('user.php');
require_once('common/include/GroupFactory.class.php');

//
// Type definition
//
$server->wsdl->addComplexType(
    'Group',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'group_id' => array('name'=>'group_id', 'type'=>'xsd:int'), 
        'group_name' => array('name'=>'group_name', 'type'=>'xsd:string'),
        'unix_group_name' => array('name'=>'unix_group_name', 'type'=>'xsd:string'),
        'description' => array('name'=>'description', 'type'=>'xsd:string')
    )
);
    
$server->wsdl->addComplexType(
    'ArrayOfGroup',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Group[]')
    ),
    'tns:Group'
);

//
// Function definition
//

$server->register('getMyProjects',		       // method name
    array('sessionKey' => 'xsd:string'		       // input parameters	      
    ),                                   
    array('return'   => 'tns:ArrayOfGroup'),	       // output parameters
    $uri,			       // namespace
    $uri.'#getMyProjects',        // soapaction
    'rpc',					       // style
    'encoded',					       // use
    'Returns the list of Groups that the current user belong to'  	       // documentation
);

$server->register(
    'getGroupByName',
    array('sessionKey'=>'xsd:string',
        'unix_group_name'=>'xsd:string'),
    array('return'=>'tns:ArrayOfGroup'),
    $uri,
    $uri.'#getGroupByName',
    'rpc',
    'encoded',
    'Returns a Group object matching with the given unix_group_name, or a soap fault if the name does not match with a valid project.'
);

$server->register(
    'getGroupById',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int'
    ),
    array('return'=>'tns:Group'),
    $uri,
    $uri.'#getGroupById',
    'rpc',
    'encoded',
    'Returns the Group object associated with the given ID, or a soap fault if the ID does not match with a valid project.'
);

//
// Function implementation
//

/**
 * Returns a soap Group object corresponding to the CodeX Group object
 *
 * @param Object{Group} $group the group we want to convert in soap
 * @return array the soap group object
 */
function group_to_soap($group) {
    $soap_group = array(
        'group_id' => $group->getGroupId(),
        'group_name' => $group->getPublicName(),
        'unix_group_name' => $group->getUnixName(),
        'description' => $group->getDescription()
    );
    return $soap_group;
}

function groups_to_soap($groups) {
    $return = array();
    foreach ($groups as $group_id => $group) {
        if (!$group || $group->isError()) {
            //skip if error
        } else {
            $return[] = group_to_soap($group);	
        }
    }
    return $return;
}

/**
 * getMyProjects : returns the array of SOAPGroup the current user is member of
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @return array the array of SOAPGroup th ecurrent user ismember of
 */
function getMyProjects($sessionKey) {
    if (session_continue($sessionKey)){
        $gf = new GroupFactory();
        $my_groups = $gf->getMyGroups();
        return groups_to_soap($my_groups);
    } else {
        return new soap_fault(invalid_session_fault,'getMyProjects','Invalid Session ','');
    }
}

/**
 * getGroupByName : returns the SOAPGroup associated with the given unix group name
 *
 * @global $Language
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param string $unix_group_name the unix name of the group we want to get
 * @return array the SOAPGroup associated with the given unix name
 */
function getGroupByName($sessionKey, $unix_group_name) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group = group_get_object_by_name($unix_group_name);  // function located in www/include/Group.class.php
        if (! $group || !is_object($group)) {
            return new soap_fault('2002','getGroupByName', $unix_group_name.' : '.$Language->getText('include_group', 'g_not_found'),$Language->getText('include_group', 'g_not_found'));
        } elseif ($group->isError()) {
            return new soap_fault('2002', 'getGroupByName', $group->getErrorMessage(),$group->getErrorMessage());
        }
        $soap_group = group_to_soap($group);
        return new soapval('return', 'tns:Group', $soap_group);
    } else {
        return new soap_fault(invalid_session_fault,'getGroupByName','Invalid Session','');
    }
}

/**
 * getGroupById : returns the SOAPGroup associated with the given ID
 *
 * @global $Language
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param string $group_id the ID of the group we want to get
 * @return array the SOAPGroup associated with the given ID
 */
function getGroupById($sessionKey, $group_id) {
    if (session_continue($sessionKey)) {
        $group = new Group($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault('2001','getGroupById', $group_id.' : '.$Language->getText('include_group', 'g_not_found'),$Language->getText('include_group', 'g_not_found'));
        } elseif ($group->isError()) {
            return new soap_fault('2001', 'getGroupById', $group->getErrorMessage(),$group->getErrorMessage());
        }
        
        $soap_group = group_to_soap($group);
        return new soapval('return', 'tns:Group', $soap_group);
    } else {
        return new soap_fault(invalid_session_fault,'getGroup','Invalid Session','');
    }
}


/**
 * Check if the user can access the project $group,
 * regarding the restricted access
 *
 * @param Object{Group} $group the Group object
 * @return boolean true if the current session user has access to this project, false otherwise
 */
function checkRestrictedAccess($group) {
    if (array_key_exists('sys_allow_restricted_users', $GLOBALS) && $GLOBALS['sys_allow_restricted_users']) {
        if ($group) {
            $user = new User(session_get_userid());
            if ($user) {
                if ($user->isRestricted()) {
                    return $group->userIsMember();
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return true;
    }
}

?>
