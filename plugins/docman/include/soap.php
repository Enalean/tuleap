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
        //'link_url' => array('name'=>'link_url', 'type' => 'xsd:string'),
        //'wiki_page' => array('name'=>'wiki_page', 'type' => 'xsd:string'),
        //'file_is_embedded' => array('name'=>'file_is_embedded', 'type' => 'xsd:boolean')
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
    'createDocmanDocument',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'parent_id'=>'xsd:int',
        'title'=>'xsd:string',
        'description'=>'xsd:string',
        'type' => 'xsd:string',
        'content' => 'xsd:string',
        'ordering'=>'xsd:string',
    	'permissions'=>'tns:ArrayOfPermission',
    	// The next are optionals and are used only for files
        'chunk_offset'=>'xsd:int',
        'chunk_size'=>'xsd:int',
    	'file_size'=>'xsd:int',
    	'file_name'=>'xsd:string',
        'mime_type'=>'xsd:string',
        ),
    array('createDocmanDocumentResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanDocument',
    'rpc',
    'encoded',
    'Create a document.'
);
$GLOBALS['server']->register(
    'appendFileChunk',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'item_id'=>'xsd:int',
        'content'=>'xsd:string',
        'chunk_offset'=>'xsd:int',
        'chunk_size'=>'xsd:int',
        ),
    array('appendFileChunkResponse'=>'xsd:int'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#appendFileChunk',
    'rpc',
    'encoded',
    'Append a chunk of data to a file.'
);
$GLOBALS['server']->register(
    'getFileMD5sum',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'item_id'=>'xsd:int',
    	'version_number'=>'xsd:int',
        ),
    array('getFileMD5sumResponse'=>'xsd:string'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getFileMD5sum',
    'rpc',
    'encoded',
    'Returns the MD5 checksum of the file corresponding to the provided item ID.'
);
$GLOBALS['server']->register(
    'createDocmanFolder',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'parent_id'=>'xsd:int',
        'title'=>'xsd:string',
        'description'=>'xsd:string',
        'ordering'=>'xsd:string',
        'permissions'=>'tns:ArrayOfPermission',
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
 * @see Docman_Actions
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
 * Returns an array containing all the permissions for the specified item. The ugroups that have no defined permission take the permission of the parent folder.
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
 * 
 */
function createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $type, $content, $ordering, $permissions, $chunk_offset, $chunk_size, $file_size, $file_name, $mime_type) {
	global $Language;

    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', 'createDocmanDocument');
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(),  'createDocmanDocument');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.',  'createDocmanDocument');
        }
        
        
        $soap_request_params = array(
            'group_id' => $group_id,
            'item' => array(
                'parent_id' => $parent_id,
                'title' => $title,
                'description' => $description,
            ),
            'ordering' => $ordering,
            'permissions'  => _get_permissions_as_array($group_id, $parent_id, $permissions),
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
            case "file":
                $soap_request_params['item']['item_type'] =  PLUGIN_DOCMAN_ITEM_TYPE_FILE;
                $soap_request_params['upload_content'] = base64_decode($content);
                break;
            case "wiki":
                $soap_request_params['item']['item_type'] =  PLUGIN_DOCMAN_ITEM_TYPE_WIKI;
                $soap_request_params['item']['wiki_page'] = $content;
                break;
            case "embedded_file":
                $soap_request_params['item']['item_type'] =  PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE;
                $soap_request_params['content'] = $content;
                break;
            default:
                $soap_request_params['item']['item_type'] =  PLUGIN_DOCMAN_ITEM_TYPE_LINK;
                $soap_request_params['item']['link_url'] = $content;
                break;
        }
        
        $request =& new SOAPRequest($soap_request_params);
        
        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $result = $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null,  $msg,  'createDocmanDocument');
            } else {
                return $result;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', 'createDocmanDocument');
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'createDocmanDocument');
    }
}

/**
 * Append a chunk of data to a file
 */
function appendFileChunk($sessionKey, $group_id, $item_id, $content, $chunk_offset, $chunk_size) {
  	global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', 'appendFileChunk');
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(),  'appendFileChunk');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.',  'appendFileChunk');
        }
        
		$soap_request_params = array(
			'group_id'			=> $group_id,
			'item_id'			=> $item_id,
            'upload_content'	=> base64_decode($content),
            'chunk_offset'		=> $chunk_offset,
            'chunk_size'		=> $chunk_size,
            //needed internally in docman vvv
            'action'			=> 'appendFileChunk',
            'confirm'			=> true,
        );
        
		$request =& new SOAPRequest($soap_request_params);
        
    	$plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $result = $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null,  $msg,  'appendFileChunk');
            } else {
                return $result;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', 'appendFileChunk');
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'appendFileChunk');
    }
}

/**
 * Returns the MD5 checksum of the file corresponding to the provided item ID.
 */
function getFileMD5sum($sessionKey, $group_id, $item_id, $version_number) {
  	global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new SoapFault(get_group_fault, 'Could Not Get Group', 'getFileMD5sum');
        } elseif ($group->isError()) {
            return new SoapFault(get_group_fault,  $group->getErrorMessage(),  'getFileMD5sum');
        }
        if (!checkRestrictedAccess($group)) {
            return new SoapFault(get_group_fault,  'Restricted user: permission denied.',  'getFileMD5sum');
        }
        
		$soap_request_params = array(
			'group_id'			=> $group_id,
			'item_id'			=> $item_id,
			'version_number'	=> $version_number,
            //needed internally in docman vvv
            'action'			=> 'getFileMD5sum',
            'confirm'			=> true,
        );
        
		$request =& new SOAPRequest($soap_request_params);

    	$plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $result = $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null,  $msg, 'getFileMD5sum');
            } else {
                return $result;
            }
        } else {
            return new SoapFault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN, 'Unavailable plugin', 'getFileMD5sum');
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'getFileMD5sum');
    }
}

/**
 * 
 */
function createDocmanFolder($sessionKey,$group_id,$parent_id, $title, $description, $ordering, $permissions) {
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
            'group_id' => $group_id,
            'item' => array(
                'parent_id'       => $parent_id,
                'title' => $title,
                'description' => $description,
                'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
            ),
            'ordering' => $ordering,
            'permissions'  => _get_permissions_as_array($group_id, $parent_id, $permissions),
            //needed internally in docman vvv
            'action'       => 'createFolder',
            'confirm'      => true,
        ));
        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $result = $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new SoapFault(null,  $msg,  'createDocmanFolder');
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
                   return new SoapFault(null,  $msg,  'deleteDocmanItem');
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
            'createDocmanDocument',
            'appendFileChunk',
        	'getFileMD5sum',
            'createDocmanFolder',
            'deleteDocmanItem',
            'monitorDocmanItem',
            'moveDocmanItem',
            ));
}


?>
