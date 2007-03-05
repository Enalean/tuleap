<?php
require_once ('nusoap.php');
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
    'Docman_Metadata',
    'complexType',
    'struct',
    'sequence',
    '',
    array(
        'id' => array('name'=>'item_id', 'type' => 'xsd:int'),
        'group_id' => array('name'=>'group_id', 'type' => 'xsd:int'),
        'name' => array('name'=>'name', 'type' => 'xsd:string'),
        'type' => array('name'=>'type', 'type' => 'xsd:int'),
        'label' => array('name'=>'label', 'type' => 'xsd:string'),
        'description' => array('name'=>'description', 'type'=>'string'),
        'isRequired' => array('name'=>'is_required', 'type'=>'boolean'),
        'isEmptyAllowed' => array('name'=>'isEmptyAllowed', 'type'=>'boolean'),
        'keepHistory' => array('name'=>'keepHistory', 'type'=>'boolean'),
        'special' => array('name'=>'special', 'type'=>'int'),
        'defaultValue' => array('name'=>'defaultValue', 'type'=>'string'),
        'useIt' => array('name'=>'useIt', 'type'=>'boolean')
    )
);

$GLOBALS['server']->wsdl->addComplexType(
    'ArrayOfDocman_Metadata',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Docman_Metadata[]')),
    'tns:Docman_Metadata'
);

//
// Function definition
//
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
        ),
    array('createDocmanDocumentResponse'=>'xsd:boolean'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#createDocmanDocument',
    'rpc',
    'encoded',
    'Create a document.'
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
        ),
    array('createDocmanFolderResponse'=>'xsd:boolean'),
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
    array('deleteDocmanItemResponse'=>'xsd:boolean'),
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
    'getDocmanItemProperties',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'item_id'=>'xsd:int'),
    array('getDocmanItemPropertiesResponse'=>'tns:ArrayOfDocman_Metadata'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getDocmanItemProperties',
    'rpc',
    'encoded',
    'Returns the properties of the document item_id'
);


//
// Function implementation
//
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
            return new soap_fault(get_group_fault,'listFolder','Could Not Get Group','Could Not Get Group');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'listFolder', $group->getErrorMessage(),$group->getErrorMessage());
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'listFolder', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
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
                   return new soap_fault('', 'listFolder', $msg, $msg);
            } else {
                return $result;
            }
        } else {
            return new soap_fault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN,'monitor','Unavailable plugin','Unavailable plugin');;
        }
    } else {
        return new soap_fault(invalid_session_fault,'monitorDocmanItem','Invalid Session','');
    }
}

/**
 * 
 */
function createDocmanDocument($sessionKey,$group_id,$parent_id, $title, $description, $type, $content, $ordering) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'createDocmanDocument','Could Not Get Group','Could Not Get Group');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'createDocmanDocument', $group->getErrorMessage(),$group->getErrorMessage());
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'createDocmanDocument', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        
        
        $soap_request_params = array(
            'group_id' => $group_id,
            'item' => array(
                'parent_id' => $parent_id,
                'title' => $title,
                'description' => $description,
            ),
            'ordering' => $ordering,
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
            $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new soap_fault('', 'createDocmanDocument', $msg, $msg);
            } else {
                return true;
            }
        } else {
            return new soap_fault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN,'createDocmanDocument','Unavailable plugin','Unavailable plugin');;
        }
    } else {
        return new soap_fault(invalid_session_fault,'createDocmanDocument','Invalid Session','');
    }
}

/**
 * 
 */
