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
        'link_url' => array('name'=>'link_url', 'type' => 'xsd:string'),
        'wiki_page' => array('name'=>'wiki_page', 'type' => 'xsd:string'),
        'file_is_embedded' => array('name'=>'file_is_embedded', 'type' => 'xsd:boolean')
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
    'delete',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'item_id'=>'xsd:int'),
    array('deleteResponse'=>'xsd:boolean'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#delete',
    'rpc',
    'encoded',
    'Delete an item (document or folder)'
);
$GLOBALS['server']->register(
    'monitor',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'item_id'=>'xsd:int'),
    array('monitorResponse'=>'xsd:boolean'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#monitor',
    'rpc',
    'encoded',
    'Monitor an item (document or folder)'
);

$GLOBALS['server']->register(
    'getProperties',
    array(
        'sessionKey'=>'xsd:string',
        'group_id'=>'xsd:int',
        'item_id'=>'xsd:int'),
    array('getPropertiesResponse'=>'tns:ArrayOfDocman_Metadata'),
    $GLOBALS['uri'],
    $GLOBALS['uri'].'#getProperties',
    'rpc',
    'encoded',
    'Returns the properties of the document item_id'
);


//
// Function implementation
//

/**
 * 
 */
function delete($sessionKey,$group_id,$item_id) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'delete','Could Not Get Group','Could Not Get Group');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'delete', $group->getErrorMessage(),$group->getErrorMessage());
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'delete', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        
        // TO DO : delete !
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
                   return new soap_fault('', 'delete', $msg, $msg);
            } else {
                return true;
            }
        } else {
            return new soap_fault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN,'delete','Unavailable plugin','Unavailable plugin');;
        }
    } else {
        return new soap_fault(invalid_session_fault,'delete','Invalid Session','');
    }
}

/**
 * 
 */
function monitor($sessionKey,$group_id,$item_id) {
    global $Language;
    if (session_continue($sessionKey)) {
        $group =& group_get_object($group_id);
        if (!$group || !is_object($group)) {
            return new soap_fault(get_group_fault,'delete','Could Not Get Group','Could Not Get Group');
        } elseif ($group->isError()) {
            return new soap_fault(get_group_fault, 'delete', $group->getErrorMessage(),$group->getErrorMessage());
        }
        if (!checkRestrictedAccess($group)) {
            return new soap_fault(get_group_fault, 'delete', 'Restricted user: permission denied.', 'Restricted user: permission denied.');
        }
        
        // TO DO : monitor !
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
                   return new soap_fault('', 'monitor', $msg, $msg);
            } else {
                return true;
            }
        } else {
            return new soap_fault(PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN,'monitor','Unavailable plugin','Unavailable plugin');;
        }
    } else {
        return new soap_fault(invalid_session_fault,'monitor','Invalid Session','');
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
function getProperties($sessionKey,$group_id,$item_id) {
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

