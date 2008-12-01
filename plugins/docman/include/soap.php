<?php

require_once ('pre.php');
require_once ('session.php');
require_once('common/include/Error.class.php');
require_once('Docman_Item.class.php');
require_once('Docman_ItemFactory.class.php');
require_once('common/include/SOAPRequest.class.php');

// define fault code constants
define('invalid_item_fault', '3017');
define('invalid_document_fault', '3018');
define('invalid_folder_fault', '3019');
define('PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN', '3020');

if (defined('NUSOAP')) {

//
// Type definition
//
$GLOBALS['server']->wsdl->addComplexType(
    'Docman_Item',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'item_id' => array('name'=>'item_id', 'type' => 'xsd:int'),
        'parent_id' => array('name'=>'parent', 'type' => 'xsd:int'), 
        'group_id' => array('name'=>'group_id', 'type' => 'xsd:int'),
        'title' => array('name'=>'title', 'type' => 'xsd:string'),
        'description' => array('name'=>'description', 'type' => 'xsd:string'),
        'create_date' => array('name'=>'create_date', 'type' => 'xsd:int'),
        'update_date' => array('name'=>'update_date', 'type' => 'xsd:int'),
        'delete_date' => array('name'=>'delete_date', 'type' => 'xsd:int'),
        'user_id' => array('name'=>'user_id', 'type'=>'xsd:int'),
        'status' => array('name'=>'status', 'type' => 'xsd:int'),
        'obsolescence_date' => array('name'=>'obsolescence_date', 'type' => 'xsd:int'),
        'rank' => array('name'=>'rank', 'type' => 'xsd:int'),
        'item_type' => array('name'=>'item_type', 'type' => 'xsd:int'),
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfDocman_Item',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Docman_Item[]')),
    'tns:Docman_Item'
);

$GLOBALS['server']->wsdl->addComplexType(
    'Permission',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'type' => array('name'=>'type', 'type' => 'xsd:string'),
        'ugroup_id' => array('name'=>'ugroup_id', 'type' => 'xsd:int'), 
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfPermission',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Permission[]')),
    'tns:Permission'
);

$GLOBALS['server']->wsdl->addComplexType(
    'MetadataValue',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'label' => array('name'=>'label', 'type' => 'xsd:string'),
        'value' => array('name'=>'value', 'type' => 'xsd:string'), 
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfMetadataValue',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:MetadataValue[]')),
    'tns:MetadataValue'
);

$GLOBALS['server']->wsdl->addComplexType(
    'MetadataListValue',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'id' => array('name'=>'id', 'type' => 'xsd:int'),
        'name'     => array('name'=>'name', 'type' => 'xsd:string'),
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfMetadataListValue',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:MetadataListValue[]')),
    'tns:MetadataListValue'
);

$GLOBALS['server']->wsdl->addComplexType(
    'Metadata',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'label' => array('name'=>'label', 'type' => 'xsd:string'),
        'name' => array('name'=>'name', 'type' => 'xsd:string'),
        'type' => array('name'=>'type', 'type' => 'xsd:string'),
        'isMultipleValuesAllowed' => array('name'=>'isMultipleValuesAllowed', 'type' => 'xsd:int'),
        'isEmptyAllowed' => array('name'=>'isEmptyAllowed', 'type' => 'xsd:int'),
        'listOfValues' => array('name'=>'listOfValues', 'type' => 'tns:ArrayOfMetadataListValue'), 
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfMetadata',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Metadata[]')),
    'tns:Metadata'
);

