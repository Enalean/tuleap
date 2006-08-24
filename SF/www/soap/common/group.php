<?php

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
        'description' => array('name'=>'description', 'type'=>'xsd:string'),
        'admin_flags' => array('name'=>'admin_flags', 'type'=>'xsd:string'),
        'group_admins' => array('name'=>'group_admins', 'type'=>'tns:ArrayOfUser')
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
$server->register('getListOfGroupsByUser',		       // method name
    array('sessionKey' => 'xsd:string',		       // input parameters	      
        'user_id' => 'xsd:int'
    ),                                   
    array('return'   => 'tns:ArrayOfGroup'),	       // output parameters
    $uri,			       // namespace
    $uri.'#getListOfGroupsByUser',        // soapaction
    'rpc',					       // style
    'encoded',					       // use
    'Returns the list of Groups that the user identified by user_id belong to'  	       // documentation
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

$server->register(
    'getGroupAdmins',
    array('sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int'),
    array('return'=>'tns:ArrayOfUser'),
    $uri,
    $uri.'#getGroupAdmins',
    'rpc',
    'encoded',
    'Returns an array of User that are admin of the Group of ID group_id'
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

/*
function getListOfGroupsByUser($sessionKey, $user_id) {
    if (session_continue($sessionKey)){
        $LIST_GROUP = array();
        $res_group = db_query("SELECT groups.group_name, "
                            . "groups.short_description, "
                            . "groups.unix_group_name, "
                            . "groups.group_id, "
                            . "groups.hide_members, "
                            . "user_group.admin_flags, "
                            . "user_group.bug_flags "
                            . "FROM groups,user_group "
                            . "WHERE user_group.user_id='$user_id' AND "
                            . "groups.group_id=user_group.group_id AND "
                            . "groups.is_public='1' AND "
                            . "groups.status='A' AND "
                            . "groups.type='1'");
        if (!$res_group || db_numrows($res_group) < 1) {
            return new soap_fault(get_groups_fault,'getListOfGroupsByUser', 'This developer is not a member of any projects.',db_error());
        } else {
            while ($row_group = db_fetch_array($res_group)) {
                $LIST_GROUP[] = row_group_to_soap($sessionKey, $row_group);
            }
            return new soapval('return', 'tns:ArrayOfGroup', $LIST_GROUP);	
        }
    } else {
        return new soap_fault(invalid_session_fault,'getListOfGroupsByUser','Invalid Session ','');
    }
}*/

/**
 * getGroupByName : returns the SOAPGroup associated with the given unix group name
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param string $unix_group_name the unix name of the group we want to get
 * @return array the SOAPGroup associated with the given unix name
 */
function getGroupByName($sessionKey, $unix_group_name) {
    if (session_continue($sessionKey)) {
        $group = group_get_object_by_name($unix_group_name);  // function located in www/include/Group.class
        if (! $group) {
            return new soap_fault('2002','getGroupByName','Could Not Get Groups by Name','Could Not Get Groups by Name');
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
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param string $group_id the ID of the group we want to get
 * @return array the SOAPGroup associated with the given ID
 */
function getGroupById($sessionKey, $group_id) {
    if (session_continue($sessionKey)) {
        $group = new Group($group_id);
        if (! $group) {
            return new soap_fault('2001','getGroupById','Could Not Get Group by Id');
        }
        $soap_group = group_to_soap($group);
        return new soapval('return', 'tns:Group', $soap_group);
    } else {
        return new soap_fault(invalid_session_fault,'getGroup','Invalid Session','');
    }
}

/*
function getGroupAdmins($sessionKey, $group_id) {
    if (session_continue($sessionKey)) {
        $group = group_get_object_by_name($unix_group_name);  // function located in www/include/Group.class
        if (! $group) {
            return new soap_fault('2002','getGroupAdmins','Could Not Get Groups by group_id','Could Not Get Groups by group_id');
        }
        if ($group->hideMembers()) {
            return new soap_fault('2002','getGroupAdmins','Could Not Get Groups Members','Could Not Get Groups Members');
        }
        $admins = $group->getAdmins();
        $row_admins = array();
        foreach ($admins as $admin) {
            $row_admin = user_to_soap($admin);
            $row_admins[] = $row_admin;
        }
        return new soapval('return', 'tns:arrayOfUser', $row_admins);
    } else {
        return new soap_fault(invalid_session_fault,'logout','Invalid Session','');
    }
}*/

?>
