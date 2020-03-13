<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../../../src/www/include/session.php';

// define fault code constants
define('INVALID_ITEM_FAULT', '3017');
define('INVALID_DOCUMENT_FAULT', '3018');
define('INVALID_FOLDER_FAULT', '3019');
define('PLUGIN_DOCMAN_SOAP_FAULT_UNAVAILABLE_PLUGIN', '3020');
define('INVALID_OPERATOR', '3021');

if (defined('NUSOAP')) {
// Type definition
    $GLOBALS['server']->wsdl->addComplexType(
        'Docman_Item',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'item_id' => array('name' => 'item_id', 'type' => 'xsd:int'),
        'parent_id' => array('name' => 'parent', 'type' => 'xsd:int'),
        'group_id' => array('name' => 'group_id', 'type' => 'xsd:int'),
        'title' => array('name' => 'title', 'type' => 'xsd:string'),
        'description' => array('name' => 'description', 'type' => 'xsd:string'),
        'create_date' => array('name' => 'create_date', 'type' => 'xsd:int'),
        'update_date' => array('name' => 'update_date', 'type' => 'xsd:int'),
        'delete_date' => array('name' => 'delete_date', 'type' => 'xsd:int'),
        'user_id' => array('name' => 'user_id', 'type' => 'xsd:int'),
        'status' => array('name' => 'status', 'type' => 'xsd:int'),
        'obsolescence_date' => array('name' => 'obsolescence_date', 'type' => 'xsd:int'),
        'rank' => array('name' => 'rank', 'type' => 'xsd:int'),
        'item_type' => array('name' => 'item_type', 'type' => 'xsd:int'),
        )
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'ArrayOfDocman_Item',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:Docman_Item[]')),
        'tns:Docman_Item'
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'Permission',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'type' => array('name' => 'type', 'type' => 'xsd:string'),
        'ugroup_id' => array('name' => 'ugroup_id', 'type' => 'xsd:int'),
        )
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'ArrayOfPermission',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:Permission[]')),
        'tns:Permission'
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'MetadataValue',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'label' => array('name' => 'label', 'type' => 'xsd:string'),
        'value' => array('name' => 'value', 'type' => 'xsd:string'),
        )
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'ArrayOfMetadataValue',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:MetadataValue[]')),
        'tns:MetadataValue'
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'MetadataListValue',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'id' => array('name' => 'id', 'type' => 'xsd:int'),
        'name'     => array('name' => 'name', 'type' => 'xsd:string'),
        )
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'ArrayOfMetadataListValue',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:MetadataListValue[]')),
        'tns:MetadataListValue'
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'Metadata',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'label' => array('name' => 'label', 'type' => 'xsd:string'),
        'name' => array('name' => 'name', 'type' => 'xsd:string'),
        'type' => array('name' => 'type', 'type' => 'xsd:string'),
        'isMultipleValuesAllowed' => array('name' => 'isMultipleValuesAllowed', 'type' => 'xsd:int'),
        'isEmptyAllowed' => array('name' => 'isEmptyAllowed', 'type' => 'xsd:int'),
        'listOfValues' => array('name' => 'listOfValues', 'type' => 'tns:ArrayOfMetadataListValue'),
        )
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'ArrayOfMetadata',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:Metadata[]')),
        'tns:Metadata'
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'ItemInfo',
        'complexType',
        'struct',
        'sequence',
        '',
        array(
        'id' => array('name' => 'id', 'type' => 'xsd:int'),
        'parent_id' => array('name' => 'parent_id', 'type' => 'xsd:int'),
        'title' => array('name' => 'title', 'type' => 'xsd:string'),
        'filename' => array('name' => 'filename', 'type' => 'xsd:string'),
        'type' => array('name' => 'type', 'type' => 'xsd:string'),
        'nb_versions' => array('name' => 'nb_versions', 'type' => 'xsd:int'),
        )
    );

    $GLOBALS['server']->wsdl->addComplexType(
        'ArrayOfItemInfo',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(array('ref' => 'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:ItemInfo[]')),
        'tns:ItemInfo'
    );
}

/**
 * Returns an array containing all the permissions for the specified item.
 * The ugroups that have no permission defined in the request take the permission of the parent folder.
 */
