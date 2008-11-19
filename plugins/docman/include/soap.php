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
        
        'content'           => 'xsd:string',
        'chunk_offset'      => 'xsd:int',
        'chunk_size'        => 'xsd:int',
        'file_size'         => 'xsd:int',
        'file_name'         => 'xsd:string',
        'mime_type'         => 'xsd:string',
        ),
    array('createDocmanFileResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanFile',
    'rpc',
    'encoded',
    'Create a file.'
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
        ),
    array('createDocmanEmbeddedFileResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanEmbeddedFile',
    'rpc',
    'encoded',
    'Create an embedded file.'
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
        ),
    array('createDocmanWikiPageResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanWikiPage',
    'rpc',
    'encoded',
    'Create a wiki page.'
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
        ),
    array('createDocmanLinkResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanLink',
    'rpc',
    'encoded',
    'Create a link.'
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
        ),
    array('createDocmanEmptyDocumentResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanEmptyDocument',
    'rpc',
    'encoded',
    'Create an empty document.'
);
$GLOBALS['server']->register(
    'appendDocmanFileChunk',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'item_id'=>'xsd:int',
        'content'=>'xsd:string',
        'chunk_offset'=>'xsd:int',
        'chunk_size'=>'xsd:int',
        ),
    array('appendDocmanFileChunkResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#appendDocmanFileChunk',
    'rpc',
    'encoded',
    'Append a chunk of data to a file.'
);
$GLOBALS['server']->register(
    'getDocmanFileMD5sum',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'item_id'=>'xsd:int',
        'version_number'=>'xsd:int',
        ),
    array('getDocmanFileMD5sumResponse'=>'xsd:string'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getDocmanFileMD5sum',
    'rpc',
    'encoded',
    'Returns the MD5 checksum of the file corresponding to the provided item ID.'
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
        ),
    array('createDocmanFolderResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanFolder',
    'rpc',
    'encoded',
    'Create a folder.'
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

} else {

//
// Function implementation
//
/**
* getRootFolder
* 
* Returns the document object that is at the top of the docman given a group object.
*
*/
function getRootFolder($sessionKey,$group_id) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', 'getRootFolder');
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(),  'getRootFolder');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.',  'getRootFolder');
        }
        
        $request =& new SOAPRequest(array(
            'group_id' => $group_id,
            //needed internally in docman vvv
            'action'       => 'getRootFolder',
        ));
        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $result = $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null,  $msg,  'getRootFolder');
            } else {
                return $result;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', 'monitor');
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'getRootFolder');
    }
}

/**
* listFolder
* 
* TODO: description
*
*/
function listFolder($sessionKey,$group_id,$item_id) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', 'listFolder');
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(),  'listFolder');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.',  'listFolder');
        }
        
        $request =& new SOAPRequest(array(
            'group_id' => $group_id,
            'id'       => $item_id,
            //needed internally in docman vvv
            'action'       => 'show',
            'report'       => 'List',
        ));
        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $result = $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null,  $msg,  'listFolder');
            } else {
                return $result;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', 'monitor');
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'listFolder');
    }
}

/**
 * Returns the integer value that corresponds to the permission
 */