function createDocmanFolder($sessionKey,$group_id,$parent_id, $title, $description, $ordering) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'createDocmanFolder','Could Not Get Group','Could Not Get Group');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'createDocmanFolder', $group->getErrorMessage(),$group->getErrorMessage());
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'createDocmanFolder', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
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
            //needed internally in docman vvv
            'action'       => 'createFolder',
            'confirm'      => true,
        ));
        $plugin_manager =& PluginManager::instance();
        $p =& $plugin_manager->getPluginByName('docman');
        if ($p && $plugin_manager->isPluginAvailable($p)) {
            $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new soap_fault('', 'createDocmanFolder', $msg, $msg);
            } else {
                return true;
            }
        } else {
            return new soap_fault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN,'createDocmanFolder','Unavailable plugin','Unavailable plugin');;
        }
    } else {
        return new soap_fault(invalid_session_fault,'createDocmanFolder','Invalid Session','');
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
            return new soap_fault(get_group_fault,'deleteDocmanItem','Could Not Get Group','Could Not Get Group');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'deleteDocmanItem', $group->getErrorMessage(),$group->getErrorMessage());
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'deleteDocmanItem', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
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
            $p->processSOAP($request);
            if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
                   $msg = $GLOBALS['Response']->getRawFeedback();
                   return new soap_fault('', 'deleteDocmanItem', $msg, $msg);
            } else {
                return true;
            }
        } else {
            return new soap_fault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN,'deleteDocmanItem','Unavailable plugin','Unavailable plugin');;
        }
    } else {
        return new soap_fault(invalid_session_fault,'deleteDocmanItem','Invalid Session','');
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
            return new soap_fault(get_group_fault,'monitorDocmanItem','Could Not Get Group','Could Not Get Group');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'monitorDocmanItem', $group->getErrorMessage(),$group->getErrorMessage());
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'monitorDocmanItem', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
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
                   return new soap_fault('', 'monitorDocmanItem', $msg, $msg);
            } else {
                return true;
            }
        } else {
            return new soap_fault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN,'monitor','Unavailable plugin','Unavailable plugin');;
        }
    } else {
        return new soap_fault(invalid_session_fault,'monitorDocmanItem','Invalid Session','');
    }
}

function moveDocmanItem($sessionKey, $group_id, $item_id, $new_parent) {
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'moveDocmanItem','Could Not Get Group','Could Not Get Group');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'moveDocmanItem', $group->getErrorMessage(),$group->getErrorMessage());
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'moveDocmanItem', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
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
                   return new soap_fault('', 'moveDocmanItem', $msg, $msg);
            } else {
                return true;
            }
        } else {
            return new soap_fault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN,'moveDocmanItem','Unavailable plugin','Unavailable plugin');;
        }
    } else {
        return new soap_fault(invalid_session_fault,'moveDocmanItem','Invalid Session','');
    }
}
/**
 * getProperties - returns an array of Docman_Metadata of the document $item_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group the document belongs to
 * @param int $item_id the ID of the document we want to retrieve the properties
 * @return array the array of SOAPDocmanMetadata of the document identified by $item_id, or a soap fault if group_id does not match with a valid project.
 */
function getDocmanItemProperties($sessionKey,$group_id,$item_id) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'getProperties','Could Not Get Group','Could Not Get Group');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'getProperties', $group->getErrorMessage(),$group->getErrorMessage());
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'getProperties', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        
        // TO DO : get the meta datas of a document
        $docman_metadatas = null;
        return docman_metadatas_to_soap($docman_metadatas);
    } else {
        return new soap_fault(invalid_session_fault,'getDocuments','Invalid Session','');
    }
}

/**
 * docman_metadata_to_soap : return the soap Docman_Metadata structure giving a PHP Docman_Metadata Object.
 * @access private
 *
 * @param Object{Docman_Metadata} $docman_metadata the docman_metadata to convert.
 * @return array the SOAPDocman_Metadata corresponding to the Docman_Metadata Object
 */
function docman_metadata_to_soap($docman_metadata) {
    $return = null;
    if ($docman_metadata) {
        //skip if error
    } else {
        $return = array(
            'id' => $docman_metadata->getID(),
            'group_id' => $docman_metadata->getGroupId(),
            'name' => $docman_metadata->getName(),
            'type' => $docman_metadata->getType(),
            'label' => $docman_metadata->getLabel(),
            'description' => $docman_metadata->getDescription(),
            'isRequired' => $docman_metadata->isRequired(),
            'isEmptyAllowed' => $docman_metadata->isEmptyAllowed(),
            'keepHistory' => $docman_metadata->keepHistory(),
            'special' => $docman_metadata->getSpecial(),
            'defaultValue' => $docman_metadata->getDefaultValue(),
            'useIt' => $docman_metadata->getUseIt()
       );
    }
    return $return;
}

function docman_metadatas_to_soap(&$docman_metadata_arr) {
    $return = array();
    foreach ($docman_metadata_arr as $docman_metadata) {
        $return[] = docman_metadata_to_soap($docman_metadata);
    }
    return $return;
}

?>