//
// Function definition
//
$GLOBALS['server']->register(
    'getRootFolder',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        ),
    array('listFolderResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getRootFolder',
    'rpc',
    'encoded',
    'Returns the document object id that is at the top of the docman given a group object.'
);
$GLOBALS['server']->register(
    'listFolder',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'item_id'=>'xsd:int',
        ),
    array('listFolderResponse'=>'tns:ArrayOfDocman_Item'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#listFolder',
    'rpc',
    'encoded',
    'List folder contents.'
);
$GLOBALS['server']->register(
    'createDocmanFile',
    array(
        'sessionKey'        => 'xsd:string',
        'group_id'          => 'xsd:int',
        
        'parent_id'         => 'xsd:int',
        'title'             => 'xsd:string',
        'description'       => 'xsd:string',
        'ordering'          => 'xsd:string',
        'status'            => 'xsd:string',
        'obsolescence_date' => 'xsd:string',
        'permissions'       => 'tns:ArrayOfPermission',
        'metadata'          => 'tns:ArrayOfMetadataValue',
        
        'file_size'         => 'xsd:int',
        'file_name'         => 'xsd:string',
        'mime_type'         => 'xsd:string',
        'content'           => 'xsd:string',
        'chunk_offset'      => 'xsd:int',
        'chunk_size'        => 'xsd:int',
        
        'author'            => 'xsd:string',
        'date'              => 'xsd:string',
        'owner'             => 'xsd:string',
        'create_date'       => 'xsd:string',
        'update_date'       => 'xsd:string',
        ),
    array('createDocmanFileResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanFile',
    'rpc',
    'encoded',
    'Create a docman file
<pre>
sessionKey        Session key
group_id          Group ID
parent_id         Parent folder ID
title             Title
description       Description
ordering          Ordering (begin, end)
status            Status (none, draft, approved, rejected)
obsolescence_date Obsolescence date (yy-mm-dd or yyyy-mm-dd)
permissions       Permissions
metadata          Metadata values
file_size         File size
file_name         File name
mime_type         Mime type
content           Content (base64 encoded data)
chunk_offset      Chunk offset
chunk_size        Chunk size
author            Version author (user name)
date              Version date (YYYY-MM-DD HH:MM)
owner             Owner of the document
create_date       Item creation date
update_date       Item update date
</pre>'
);
$GLOBALS['server']->register(
    'createDocmanFileVersion',
    array(
        'sessionKey'        => 'xsd:string',
        'group_id'          => 'xsd:int',
        
        'item_id'           => 'xsd:int',
        'label'             => 'xsd:string',
        'changelog'         => 'xsd:string',

        'file_size'         => 'xsd:int',
        'file_name'         => 'xsd:string',
        'mime_type'         => 'xsd:string',
        'content'           => 'xsd:string',
        'chunk_offset'      => 'xsd:int',
        'chunk_size'        => 'xsd:int',
        
        'author'            => 'xsd:string',
        'date'              => 'xsd:string',
        ),
    array('createDocmanFileVersionResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanFileVersion',
    'rpc',
    'encoded',
    'Create a docman file version
<pre>
sessionKey        Session key
group_id          Group ID
item_id           Item ID
label             Version label
changelog         Changelog
file_size         File size
file_name         File name
mime_type         Mime type
content           Content (base64 encoded data)
chunk_offset      Chunk offset
chunk_size        Chunk size
author            Version author (user name)
date              Version date (YYYY-MM-DD HH:MM)
</pre>'
);
$GLOBALS['server']->register(
    'createDocmanEmbeddedFile',
    array(
        'sessionKey'        => 'xsd:string',
        'group_id'          => 'xsd:int',
        
        'parent_id'         => 'xsd:int',
        'title'             => 'xsd:string',
        'description'       => 'xsd:string',
        'ordering'          => 'xsd:string',
        'status'            => 'xsd:string',
        'obsolescence_date' => 'xsd:string',
        'content'           => 'xsd:string',
        'permissions'       => 'tns:ArrayOfPermission',
        'metadata'          => 'tns:ArrayOfMetadataValue',
    
        'author'            => 'xsd:string',
        'date'              => 'xsd:string',
        'owner'             => 'xsd:string',
        'create_date'       => 'xsd:string',
        'update_date'       => 'xsd:string',
        ),
    array('createDocmanEmbeddedFileResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanEmbeddedFile',
    'rpc',
    'encoded',
    'Create a docman embedded file
<pre>
sessionKey        Session key
group_id          Group ID
parent_id         Parent folder ID
title             Title
description       Description
ordering          Ordering (begin, end)
status            Status (none, draft, approved, rejected)
obsolescence_date Obsolescence date (yy-mm-dd or yyyy-mm-dd)
content           Content (raw data)
permissions       Permissions
metadata          Metadata values
author            Version author (user name)
date              Version date (YYYY-MM-DD HH:MM)
owner             Owner of the document
create_date       Item creation date
update_date       Item update date
</pre>'
);
$GLOBALS['server']->register(
    'createDocmanEmbeddedFileVersion',
    array(
        'sessionKey'        => 'xsd:string',
        'group_id'          => 'xsd:int',
    
        'item_id'           => 'xsd:int',
        'label'             => 'xsd:string',
        'changelog'         => 'xsd:string',
        'content'           => 'xsd:string',
        'author'            => 'xsd:string',
        'date'              => 'xsd:string',
        ),
    array('createDocmanEmbeddedFileVersionResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanEmbeddedFileVersion',
    'rpc',
    'encoded',
    'Create a docman embedded file version
<pre>
sessionKey        Session key
group_id          Group ID
item_id           Item ID
label             Version label
changelog         Changelog
content           Content (raw data)
author            Version author (user name)
date              Version date (YYYY-MM-DD HH:MM)
</pre>'
);
$GLOBALS['server']->register(
    'createDocmanWikiPage',
    array(
        'sessionKey'        => 'xsd:string',
        'group_id'          => 'xsd:int',
        
        'parent_id'         => 'xsd:int',
        'title'             => 'xsd:string',
        'description'       => 'xsd:string',
        'ordering'          => 'xsd:string',
        'status'            => 'xsd:string',
        'obsolescence_date' => 'xsd:string',
        'content'           => 'xsd:string',
        'permissions'       => 'tns:ArrayOfPermission',
        'metadata'          => 'tns:ArrayOfMetadataValue',
        'owner'             => 'xsd:string',
        'create_date'       => 'xsd:string',
        'update_date'       => 'xsd:string',
        ),
    array('createDocmanWikiPageResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanWikiPage',
    'rpc',
    'encoded',
    'Create a docman wiki page
<pre>
sessionKey        Session key
group_id          Group ID
parent_id         Parent folder ID
title             Title
description       Description
ordering          Ordering (begin, end)
status            Status (none, draft, approved, rejected)
obsolescence_date Obsolescence date (yy-mm-dd or yyyy-mm-dd)
content           Content (page name)
permissions       Permissions
metadata          Metadata values
owner             Owner of the document
create_date       Item creation date
update_date       Item update date
</pre>'
);
$GLOBALS['server']->register(
    'createDocmanLink',
    array(
        'sessionKey'        => 'xsd:string',
        'group_id'          => 'xsd:int',
        
        'parent_id'         => 'xsd:int',
        'title'             => 'xsd:string',
        'description'       => 'xsd:string',
        'ordering'          => 'xsd:string',
        'status'            => 'xsd:string',
        'obsolescence_date' => 'xsd:string',
        'content'           => 'xsd:string',
        'permissions'       => 'tns:ArrayOfPermission',
        'metadata'          => 'tns:ArrayOfMetadataValue',
        'owner'             => 'xsd:string',
        'create_date'       => 'xsd:string',
        'update_date'       => 'xsd:string',
        ),
    array('createDocmanLinkResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanLink',
    'rpc',
    'encoded',
    'Create a docman link
<pre>
sessionKey        Session key
group_id          Group ID
parent_id         Parent folder ID
title             Title
description       Description
ordering          Ordering (begin, end)
status            Status (none, draft, approved, rejected)
obsolescence_date Obsolescence date (yy-mm-dd or yyyy-mm-dd)
content           Content (url)
permissions       Permissions
metadata          Metadata values
owner             Owner of the document
create_date       Item creation date
</pre>'
);
$GLOBALS['server']->register(
    'createDocmanEmptyDocument',
    array(
        'sessionKey'        => 'xsd:string',
        'group_id'          => 'xsd:int',
        
        'parent_id'         => 'xsd:int',
        'title'             => 'xsd:string',
        'description'       => 'xsd:string',
        'ordering'          => 'xsd:string',
        'status'            => 'xsd:string',
        'obsolescence_date' => 'xsd:string',
        'permissions'       => 'tns:ArrayOfPermission',
        'metadata'          => 'tns:ArrayOfMetadataValue',
        'owner'             => 'xsd:string',
        'create_date'       => 'xsd:string',
        'update_date'       => 'xsd:string',
        ),
    array('createDocmanEmptyDocumentResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanEmptyDocument',
    'rpc',
    'encoded',
    'Create a docman empty document
<pre>
sessionKey        Session key
group_id          Group ID
parent_id         Parent folder ID
title             Title
description       Description
ordering          Ordering (begin, end)
status            Status (none, draft, approved, rejected)
obsolescence_date Obsolescence date (yy-mm-dd or yyyy-mm-dd)
permissions       Permissions
metadata          Metadata values
owner             Owner of the document
create_date       Item creation date
update_date       Item update date
</pre>'
);
$GLOBALS['server']->register(
    'appendDocmanFileChunk',
    array(
        'sessionKey'   => 'xsd:string',
        'group_id'     => 'xsd:int',
        'item_id'      => 'xsd:int',
        'content'      => 'xsd:string',
        'chunk_offset' => 'xsd:int',
        'chunk_size'   => 'xsd:int',
        ),
    array('appendDocmanFileChunkResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#appendDocmanFileChunk',
    'rpc',
    'encoded',
    'Append a chunk of data to a file
<pre>
sessionKey        Session key
group_id          Group ID
item_id           Item ID
content           Content (base64 encoded data)
chunk_offset      Chunk offset
chunk_size        Chunk size
</pre>'
);
$GLOBALS['server']->register(
    'getDocmanFileMD5sum',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'item_id'=>'xsd:int',
        ),
    array('getDocmanFileMD5sumResponse'=>'xsd:string'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getDocmanFileMD5sum',
    'rpc',
    'encoded',
    'Returns the MD5 checksum of the file corresponding to the provided item ID
<pre>
sessionKey        Session key
group_id          Group ID
item_id           Item ID
</pre>'
);
$GLOBALS['server']->register(
    'createDocmanFolder',
    array(
        'sessionKey'        => 'xsd:string',
        'group_id'          => 'xsd:int',
        
        'parent_id'         => 'xsd:int',
        'title'             => 'xsd:string',
        'description'       => 'xsd:string',
        'ordering'          => 'xsd:string',
        'status'            => 'xsd:string',
        'permissions'       => 'tns:ArrayOfPermission',
        'metadata'          => 'tns:ArrayOfMetadataValue',
        'owner'             => 'xsd:string',
        'create_date'       => 'xsd:string',
        'update_date'       => 'xsd:string',
        ),
    array('createDocmanFolderResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanFolder',
    'rpc',
    'encoded',
    'Create a folder
<pre>
sessionKey   Session key
group_id     Group ID
parent_id    Parent folder ID
title        Title
description  Description
ordering     Ordering (begin, end)
status       Status (none, draft, approved, rejected)
permissions  Permissions
metadata     Metadata values
owner        Owner of the folder
create_date  Item creation date
update_date  Item update date
</pre>'
);
$GLOBALS['server']->register(
    'deleteDocmanItem',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'item_id'=>'xsd:int'),
    array('deleteDocmanItemResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#deleteDocmanItem',
    'rpc',
    'encoded',
    'Delete an item (document or folder)'
);
$GLOBALS['server']->register(
    'monitorDocmanItem',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'item_id'=>'xsd:int'),
    array('monitorDocmanItemResponse'=>'xsd:boolean'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#monitorDocmanItem',
    'rpc',
    'encoded',
    'Monitor an item (document or folder)'
);

$GLOBALS['server']->register(
    'moveDocmanItem',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'  =>'xsd:int',
        'item_id'   =>'xsd:int',
        'parent'    =>'xsd:int'),
    array('moveDocmanItemResponse'=>'xsd:boolean'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#moveDocmanItem',
    'rpc',
    'encoded',
    'Move an item in a new folder'
);

$GLOBALS['server']->register(
    'getDocmanProjectMetadata',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'  =>'xsd:int',),
    array('getDocmanProjectMetadataResponse'=>'tns:ArrayOfMetadata'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getDocmanProjectMetadata',
    'rpc',
    'encoded',
    'Returns the metadata defined for the given project
<pre>
sessionKey   Session key
group_id     Group ID
</pre>'
);

} else {

//
// Function implementation
//
/**
 * Returns an array containing all the permissions for the specified item.
 * The ugroups that have no permission defined in the request take the permission of the parent folder.
 */
function _get_permissions_as_array($group_id, $parent_id, $permissions) {
    $permissions_array = array();
    
    $perms = array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE');

    // Get the ugroups of the parent
    $ugroups = permission_get_ugroups_permissions($group_id, $parent_id, $perms, false);
    
    // Initialize the ugroup permissions to the same values as the parent folder
    foreach ($ugroups as $ugroup) {
        $ugroup_id = $ugroup['ugroup']['id'];
        $permissions_array[$ugroup_id] = 100;
        foreach ($perms as $perm) {
            if (isset($ugroup['permissions'][$perm])) {
                $permissions_array[$ugroup_id] = Docman_PermissionsManager::getDefinitionIndexForPermission($perm);
            }
        }
    }
    
    // Set the SOAP-provided permissions
    foreach ($permissions as $index => $permission) {
        $ugroup_id = $permission->ugroup_id;
        if (isset($permissions_array[$ugroup_id])) {
            $permissions_array[$ugroup_id] = Docman_PermissionsManager::getDefinitionIndexForPermission($permission->type);
        }
    }
    
    return $permissions_array;
}

/**
 * Takes an array of metadata objects as provided by the SOAP request:
 * 
 * Array
 * (
 *     [0] => stdClass Object
 *         (
 *             [label] => field_2
 *             [value] => This is a string
 *         )
 * 
 *     [1] => stdClass Object
 *         (
 *             [label] => field_9
 *             [value] => 103
 *         )
 * 
 *     [2] => stdClass Object
 *         (
 *             [label] => field_9
 *             [value] => 104
 *         )
 * )
 * 
 * And returns an associative array of metadata as required by the Docman Actions:
 * 
 * Array
 * (
 *     [field_2] => This is a string
 *     [field_9] => Array
 *         (
 *             [0] => 103
 *             [1] => 104
 *         )
 * )  
 */
function _get_metadata_as_array($metadata) {
    $metadata_array = array();
    
    foreach ($metadata as $m) {
        if (isset($metadata_array[$m->label])) {
            if (is_array($metadata_array[$m->label])) {
                array_push($metadata_array[$m->label], $m->value);
            } else {
                $metadata_array[$m->label] = array($metadata_array[$m->label], $m->value);
            }
        } else {
            $metadata_array[$m->label] = $m->value;
        }
    }

    return $metadata_array;
}

/**
 * Returns the constant value associated to the requested status
 */
function _get_status_value($status) {
    switch ($status) {
        case 'draft' : $value = PLUGIN_DOCMAN_ITEM_STATUS_DRAFT; break;
        case 'approved' : $value = PLUGIN_DOCMAN_ITEM_STATUS_APPROVED; break;
        case 'rejected' : $value = PLUGIN_DOCMAN_ITEM_STATUS_REJECTED; break;
        default : $value = PLUGIN_DOCMAN_ITEM_STATUS_NONE; break;
    }
    
    return $value;
}

/**
 * Returns the user ID corresponding to the given user name, or null if it doesn't exist
 */
function _getUserIdByUserName($userName) {
    if ($userName == '') {
        return null;
    } else {
        $user = UserManager::instance()->getUserByUserName($userName);
        if ($user == null) {
            return null;
        } else {
            return $user->getId();
        }
    }
}

/**
 * Makes a docman request
 *
 * @param string       $sessionKey   Session Key
 * @param int          $group_id     Group ID
 * @param array        $params       Request parameters
 * @return unknown                   Request response
 */
function _makeDocmanRequest($sessionKey, $group_id, $action, $params = array()) {
    $actor ="_makeDocmanRequest ($action)";
    
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', $actor);
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(), $actor);
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.', $actor);
        }
        
        $params['group_id'] = $group_id;
        $params['action'] = $action;
        $params['confirm'] = true;
        
        $request =& new SOAPRequest($params);
        
        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            // Process request
            $result = $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null, $msg, $actor);
            } else {
                return $result;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', $actor);
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', $actor);
    }
}

/**
* Returns the document object that is at the top of the docman given a group object.
*/
function getRootFolder($sessionKey,$group_id) {
    return _makeDocmanRequest($sessionKey, $group_id, 'getRootFolder');
}

/**
* Lists the contents of a folder
*/
function listFolder($sessionKey,$group_id,$item_id) {
    $params = array('id' => $item_id, 'report' => 'List');
    return _makeDocmanRequest($sessionKey, $group_id, 'show', $params);
}

/**
 * Creates a docman item
 *
 * @param string       $sessionKey        Session key
 * @param int          $group_id          Group ID
 * @param int          $parent_id         Parent folder ID
 * @param string       $title             Title
 * @param string       $description       Description
 * @param string       $ordering          Ordering (begin, end)
 * @param string       $status            Status (none, draft, approved, rejected)
 * @param string       $obsolescence_date Obsolescence date (yy-mm-dd or yyyy-mm-dd)
 * @param string       $type              Type (folder, file, embedded_file, link, empty, wiki)
 * @param Array        $permissions       Permissions
 * @param Array        $metadata          Metadata values
 * @param string       $owner             Owner
 * @param string       $create_date       Create date
 * @param string       $update_date       Update date
 * @param Array        $extraParams       Extra parameters array
 */
function _createDocmanItem($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $type, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams = array()) {
        
    $params = array(
        'item'         => array(
            'parent_id'         => $parent_id,
            'title'             => $title,
            'description'       => $description,
            'item_type'         => $type,
            'status'            => _get_status_value($status),
            'create_date'       => $create_date,
            'update_date'       => $update_date,
        ),
        'owner'             => _getUserIdByUserName($owner),
        'ordering'     => $ordering,
        'permissions'  => _get_permissions_as_array($group_id, $parent_id, $permissions),
        'metadata'     => _get_metadata_as_array($metadata),
    );

    if (isset($extraParams['item'])) {
        $params['item'] += $extraParams['item'];
    }
    $params += $extraParams;
    
    return _makeDocmanRequest($sessionKey, $group_id, 'createItem', $params);
}

/**
 * Creates a docman document
 */
function _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $type, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams = array()) {
    $extraParams['item']['obsolescence_date'] = $obsolescence_date;
    return _createDocmanItem($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $type, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}

/**
 * Creates a docman file version
 */
function createDocmanFileVersion($sessionKey, $group_id, $item_id, $label, $changelog, $file_size, $file_name, $mime_type, $content, $chunk_offset, $chunk_size, $author, $date) {
        
    $params = array(
        'id'             => $item_id,
        'version'        => array('label' => $label, 'changelog' => $changelog),
        'upload_content' => base64_decode($content),
        'chunk_offset'   => $chunk_offset,
        'chunk_size'     => $chunk_size,
        'file_size'      => $file_size,
        'file_name'      => $file_name,
        'mime_type'      => $mime_type,
        'date'           => $date,
        'author'         => _getUserIdByUserName($author),
    );
    
    return _makeDocmanRequest($sessionKey, $group_id, 'new_version', $params);
}

/**
 * Creates a docman embedded file version
 */
function createDocmanEmbeddedFileVersion($sessionKey, $group_id, $item_id, $label, $changelog, $content, $author, $date) {

    $params = array(
        'id'        => $item_id,
        'version'   => array('label' => $label, 'changelog' => $changelog,),
        'content'   => $content,
        'date'      => $date,
        'author'    => _getUserIdByUserName($author),
    );
    
    return _makeDocmanRequest($sessionKey, $group_id, 'new_version', $params);
}

/**
 * Creates a docman file
 */
function createDocmanFile($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $permissions, $metadata, $file_size, $file_name, $mime_type, $content, $chunk_offset, $chunk_size, $author, $date, $owner, $create_date, $update_date) {

    $extraParams = array(
        'chunk_offset'   => $chunk_offset,
        'chunk_size'     => $chunk_size,
        'file_size'      => $file_size,
        'file_name'      => $file_name,
        'mime_type'      => $mime_type,
        'upload_content' => base64_decode($content),
        'date'           => $date,
        'author'         => _getUserIdByUserName($author),
    );
    
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_FILE, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}

/**
 * Creates a docman embedded file
 */
function createDocmanEmbeddedFile($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $content, $permissions, $metadata, $author, $date, $owner, $create_date, $update_date) {
    $extraParams = array(
        'content' => $content,
        'date'    => $date,
        'author'  => _getUserIdByUserName($author),
    );
    
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}

/**
 * Creates a docman wiki page
 */
function createDocmanWikiPage($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $content, $permissions, $metadata, $owner, $create_date, $update_date) {
    $extraParams['item']['wiki_page'] = $content;
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_WIKI, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}

/**
 * Creates a docman link
 */
function createDocmanLink($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $content, $permissions, $metadata, $owner, $create_date, $update_date) {
    $extraParams['item']['link_url'] = $content;
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_LINK, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}

/**
 * Creates a docman embedded file
 */
function createDocmanEmptyDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $permissions, $metadata, $owner, $create_date, $update_date) {
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, $permissions, $metadata, $owner, $create_date, $update_date);
}

/**
 * Appends a chunk of data to a file
 */
function appendDocmanFileChunk($sessionKey, $group_id, $item_id, $content, $chunk_offset, $chunk_size) {

    $params = array(
        'item_id'        => $item_id,
        'upload_content' => base64_decode($content),
        'chunk_offset'   => $chunk_offset,
        'chunk_size'     => $chunk_size,
    );
        
    return _makeDocmanRequest($sessionKey, $group_id, 'appendFileChunk', $params);
}

/**
 * Returns the MD5 checksum of the file corresponding to the provided item ID.
 */
function getDocmanFileMD5sum($sessionKey, $group_id, $item_id) {
    $params = array('item_id' => $item_id);
    return _makeDocmanRequest($sessionKey, $group_id, 'getFileMD5sum', $params);
}

/**
 * Creates a docman folder
 */
function createDocmanFolder($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $permissions, $metadata, $owner, $create_date, $update_date) {
    return _createDocmanItem($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, $permissions, $metadata, $owner, $create_date, $update_date);
}

/**
 * Deletes a docman item
 */
function deleteDocmanItem($sessionKey, $group_id, $item_id) {
    return _makeDocmanRequest($sessionKey, $group_id, 'delete', array('id' => $item_id));
}

/**
 * Enables the monitoring if an item by a user
 */
function monitorDocmanItem($sessionKey, $group_id, $item_id) {
    return _makeDocmanRequest($sessionKey, $group_id, 'monitor', array('id' => $item_id, 'monitor' => true));
}

/**
 * Moves an item
 */
function moveDocmanItem($sessionKey, $group_id, $item_id, $new_parent) {
    return _makeDocmanRequest($sessionKey, $group_id, 'move_here', array('item_to_move' => $item_id, 'id' => $new_parent));
}

/**
 * Returns the metadata of the given project 
 */
function getDocmanProjectMetadata($sessionKey, $group_id) {

    $result = _makeDocmanRequest($sessionKey, $group_id, 'getProjectMetadata');
    
    foreach ($result as &$md) {
        $md->listOfValues = array();
        if($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $md->listOfValues = _makeDocmanRequest($sessionKey, $group_id, 'getMetadataListOfValues', array('label' => $md->getLabel()));
         }
    }
    
    return $result;
}

$GLOBALS['server']->addFunction(
        array(
            'getRootFolder',
            'listFolder',
            'createDocmanEmbeddedFile',
            'createDocmanEmbeddedFileVersion',
            'createDocmanWikiPage',
            'createDocmanLink',
            'createDocmanEmptyDocument',
            'createDocmanFile',
            'createDocmanFileVersion',
            'appendDocmanFileChunk',
            'getDocmanFileMD5sum',
            'createDocmanFolder',
            'deleteDocmanItem',
            'monitorDocmanItem',
            'moveDocmanItem',
            'getDocmanProjectMetadata',
            ));
}

?>