function _get_definition_index_for_permission($p) {
    switch ($p) {
        case 'PLUGIN_DOCMAN_READ':
            return 1;
            break;
        case 'PLUGIN_DOCMAN_WRITE':
            return 2;
            break;
        case 'PLUGIN_DOCMAN_MANAGE':
            return 3;
            break;
        default:
            return 100;
            break;
    }
}

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
                $permissions_array[$ugroup_id] = _get_definition_index_for_permission($perm);
            }
        }
    }
    
    // Set the SOAP-provided permissions
    foreach ($permissions as $index => $permission) {
        $ugroup_id = $permission->ugroup_id;
        if (isset($permissions_array[$ugroup_id])) {
            $permissions_array[$ugroup_id] = _get_definition_index_for_permission($permission->type);
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
 * And returns an associated array of metadata as required by the Docman Actions:
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
 * Create a docman document
 *
 * @param string       $sessionKey        Session key
 * @param int          $group_id          Group ID
 * @param int          $parent_id         Parent folder ID
 * @param string       $title             Title
 * @param string       $description       Description
 * @param string       $ordering          Ordering (begin, end)
 * @param string       $status            Status (none, draft, approved, rejected)
 * @param string       $obsolescence_date Obsolescence date (yy-mm-dd or yyyy-mm-dd)
 * @param string       $type              Type (file, embedded_file, link, empty, wiki)
 * @param Array        $permissions       Permissions
 * @param Array        $metadata          Metadata values
 * @param string       $soapfunction      The SOAP function that called this function
 * @param string       $content           Content (base64 encoded data, url, wiki page name)
 * @param int          $chunk_offset      Chunk offset
 * @param int          $chunk_size        Chunk size
 * @param int          $file_size         File size
 * @param string       $file_name         File name
 * @param string       $mime_type         Mime type
 */
function _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $type, $permissions, $metadata, $soapfunction, $content = null, $chunk_offset = null, $chunk_size = null, $file_size = null, $file_name = null, $mime_type = null) {
    global $Language;
    
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', $soapfunction);
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(), $soapfunction);
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.', $soapfunction);
        }
        
        $soap_request_params = array(
            'group_id'     => $group_id,
            'item'         => array(
                'parent_id'         => $parent_id,
                'title'             => $title,
                'description'       => $description,
                'status'            => _get_status_value($status),
                'item_type'         => $type,
                'obsolescence_date' => $obsolescence_date
            ),
            'ordering'     => $ordering,
            'permissions'  => _get_permissions_as_array($group_id, $parent_id, $permissions),
            'metadata'     => _get_metadata_as_array($metadata),
            'chunk_offset' => $chunk_offset,
            'chunk_size'   => $chunk_size,
            'file_size'    => $file_size,
            'file_name'    => $file_name,
            'mime_type'    => $mime_type,
            //needed internally in docman vvv
            'action'       => 'createDocument',
            'confirm'      => true,  
        );
        switch ($type) {
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:            $soap_request_params['upload_content'] = base64_decode($content); break;
            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:    $soap_request_params['content'] = $content; break;
            case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:            $soap_request_params['item']['wiki_page'] = $content; break;
            case PLUGIN_DOCMAN_ITEM_TYPE_LINK:            $soap_request_params['item']['link_url'] = $content; break;
        }
        
        $request =& new SOAPRequest($soap_request_params);
        
        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $result = $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null, $msg, $soapfunction);
            } else {
                return $result;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', $soapfunction);
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', $soapfunction);
    }
}

/**
 * Create a docman file
 *
 * @param string       $sessionKey        Session key
 * @param int          $group_id          Group ID
 * @param int          $parent_id         Parent folder ID
 * @param string       $title             Title
 * @param string       $description       Description
 * @param string       $ordering          Ordering (begin, end)
 * @param string       $status            Status (none, draft, approved, rejected)
 * @param string       $obsolescence_date Obsolescence date (yy-mm-dd or yyyy-mm-dd)
 * @param Array        $permissions       Permissions
 * @param Array        $metadata          Metadata values
 * @param string       $content           Content (base64 encoded data)
 * @param int          $chunk_offset      Chunk offset
 * @param int          $chunk_size        Chunk size
 * @param int          $file_size         File size
 * @param string       $file_name         File name
 * @param string       $mime_type         Mime type
 */
function createDocmanFile($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $permissions, $metadata, $content, $chunk_offset, $chunk_size, $file_size, $file_name, $mime_type) {
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_FILE, $permissions, $metadata, 'createDocmanFile', $content, $chunk_offset, $chunk_size, $file_size, $file_name, $mime_type);
}

/**
 * Create a docman embedded file
 *
 * @param string       $sessionKey        Session key
 * @param int          $group_id          Group ID
 * @param int          $parent_id         Parent folder ID
 * @param string       $title             Title
 * @param string       $description       Description
 * @param string       $ordering          Ordering (begin, end)
 * @param string       $status            Status (none, draft, approved, rejected)
 * @param string       $obsolescence_date Obsolescence date (yy-mm-dd or yyyy-mm-dd)
 * @param string       $content           Content (raw data)
 * @param Array        $permissions       Permissions
 * @param Array        $metadata          Metadata values
 */