function _get_permissions_as_array($group_id, $item_id, $permissions)
{
    $permissions_array = array();

    $perms = array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE');

    // Get the ugroups of the parent
    $ugroups = permission_get_ugroups_permissions($group_id, $item_id, $perms, false);

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
function _get_metadata_as_array($metadata)
{
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
function _get_status_value($status)
{
    switch ($status) {
        case 'draft':
            $value = PLUGIN_DOCMAN_ITEM_STATUS_DRAFT;
            break;
        case 'approved':
            $value = PLUGIN_DOCMAN_ITEM_STATUS_APPROVED;
            break;
        case 'rejected':
            $value = PLUGIN_DOCMAN_ITEM_STATUS_REJECTED;
            break;
        default:
            $value = PLUGIN_DOCMAN_ITEM_STATUS_NONE;
            break;
    }

    return $value;
}

/**
 * Returns the user ID corresponding to the given user name, or null if it doesn't exist
 */
function _getUserIdByUserName($userName)
{
    $user = UserManager::instance()->getUserByUserName($userName);
    if ($user == null) {
        return null;
    } else {
        return $user->getId();
    }
}

/**
 * Makes a docman request
 *
 * @param string       $sessionKey   Session Key
 * @param int          $group_id     Group ID
 * @param array        $params       Request parameters
 * @return mixed                   Request response
 */
function _makeDocmanRequest($sessionKey, $group_id, $action, $params = array())
{
    $actor = "_makeDocmanRequest ($action)";

    if (session_continue($sessionKey)) {
        try {
            $pm = ProjectManager::instance();
            $pm->getGroupByIdForSoap($group_id, $actor);
        } catch (SoapFault $e) {
            return $e;
        }

        $params['group_id'] = $group_id;
        $params['action'] = $action;
        $params['confirm'] = true;

        $request = new SOAPRequest($params);

        $plugin_manager = PluginManager::instance();
        $p              = $plugin_manager->getPluginByName('docman');
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
        return new SoapFault(INVALID_SESSION_FAULT, 'Invalid Session', $actor);
    }
}

/**
 * Returns an array containing the common item params needed by docman actions
 */
function _buildItemParams($group_id, $perm_item_id, $title, $description, $status, $type, $permissions, $metadata, $owner, $create_date, $update_date)
{
    $params = array();

    if ($title !== null) {
        $params['item']['title'] = $title;
    }
    if ($description !== null) {
        $params['item']['description'] = $description;
    }
    if ($type !== null) {
        $params['item']['item_type'] = $type;
    }
    if ($status !== null) {
        $params['item']['status'] = _get_status_value($status);
    }
    if ($create_date !== null) {
        $params['item']['create_date'] = $create_date;
    }
    if ($update_date !== null) {
        $params['item']['update_date'] = $update_date;
    }
    if ($owner !== null) {
        $params['item']['owner'] = $owner;
    }
    if ($permissions !== null) {
        $params['permissions'] = _get_permissions_as_array($group_id, $perm_item_id, $permissions);
    }
    if ($metadata !== null) {
        $params['metadata'] = _get_metadata_as_array($metadata);
    }

    return $params;
}

/**
 * This function is like the PHP function array_merge_recursive but prevents returning null when one of the arrays is null
 */
function _safe_array_merge_recursive($array1, $array2)
{
    if ($array1 === null) {
        $array1 = array();
    }
    if ($array2 === null) {
        $array2 = array();
    }
    return array_merge_recursive($array1, $array2);
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
 * @param string       $obsolescence_date Obsolescence date
 * @param string       $type              Type (folder, file, embedded_file, link, empty, wiki)
 * @param Array        $permissions       Permissions
 * @param Array        $metadata          Metadata values
 * @param string       $owner             Owner
 * @param string       $create_date       Create date
 * @param string       $update_date       Update date
 * @param Array        $extraParams       Extra parameters array
 */
function _createDocmanItem($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $type, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams = array())
{
    $params = _buildItemParams($group_id, $parent_id, $title, $description, $status, $type, $permissions, $metadata, $owner, $create_date, $update_date);
    $params['item']['parent_id'] = $parent_id;
    $params['ordering'] = $ordering;

    return _makeDocmanRequest($sessionKey, $group_id, 'createItem', _safe_array_merge_recursive($params, $extraParams));
}

/**
 * Creates a docman document
 */
function _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $type, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams = array())
{
    if ($obsolescence_date !== null) {
        $extraParams['item']['obsolescence_date'] = $obsolescence_date;
    }
    return _createDocmanItem($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $type, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}

/**
 * Updates a docman item
 */
function _updateDocmanItem($sessionKey, $group_id, $item_id, $title, $description, $status, $type, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams = array())
{
    $params = _buildItemParams($group_id, $item_id, $title, $description, $status, $type, $permissions, $metadata, $owner, $create_date, $update_date);
    $params['item']['id'] = $item_id;

    $permParams['id'] = $item_id;
    $permParams['permissions'] = $params['permissions'];
    $result = _makeDocmanRequest($sessionKey, $group_id, 'permissions', $permParams);
    if ($result instanceof SoapFault) {
        return $result;
    }
    $result = _makeDocmanRequest($sessionKey, $group_id, 'update', _safe_array_merge_recursive($params, $extraParams));
    if ($result instanceof SoapFault) {
        return $result;
    }
    return true;
}

/**
 * Updates a docman document
 */
function _updateDocmanDocument($sessionKey, $group_id, $item_id, $title, $description, $status, $obsolescence_date, $type, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams = array())
{
    if ($obsolescence_date !== null) {
        $extraParams['item']['obsolescence_date'] = $obsolescence_date;
    }
    return _updateDocmanItem($sessionKey, $group_id, $item_id, $title, $description, $status, $type, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}
// SOAP function implementations
/**
* Returns the document object that is at the top of the docman given a group object.
*/
function getRootFolder($sessionKey, $group_id)
{
    return _makeDocmanRequest($sessionKey, $group_id, 'getRootFolder');
}
$soapFunctions[] = array('getRootFolder', 'Returns the document object id that is at the top of the docman given a group object');


/**
* Lists the contents of a folder
*/
function listFolder($sessionKey, $group_id, $item_id)
{
    $params = array('id' => $item_id, 'report' => 'List');
    return _makeDocmanRequest($sessionKey, $group_id, 'show', $params);
}
$soapFunctions[] = array('listFolder', 'List folder contents', 'tns:ArrayOfDocman_Item');

function operatorToValue($operator)
{
    if ($operator == '=') {
        return 0;
    } elseif ($operator == '<') {
        return -1;
    } elseif ($operator == '>') {
        return 1;
    }
}

function isValidOperator($operator)
{
    if ($operator == '<' ||
       $operator == '>' ||
       $operator == '=') {
        return true;
    }
    return false;
}

/**
 * Returns all the items that match given criterias
 */
function searchDocmanItem($sessionKey, $group_id, $item_id, $criterias)
{
    $params = array('id' => $item_id);
    foreach ($criterias as $criteria) {
        $params[$criteria->field_name . '_value'] = $criteria->field_value;
        if (!isValidOperator($criteria->operator)) {
            return new SoapFault(INVALID_OPERATOR, 'This operator is not valid. Only <, >, = are valid.', 'searchDocmanItem');
        }
        $params[$criteria->field_name . '_operator']  = operatorToValue($criteria->operator);
    }
    return _makeDocmanRequest($sessionKey, $group_id, 'search', $params);
}
$soapFunctions[] = array('searchDocmanItem', 'Returns all the items that match given criterias', 'tns:ArrayOfDocman_Item');

/**
 * Returns the the content of a file (or embedded file) base64 encoded
 */
function getDocmanFileContents($sessionKey, $group_id, $item_id, $version_number)
{
    $params = array('item_id' => $item_id);
    if ($version_number >= 0) {
        $params['version_number'] = $version_number;
    }
    return _makeDocmanRequest($sessionKey, $group_id, 'getFileContents', $params);
}
$soapFunctions[] = array('getDocmanFileContents', 'Returns the content of a file (or embedded file) base64 encoded. (version_number = -1 means last)', 'xsd:string');

/**
 * Returns the MD5 checksum of the file (last version) corresponding to the provided item ID.
 */
function getDocmanFileMD5sum($sessionKey, $group_id, $item_id, $version_number)
{
    $params = array('item_id' => $item_id, 'version' => $version_number);
    return _makeDocmanRequest($sessionKey, $group_id, 'getFileMD5sum', $params);
}
$soapFunctions[] = array('getDocmanFileMD5sum', 'Returns the MD5 checksum of the file (last version) corresponding to the provided item ID', 'xsd:string');


/**
 * Returns the MD5 checksum of the file (all versions) corresponding to the provided item ID.
 */
function getDocmanFileAllVersionsMD5sum($sessionKey, $group_id, $item_id)
{
    $params = array('item_id' => $item_id, 'all_versions' => true);
    return _makeDocmanRequest($sessionKey, $group_id, 'getFileMD5sum', $params);
}
$soapFunctions[] = array('getDocmanFileAllVersionsMD5sum', 'Returns the MD5 checksum of the file (all versions) corresponding to the provided item ID', 'tns:ArrayOfstring');


/**
 * Returns the metadata of the given project
 */
function getDocmanProjectMetadata($sessionKey, $group_id)
{
    $result = _makeDocmanRequest($sessionKey, $group_id, 'getProjectMetadata');
    if ($result instanceof SoapFault) {
        return $result;
    }

    foreach ($result as &$md) {
        $md->listOfValues = array();
        if ($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $md->listOfValues = _makeDocmanRequest($sessionKey, $group_id, 'getMetadataListOfValues', array('label' => $md->getLabel()));
        }
    }

    return $result;
}
$soapFunctions[] = array('getDocmanProjectMetadata', 'Returns the metadata of the given project', 'tns:ArrayOfMetadata');


/**
 * Returns the tree information of the given project
 */
function getDocmanTreeInfo($sessionKey, $group_id, $parent_id)
{
    return _makeDocmanRequest($sessionKey, $group_id, 'getTreeInfo', array('parent_id' => $parent_id));
}
$soapFunctions[] = array('getDocmanTreeInfo', 'Returns the tree information of the given project', 'tns:ArrayOfItemInfo');


/**
 * Creates a docman folder
 */
function createDocmanFolder($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $permissions, $metadata, $owner, $create_date, $update_date)
{
    return _createDocmanItem($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, $permissions, $metadata, $owner, $create_date, $update_date);
}
$soapFunctions[] = array('createDocmanFolder', 'Create a folder');


/**
 * Creates a docman file
 */
function createDocmanFile($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $permissions, $metadata, $file_size, $file_name, $mime_type, $content, $chunk_offset, $chunk_size, $author, $date, $owner, $create_date, $update_date)
{
    if ((int) $file_size >= (int) ForgeConfig::get(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING)) {
        return new SoapFault(
            INVALID_ITEM_FAULT,
            sprintf('Maximum file size is %s bytes, got %s bytes', ForgeConfig::get(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING), $file_size)
        );
    }

    $content = base64_decode($content);

    if (strlen($content) !== (int) $file_size) {
        return new SoapFault(
            INVALID_ITEM_FAULT,
            sprintf('Expected a file of %s bytes, got a file of %s bytes', $file_size, strlen($content))
        );
    }

    //ignore mime type coming from the client, guess it instead
    //Write the content of the file into a temporary file
    //The best accurate results are got when the file has the real extension, therefore use the filename
    $tmp     = tempnam(ForgeConfig::get('tmp_dir'), 'Mime-detect');
    $tmpname = $tmp . '-' . basename($file_name);
    file_put_contents($tmpname, $content);
    $mime_type = MIME::instance()->type($tmpname);

    //remove both files created by tempnam() and file_put_contents()
    unlink($tmp);
    unlink($tmpname);

    $extraParams = array(
        'chunk_offset'   => $chunk_offset,
        'chunk_size'     => $chunk_size,
        'file_name'      => $file_name,
        'mime_type'      => $mime_type,
        'upload_content' => $content,
        'date'           => $date,
        'author'         => _getUserIdByUserName($author),
    );

    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_FILE, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}
$soapFunctions[] = array('createDocmanFile', 'Creates a docman file');


/**
 * Creates a docman embedded file
 */
function createDocmanEmbeddedFile($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $content, $permissions, $metadata, $author, $date, $owner, $create_date, $update_date)
{
    $extraParams = array(
        'content' => $content,
        'date'    => $date,
        'author'  => _getUserIdByUserName($author),
    );

    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}
$soapFunctions[] = array('createDocmanEmbeddedFile', 'Creates a docman embedded file');


/**
 * Creates a docman wiki page
 */
function createDocmanWikiPage($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $content, $permissions, $metadata, $owner, $create_date, $update_date)
{
    $extraParams['item']['wiki_page'] = $content;
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_WIKI, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}
$soapFunctions[] = array('createDocmanWikiPage', 'Creates a docman wiki page');


/**
 * Creates a docman link
 */
function createDocmanLink($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $content, $permissions, $metadata, $owner, $create_date, $update_date)
{
    $extraParams['item']['link_url'] = $content;
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_LINK, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}
$soapFunctions[] = array('createDocmanLink', 'Creates a docman link');


/**
 * Creates a docman embedded file
 */
function createDocmanEmptyDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, $permissions, $metadata, $owner, $create_date, $update_date)
{
    return _createDocmanDocument($sessionKey, $group_id, $parent_id, $title, $description, $ordering, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, $permissions, $metadata, $owner, $create_date, $update_date);
}
$soapFunctions[] = array('createDocmanEmptyDocument', 'Creates a docman empty document');


/**
 * Creates a docman file version
 */
function createDocmanFileVersion($sessionKey, $group_id, $item_id, $label, $changelog, $file_size, $file_name, $mime_type, $content, $chunk_offset, $chunk_size, $author, $date)
{
    if ((int) $file_size >= (int) ForgeConfig::get(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING)) {
        return new SoapFault(
            INVALID_ITEM_FAULT,
            sprintf('Maximum file size is %s bytes, got %s bytes', ForgeConfig::get(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING), $file_size)
        );
    }

    $content = base64_decode($content);

    if (strlen($content) !== (int) $file_size) {
        return new SoapFault(
            INVALID_ITEM_FAULT,
            sprintf('Expected a file of %s bytes, got a file of %s bytes', $file_size, strlen($content))
        );
    }

    $params = array(
        'id'             => $item_id,
        'version'        => array('label' => $label, 'changelog' => $changelog),
        'upload_content' => $content,
        'chunk_offset'   => $chunk_offset,
        'chunk_size'     => $chunk_size,
        'file_name'      => $file_name,
        'mime_type'      => $mime_type,
        'date'           => $date,
        'author'         => _getUserIdByUserName($author),
    );

    return _makeDocmanRequest($sessionKey, $group_id, 'new_version', $params);
}
$soapFunctions[] = array('createDocmanFileVersion', 'Creates a docman file version');


/**
 * Creates a docman embedded file version
 */
function createDocmanEmbeddedFileVersion($sessionKey, $group_id, $item_id, $label, $changelog, $content, $author, $date)
{
    $params = array(
        'id'        => $item_id,
        'version'   => array('label' => $label, 'changelog' => $changelog,),
        'content'   => $content,
        'date'      => $date,
        'author'    => _getUserIdByUserName($author),
    );

    return _makeDocmanRequest($sessionKey, $group_id, 'new_version', $params);
}
$soapFunctions[] = array('createDocmanEmbeddedFileVersion', 'Creates a docman embedded file version');


/**
 * Appends a chunk of data to the last version of a file
 */
function appendDocmanFileChunk($sessionKey, $group_id, $item_id, $content, $chunk_offset, $chunk_size)
{
    $params = array(
        'item_id'        => $item_id,
        'upload_content' => base64_decode($content),
        'chunk_offset'   => $chunk_offset,
        'chunk_size'     => $chunk_size,
    );

    return _makeDocmanRequest($sessionKey, $group_id, 'appendFileChunk', $params);
}
$soapFunctions[] = array('appendDocmanFileChunk', 'Appends a chunk of data to the last version of a file');


/**
 * Moves an item
 */
function moveDocmanItem($sessionKey, $group_id, $item_id, $new_parent)
{
    return _makeDocmanRequest($sessionKey, $group_id, 'move_here', array('item_to_move' => $item_id, 'id' => $new_parent));
}
$soapFunctions[] = array('moveDocmanItem', 'Moves an item in a new folder', 'xsd:boolean');



/**
 * Download a file given its item_id and version
 */
function getDocmanFileChunk($sessionKey, $group_id, $item_id, $version_number, $chunk_offset, $chunk_size)
{
        $params = array(
        'item_id'        => $item_id,
        'version_number' => $version_number,
        'chunk_offset'   => $chunk_offset,
        'chunk_size'     => $chunk_size,
        );

        return _makeDocmanRequest($sessionKey, $group_id, 'getFileChunk', $params);
}
$soapFunctions[] = array('getDocmanFileChunk', 'Returns a part (chunk) of the content, encoded in base64, ' .
                                               'of the file/embedded file which id item_id of a given version version_number, ' .
                                               'if the version is not specified it will be the current one, in the project group_id.' .
                                               'Returns an error if the group ID does not match with a valid project, or if the item ID ' .
                                               'does not match with the right group ID, or if the version number does not match with the item ID.', 'xsd:string');

/**
 * Deletes a docman item
 */
function deleteDocmanItem($sessionKey, $group_id, $item_id)
{
    return _makeDocmanRequest($sessionKey, $group_id, 'delete', array('id' => $item_id));
}
$soapFunctions[] = array('deleteDocmanItem', 'Delete an item (document or folder)');


/**
 * Enables the monitoring of an item by a user
 */
function monitorDocmanItem($sessionKey, $group_id, $item_id)
{
    return _makeDocmanRequest($sessionKey, $group_id, 'monitor', array('id' => $item_id, 'monitor' => true));
}
$soapFunctions[] = array('monitorDocmanItem', 'Enables the monitoring of an item by a user', 'xsd:boolean');


/**
 * Updates a docman folder
 */
function updateDocmanFolder($sessionKey, $group_id, $item_id, $title, $description, $status, $permissions, $metadata, $owner, $create_date, $update_date)
{
    return _updateDocmanItem($sessionKey, $group_id, $item_id, $title, $description, $status, PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, $permissions, $metadata, $owner, $create_date, $update_date);
}
$soapFunctions[] = array('updateDocmanFolder', 'Updates a docman folder');


/**
 * Updates a docman file
 */
function updateDocmanFile($sessionKey, $group_id, $item_id, $title, $description, $status, $obsolescence_date, $permissions, $metadata, $owner, $create_date, $update_date)
{
    return _updateDocmanDocument($sessionKey, $group_id, $item_id, $title, $description, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_FILE, $permissions, $metadata, $owner, $create_date, $update_date);
}
$soapFunctions[] = array('updateDocmanFile', 'Updates a docman file');


/**
 * Updates a docman embedded file
 */
function updateDocmanEmbeddedFile($sessionKey, $group_id, $item_id, $title, $description, $status, $obsolescence_date, $permissions, $metadata, $owner, $create_date, $update_date)
{
    return _updateDocmanDocument($sessionKey, $group_id, $item_id, $title, $description, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE, $permissions, $metadata, $owner, $create_date, $update_date);
}
$soapFunctions[] = array('updateDocmanEmbeddedFile', 'Updates a docman embedded file');


/**
 * Updates a docman wiki page
 */
function updateDocmanWikiPage($sessionKey, $group_id, $item_id, $title, $description, $status, $obsolescence_date, $content, $permissions, $metadata, $owner, $create_date, $update_date)
{
    if ($content !== null) {
        $extraParams['item']['wiki_page'] = $content;
    }
    return _updateDocmanDocument($sessionKey, $group_id, $item_id, $title, $description, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_WIKI, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}
$soapFunctions[] = array('updateDocmanWikiPage', 'Updates a docman wiki page');


/**
 * Updates a docman link
 */
function updateDocmanLink($sessionKey, $group_id, $item_id, $title, $description, $status, $obsolescence_date, $content, $permissions, $metadata, $owner, $create_date, $update_date)
{
    if ($content !== null) {
        $extraParams['item']['link_url'] = $content;
    }
    return _updateDocmanDocument($sessionKey, $group_id, $item_id, $title, $description, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_LINK, $permissions, $metadata, $owner, $create_date, $update_date, $extraParams);
}
$soapFunctions[] = array('updateDocmanLink', 'Updates a docman link');


/**
 * Updates a docman empty document
 */
function updateDocmanEmptyDocument($sessionKey, $group_id, $item_id, $title, $description, $status, $obsolescence_date, $permissions, $metadata, $owner, $create_date, $update_date)
{
    return _updateDocmanDocument($sessionKey, $group_id, $item_id, $title, $description, $status, $obsolescence_date, PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, $permissions, $metadata, $owner, $create_date, $update_date);
}
$soapFunctions[] = array('updateDocmanEmptyDocument', 'Updates a docman empty document');


// Functions registering
if (defined('NUSOAP')) {
    // Soap parameters definition
    $GLOBALS['soapParameters'] = array(
                      'sessionKey'        => array('xsd:string', 'Session key'),
                      'group_id'          => array('xsd:int', 'Group ID'),
                      'parent_id'         => array('xsd:int', 'Parent ID'),
                      'item_id'           => array('xsd:int', 'item ID'),
                      'title'             => array('xsd:string', 'Title'),
                      'description'       => array('xsd:string', 'Description'),
                      'ordering'          => array('xsd:string', 'Ordering (begin, end)'),
                      'status'            => array('xsd:string', 'Status (none, draft, approved, rejected)'),
                      'obsolescence_date' => array('xsd:string', 'Obsolescence date (timestamp)'),
                      'content'           => array('xsd:string', 'Content'),
                      'permissions'       => array('tns:ArrayOfPermission', 'Permissions'),
                      'metadata'          => array('tns:ArrayOfMetadataValue', 'Metadata values'),
                      'owner'             => array('xsd:string', 'Owner of the item'),
                      'create_date'       => array('xsd:string', 'Item creation date (timestamp)'),
                      'update_date'       => array('xsd:string', 'Item update date (timestamp)'),
                      'author'            => array('xsd:string', 'Version author'),
                      'date'              => array('xsd:string', 'Version date (timestamp)'),
                      'label'             => array('xsd:string', 'version label'),
                      'changelog'         => array('xsd:string', 'Version changelog'),
                      'file_size'         => array('xsd:int', 'File size'),
                      'file_name'         => array('xsd:string', 'File name'),
                      'mime_type'         => array('xsd:string', 'Mime type'),
                      'chunk_offset'      => array('xsd:int', 'Chunk offset'),
                      'chunk_size'        => array('xsd:int', 'Chunk size'),
                      'new_parent'        => array('xsd:int', 'New parent ID'),
                      'criterias'         => array('tns:ArrayOfCriteria', 'Criteria'),
                      'version_number'    => array('xsd:int', 'Version number'),
                  );
}
/**
 * Registers a function on the soap server. The parameters of the function are retrieved by reflexion.
 */
function _registerFunction($name, $doc, $response = 'xsd:int')
{
    if (defined('NUSOAP')) {
        // WSDL generation
        $function = new ReflectionFunction($name);
        $parameters = $function->getParameters();

        $usedParameters = array();
        foreach ($parameters as $parameter) {
            $usedParameters[] = $parameter->getName();
        }

        $soapParameters = $GLOBALS['soapParameters'];

        $parameters = array();
        $paramsDoc = '<pre>';

        foreach ($usedParameters as $usedParameter) {
            $parameters[$usedParameter] = $soapParameters[$usedParameter][0];
            $paramsDoc .= str_pad($usedParameter, 20) . $soapParameters[$usedParameter][1] . '<br/>';
        }

        $paramsDoc .= '</pre>';

        $GLOBALS['server']->register(
            $name,
            $parameters,
            array($name . 'Response' => $response),
            $GLOBALS['uri'],
            $GLOBALS['uri'] . '#' . $name,
            'rpc',
            'encoded',
            "$doc $paramsDoc"
        );
    } else {
        $GLOBALS['server']->addFunction($name);
    }
}

/**
 * Registers all the functions defined in the $soapFunctions array
 */
function _registerFunctions($functions)
{
    if (is_array($functions)) {
        foreach ($functions as $function) {
            if (isset($function[2])) {
                _registerFunction($function[0], $function[1], $function[2]);
            } else {
                _registerFunction($function[0], $function[1]);
            }
        }
    }
}

_registerFunctions($soapFunctions);
