<?php

require_once('user.php');
require_once('common/include/GroupFactory.class.php');

if (defined('NUSOAP')) {
	
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

$server->wsdl->addComplexType(
    'UGroupMember',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'user_id' => array('name'=>'user_id', 'type'=>'xsd:int'), 
        'user_name' => array('name'=>'user_name', 'type'=>'xsd:string'),
    )
);

$server->wsdl->addComplexType(
    'ArrayOfUGroupMember',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:UGroupMember[]')
    ),
    'tns:UGroupMember'
);

$GLOBALS['server']->wsdl->addComplexType(
    'Ugroup',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'ugroup_id' => array('name'=>'ugroup_id', 'type' => 'xsd:int'),
        'name' => array('name'=>'name', 'type' => 'xsd:string'),
        'members' => array('name'=>'members', 'type' => 'tns:ArrayOfUGroupMember'),
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfUgroup',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Ugroup[]')),
    'tns:Ugroup'
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
    array('return'=>'tns:Group'),
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


$server->register(
    'getGroupUgroups',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int'
    ),
    array('return'=>'tns:ArrayOfUgroup'),
    $uri,
    $uri.'#getGroupUgroups',
    'rpc',
    'encoded',
    'Returns the Ugroups associated to the given project.'
);

} else {
	

/**
 * Returns a soap Group object corresponding to the Codendi Group object
 *
 * @param Object{Group} $group the group we want to convert in soap
 * @return array the soap group object
 */
function group_to_soap($group) {
	$soap_group = array(
        'group_id' => $group->getGroupId(),
        'group_name' => util_unconvert_htmlspecialchars($group->getPublicName()),
        'unix_group_name' => $group->getUnixName(),
        'description' => util_unconvert_htmlspecialchars($group->getDescription())
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
        return new SoapFault(invalid_session_fault, 'Invalid Session ', 'getMyProjects');
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
        $group = group_get_object_by_name($unix_group_name);  // function located in common/project/Group.class.php
        if (! $group || !is_object($group)) {
            return new SoapFault('2002', $unix_group_name.' : '.$Language->getText('include_group', 'g_not_found'), 'getGroupByName');
        } elseif ($group->isError()) {
            return new SoapFault('2002', $group->getErrorMessage(), 'getGroupByName', '');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault, 'Restricted user: permission denied.', 'getGroupByName');
        }
        
        $soap_group = group_to_soap($group);
        return new SoapVar($soap_group, SOAP_ENC_OBJECT);
    } else {
        return new SoapFault(invalid_session_fault,'Invalid Session','getGroupByName');
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
        $pm = ProjectManager::instance();
        $group = $pm->getProject($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault('2001', $group_id.' : '.$GLOBALS['Language']->getText('include_group', 'g_not_found'),'getGroupById');
        } elseif ($group->isError()) {
            return new SoapFault('2001', $group->getErrorMessage(),'getGroupById');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault, 'Restricted user: permission denied.', 'getGroupById');
        }
        
        $soap_group = group_to_soap($group);
        return $soap_group;
    } else {
        return new SoapFault(invalid_session_fault,'Invalid Session','getGroup');
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
    if (array_key_exists('sys_allow_restricted_users', $GLOBALS) && $GLOBALS['sys_allow_restricted_users'] == 1) {
        if ($group) {
            $user = UserManager::instance()->getCurrentUser();
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

/**
 * Returns true is the current user is a member of the given group
 */
function checkGroupMemberAccess($group) {
    if ($group) {
        $user = UserManager::instance()->getCurrentUser();
        if ($user) {
            return $group->userIsMember();
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function ugroups_to_soap($ugroups) {
    $return = array();
    
    foreach ($ugroups as $ugroup) {
        $ugroup_id = $ugroup['ugroup_id'];
        if (!isset($return[$ugroup_id])) {
            $return[$ugroup_id]['ugroup_id'] = $ugroup_id;
            $return[$ugroup_id]['name'] = $ugroup['name'];
            $return[$ugroup_id]['members'] = array();
        }
        
        if ($ugroup['user_id']) {
            $return[$ugroup_id]['members'][] = array('user_id' => $ugroup['user_id'],
                                                     'user_name' => $ugroup['user_name']);
        }
    }
    
    return $return;
}

/**
 * Returns the Ugroups associated to the given project
 * This function can only be called by members of the group 
 */
function getGroupUgroups($sessionKey, $group_id) {
   global $Language;
    if (session_continue($sessionKey)) {
        $pm = ProjectManager::instance();
        $group = $pm->getProject($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', 'getGroupUgroups');
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(),  'getGroupUgroups');
        }
        if (!checkGroupMemberAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.',  'getGroupUgroups');
        }
        
        $ugroups = ugroup_get_ugroups_with_members($group_id);
        $return = ugroups_to_soap($ugroups);
        
        return $return;
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'getGroupUgroups');
    }
}

$server->addFunction(
    	array(
            'getMyProjects',
            'getGroupByName',
            'getGroupById',
    	    'getGroupUgroups',
            ));

}

?>