function createDocmanEmbeddedFile($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $content, $permissions, $metadata) {
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE, $permissions, $metadata, 'createDocmanEmbeddedFile', $content);
}

/**
 * Create a docman wiki page
 *
 * @param string       $sessionKey        Session key
 * @param int          $group_id          Group ID
 * @param int          $parent_id         Parent folder ID
 * @param string       $title             Title
 * @param string       $description       Description
 * @param string       $ordering          Ordering (begin, end)
 * @param string       $status            Status (none, draft, approved, rejected)
 * @param string       $obsolescence_date Obsolescence date (yy-mm-dd or yyyy-mm-dd)
 * @param string       $content           Content (page name)
 * @param Array        $permissions       Permissions
 * @param Array        $metadata          Metadata values
 */
function createDocmanWikiPage($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $content, $permissions, $metadata) {
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_WIKI, $permissions, $metadata, 'createDocmanWikiPage', $content);
}

/**
 * Create a docman link
 *
 * @param string       $sessionKey        Session key
 * @param int          $group_id          Group ID
 * @param int          $parent_id         Parent folder ID
 * @param string       $title             Title
 * @param string       $description       Description
 * @param string       $ordering          Ordering (begin, end)
 * @param string       $status            Status (none, draft, approved, rejected)
 * @param string       $obsolescence_date Obsolescence date (yy-mm-dd or yyyy-mm-dd)
 * @param string       $content           Content (url)
 * @param Array        $permissions       Permissions
 * @param Array        $metadata          Metadata values
 */
function createDocmanLink($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $content, $permissions, $metadata) {
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_LINK, $permissions, $metadata, 'createDocmanLink', $content);
}

/**
 * Create a docman embedded file
 *
 * @param string       $sessionKey        Session key
 * @param int          $group_id          Group ID
 * @param int          $parent_id         Parent folder ID
 * @param string       $title             Title
 * @param string       $description       Description
 * @param string       $ordering          Ordering (begin, end)
 * @param string       $status            Status (none, draft, approved, rejected)
 * @param string       $obsolescence_date Obsolescence date (yy-mm-dd or yyyy-mm-dd)
 * @param Array        $permissions       Permissions
 * @param Array        $metadata          Metadata values
 */
function createDocmanEmptyDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $permissions, $metadata) {
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, $permissions, $metadata, 'createDocmanEmptyDocument');
}

/**
 * Append a chunk of data to a file
 * 
 * @param string       $sessionKey   Session key
 * @param int          $group_id     Group ID
 * @param int          $parent_id    Parent folder ID
 * @param string       $content      Content (base64 encoded data)
 * @param int          $chunk_offset Chunk offset
 * @param int          $chunk_size   Chunk size
 */
function appendDocmanFileChunk($sessionKey, $group_id, $item_id, $content, $chunk_offset, $chunk_size) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', 'appendDocmanFileChunk');
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(),  'appendDocmanFileChunk');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.',  'appendDocmanFileChunk');
        }
        
        $soap_request_params = array(
            'group_id'       => $group_id,
            'item_id'        => $item_id,
            'upload_content' => base64_decode($content),
            'chunk_offset'   => $chunk_offset,
            'chunk_size'     => $chunk_size,
            //needed internally in docman vvv
            'action'         => 'appendFileChunk',
            'confirm'        => true,
        );
        
        $request =& new SOAPRequest($soap_request_params);
        
        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $result = $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null,  $msg,  'appendDocmanFileChunk');
            } else {
                return $result;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', 'appendDocmanFileChunk');
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'appendDocmanFileChunk');
    }
}

/**
 * Returns the MD5 checksum of the file corresponding to the provided item ID.
 * 
 * @param string       $sessionKey     Session key
 * @param int          $group_id       Group ID
 * @param int          $item_id        Item ID
 * @param int          $version_number Version Number
 */
function getDocmanFileMD5sum($sessionKey, $group_id, $item_id, $version_number) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', 'getDocmanFileMD5sum');
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(),  'getDocmanFileMD5sum');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.',  'getDocmanFileMD5sum');
        }
        
        $soap_request_params = array(
            'group_id'       => $group_id,
            'item_id'        => $item_id,
            'version_number' => $version_number,
            //needed internally in docman vvv
            'action'         => 'getFileMD5sum',
            'confirm'        => true,
        );
        
        $request =& new SOAPRequest($soap_request_params);

        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $result = $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null,  $msg, 'getDocmanFileMD5sum');
            } else {
                return $result;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', 'getDocmanFileMD5sum');
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'getDocmanFileMD5sum');
    }
}

/**
 * Create a docman folder
 *
 * @param string       $sessionKey   Session key
 * @param int          $group_id     Group ID
 * @param int          $parent_id    Parent folder ID
 * @param string       $title        Title
 * @param string       $description  Description
 * @param string       $ordering     Ordering (begin, end)
 * @param string       $status       Status (none, draft, approved, rejected)
 * @param Array        $permissions  Permissions
 * @param Array        $metadata     Metadata values
 */
function createDocmanFolder($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $permissions, $metadata) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', 'createDocmanFolder');
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(),  'createDocmanFolder');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.',  'createDocmanFolder');
        }
        
        $request =& new SOAPRequest(array(
            'group_id'    => $group_id,
            'item'        => array(
                'parent_id'   => $parent_id,
                'title'       => $title,
                'description' => $description,
                'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
                'status'      => _get_status_value($status),
            ),
            'ordering'    => $ordering,
            'permissions' => _get_permissions_as_array($group_id, $parent_id, $permissions),
            'metadata'    => _get_metadata_as_array($metadata),
            //needed internally in docman vvv
            'action'      => 'createFolder',
            'confirm'     => true,
        ));
        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $result = $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null, $msg, 'createDocmanFolder');
            } else {
                return $result;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', 'createDocmanFolder');
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'createDocmanFolder');
    }
}

/**
 * 
 */
function deleteDocmanItem($sessionKey,$group_id,$item_id) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', 'deleteDocmanItem');
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(),  'deleteDocmanItem');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.',  'deleteDocmanItem');
        }
        
        $request =& new SOAPRequest(array(
            'group_id' => $group_id,
            'id'       => $item_id,
            //needed internally in docman vvv
            'action'       => 'delete',
            'confirm'      => true,
        ));
        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $result = $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null, $msg, 'deleteDocmanItem');
            } else {
                return $result;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', 'deleteDocmanItem');
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'deleteDocmanItem');
    }
}

/**
 * 
 */
function monitorDocmanItem($sessionKey,$group_id,$item_id) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', 'monitorDocmanItem');
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(),  'monitorDocmanItem');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.',  'monitorDocmanItem');
        }
        
        $request =& new SOAPRequest(array(
            'group_id' => $group_id,
            'id'       => $item_id,
            //needed internally in docman vvv
            'action'       => 'monitor',
            'monitor'      => true,
        ));
        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null,  $msg,  'monitorDocmanItem');
            } else {
                return true;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', 'monitor');
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'monitorDocmanItem');
    }
}

function moveDocmanItem($sessionKey, $group_id, $item_id, $new_parent) {
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', 'moveDocmanItem');
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(),  'moveDocmanItem');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.',  'moveDocmanItem');
        }
        
        $request =& new SOAPRequest(array(
            'group_id'     => $group_id,
            'item_to_move' => $item_id,
            'id'           => $new_parent,
            //needed internally in docman vvv
            'action'       => 'move_here',
            'confirm'      => true,
        ));
        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null,  $msg,  'moveDocmanItem');
            } else {
                return true;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', 'moveDocmanItem');
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'moveDocmanItem');
    }
}

$GLOBALS['server']->addFunction(
        array(
            'getRootFolder',
            'listFolder',
        
            'createDocmanEmbeddedFile',
            'createDocmanWikiPage',
            'createDocmanLink',
            'createDocmanEmptyDocument',
        
            'createDocmanFile',
            'appendDocmanFileChunk',
            'getDocmanFileMD5sum',
        
            'createDocmanFolder',
            'deleteDocmanItem',
            'monitorDocmanItem',
            'moveDocmanItem',
            ));
}


?>
