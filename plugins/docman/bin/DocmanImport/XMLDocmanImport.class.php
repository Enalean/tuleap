<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class MultiplePathFoundException extends LogicException {}

define('PLUGIN_DOCMAN_METADATA_TYPE_TEXT', 1);
define('PLUGIN_DOCMAN_METADATA_TYPE_STRING', 6);
define('PLUGIN_DOCMAN_METADATA_TYPE_DATE', 4);
define('PLUGIN_DOCMAN_METADATA_TYPE_LIST', 5);

class XMLDocmanImport {
    const CHILDREN_ONLY   = 2;
    const PARENT_CHILDREN = 3;

    private $groupId;

    private $dataBaseDir;
    private $doc;

    // Metadata map
    private $metadataMap;

    // Group map
    private $ugroupMap;

    private $soap;
    private $hash;

    /**
     * XMLDocmanImport constructor
     *
     * @param int    $groupId  Group ID
     * @param string $wsdl     WSDL location
     * @param string $login    Login
     * @param string $password Password
     */
    public function __construct($groupId, $wsdl, $login, $password) {
        $this->groupId = $groupId;
        $this->metadataMap = array();
        $this->ugroupMap = array();

        // Disable the cache for testing purposes. TODO re-enable the cache for production.
        ini_set("soap.wsdl_cache_enabled", "0");

        try {
            $this->soap = new SoapClient($wsdl, array('trace' => true));
            $this->hash = $this->soap->login($login, $password)->session_hash;
        } catch (Exception $e) {
            $this->printSoapErrorAndDie($e);
        }

        echo "Connected to $wsdl as $login.".PHP_EOL;
    }

    /**
     * Retrieves the ugroups of this project, and build the map that make the correspondance between
     * the group name and the group ID. The members are also retrieved.
     */
    private function buildUgroupMap () {
        echo "Retrieving ugroups... ";
        try {
            $ugroups = $this->soap->getGroupUgroups($this->hash, $this->groupId);
            foreach ($ugroups as $ugroup) {
                if ($this->doc->xpath("/docman/ugroups/ugroup[@name='$ugroup->name']")) {
                    $this->ugroupMap[$ugroup->name]['ugroup_id'] = $ugroup->ugroup_id;
                    $this->ugroupMap[$ugroup->name]['members'] = array();
                    foreach ($ugroup->members as $member) {
                        $this->ugroupMap[$ugroup->name]['members'][$member->user_name] = $member->user_id;
                    }
                }
            }
        } catch (Exception $e) {
            $this->printSoapErrorAndDie($e);
        }

        echo "Done.".PHP_EOL;
    }

    /**
     * Retrieve the metadata of this project, and build the map that make the correspondance between
     * the metadata name and the metadata label. For list of values, the values are also retrieved.
     */
    private function buildMetadataMap() {
        echo "Retrieving metadata definition... ";
        try {
            $metadataList = $this->soap->getDocmanProjectMetadata($this->hash, $this->groupId);

            foreach ($metadataList as $metadata) {
                if ($this->doc->xpath("/docman/propdefs/propdef[@name='$metadata->name']")) {
                    $this->metadataMap[$metadata->name]['label'] = $metadata->label;
                    $this->metadataMap[$metadata->name]['type'] = $metadata->type;
                    $this->metadataMap[$metadata->name]['isEmptyAllowed'] = $metadata->isEmptyAllowed;
                    if ($metadata->type == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                        $this->metadataMap[$metadata->name]['isMultipleValuesAllowed'] = $metadata->isMultipleValuesAllowed;
                        $lov = $metadata->listOfValues;
                        foreach ($lov as $val) {
                            if ($val->id != 100) {
                                $this->metadataMap[$metadata->name]['values'][$val->name] = $val->id;
                            }
                        }
                    }
                } else if (!$metadata->isEmptyAllowed){
                    $missingProp[] = $metadata->name;
                }
            }
        } catch (Exception $e) {
            $this->printSoapErrorAndDie($e);
        }

        if (isset($missingProp)) {
            $this->exitError("The following propert".((count($missingProp) > 1)? "ies don't": "y doesn't")." allow empty values and must be defined in the <propdefs> node: ".implode(", ", $missingProp).PHP_EOL);
        }

        echo "Done.".PHP_EOL;
    }

    private function printSoapErrorAndDie(Exception $e) {
        //print_r($e);
        echo "Response:".PHP_EOL.$this->soap->__getLastResponse().PHP_EOL;
        $this->exitError($e->getMessage());
    }

    /**
     * Loads and checks an XML document
     */
    private function loadXML($rootPath) {
        $archiveName = basename($rootPath);
        $this->dataBaseDir = $rootPath.'/'.$archiveName;

        // DTD validation
        $dom = new DOMDocument();
        $dom->load($rootPath.'/'.$archiveName.'.xml');
        if (!$dom->validate()) {
            $this->warn("DTD Validation failed.");
        }

        $this->doc = simplexml_import_dom($dom);

        // Build the maps
        $this->buildMetadataMap();
        $this->buildUgroupMap();

        // Sanity checks
        echo "Checking the XML document... ";
        $this->checkMetadataDefinition();
        $this->checkMetadataUsage();
        $this->checkUgroupDefinition();
        $this->checkUgroupsUsage();
        echo "Done.".PHP_EOL;
    }

    /**
     * Checks if the metadata used in the XML document reference metadata defined in the propdefs node of the document
     */
    private function checkMetadataUsage() {
        $propertyList = $this->doc->xpath('//item/properties/property');

        // Check the values set to the properties
        foreach ($propertyList as $property) {
            $item_nodes = $property->xpath('../..');
            $item_name = $item_nodes[0]->properties->title;
            $title = (string)$property['title'];
             
            if (isset($this->metadataMap[$title])) {
                $metadataDef =  $this->metadataMap[$title];
                $type = $metadataDef['type'];
                switch ($type) {
                    case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                        $value = (string)$property;
                        $match = preg_match('/^([0-9]+)-([0-9]+)-([0-9]+)$/', $value);
                        if (!$match) {
                            $errorMsg .= "Item '$item_name':\tThe property '$title' set to the value '$value' is a date and must follow the format YYYY-MM-DD.".PHP_EOL;
                        }
                        break;
                    case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                        $values = $property->value;

                        // Check if the defined values exist
                        foreach ($values as $value) {
                            if (!isset($metadataDef['values'][(string)$value])) {
                                $errorMsg .= "Item '$item_name':\tThe list property '$title' is set to an incorrect value: '$value'".PHP_EOL;
                            }
                        }

                        // Check that just one value is given to a list that allow only one value
                        if (count($values) > 1 && !$metadataDef['isMultipleValuesAllowed']) {
                            $errorMsg .= "Item '$item_name':\tThe list property '$title' allows only one value, but ".count($values)." values are given.".PHP_EOL;
                        }
                        // Check if no value is given to a list that require a value
                        else if (count($values) == 0 && !$metadataDef['isEmptyAllowed']) {
                            $errorMsg .= "Item '$item_name':\tThe list property '$title' is required, but no <value> element is found.".PHP_EOL;
                        }
                }
            } else {
                $errorMsg .= "Item '$item_name':\tThe property '$title' is not defined in a 'propdef' element.".PHP_EOL;
            }
        }

        // Check if no value is set to a property that require a value
        $propertiesList = $this->doc->xpath("//item[@type!='folder']/properties");

        // Build the list of all required properties
        $requiredProperties = array();
        foreach ($this->metadataMap as $metadataName => $metadataDef) {
            if (!$metadataDef['isEmptyAllowed']) {
                $requiredProperties[] = $metadataName;
            }
        }

        // Iterate over the required properties
        foreach ($requiredProperties as $requiredProperty) {
            // Iterate over the <properties> nodes (folders are not included)
            foreach ($propertiesList as $properties) {
                $item_name = $properties->title;
                $searchedProperty = $properties->xpath("property[@title='$requiredProperty']");
                if (count($searchedProperty) == 0) {
                    $errorMsg .= "Item '$item_name':\tThe required property '$requiredProperty' is not set.".PHP_EOL;
                } else if ((string)$searchedProperty[0] == '') {
                    $errorMsg .= "Item '$item_name':\tThe required property '$requiredProperty' is set to an empty value.".PHP_EOL;
                }
            }
        }

        if (isset($errorMsg)) {
            $this->exitError($errorMsg);
        }
    }

    /**
     * Checks if the permissions defined in the XML document reference ugroups defined in the ugroups node of the document
     */
    private function checkUgroupsUsage() {
        $permissions = $this->doc->xpath('//item/permissions/permission');

        foreach ($permissions as $permission) {
            $ugroup_name = (string)$permission['ugroup'];

            if (!isset($this->ugroupMap[$ugroup_name])) {
                $item_nodes = $permission->xpath('../..');
                $item_name = $item_nodes[0]->properties->title;

                $errorMsg .= "Item '$item_name':\tThe permission references an undefined group: '$ugroup_name'".PHP_EOL;
            }
        }

        if (isset($errorMsg)) {
            $this->exitError($errorMsg);
        }
    }

    /**
     * Checks the definition of the ugroups
     */
    private function checkUgroupDefinition() {
        $ugroups = $this->doc->xpath('/docman/ugroups/ugroup');

        foreach ($ugroups as $ugroup) {
            $name = (string)$ugroup['name'];

            if (isset($this->ugroupMap[$name])) {
                foreach ($ugroup->member as $member) {
                    $user_name = (string)$member;
                    $members[] = $user_name;
                    if (!isset($this->ugroupMap[$name]['members'][$user_name])) {
                        $this->warn("The user '$user_name' is not a member of the ugroup '$name' on the server.");
                    }
                }

                foreach ($this->ugroupMap[$name]['members'] as $user_name => $member) {
                    if (!in_array($user_name, $members)) {
                        $this->warn("The user '$user_name' is a member of the ugroup '$name' on the server, but he's not inside the ugroup definition in the XML document.");
                    }
                }
            } else {
                $errorMsg .= "The ugroup '$name' doesn't exist on the server.".PHP_EOL;
            }
        }

        if (isset($errorMsg)) {
            $this->exitError($errorMsg);
        }
    }

    /**
     * Checks the metadata definitions. The definitions in the XML document must correspond on the server.
     * The type is checked using the metadata name. If this is a list, the allowed values are also checked.
     */
    private function checkMetadataDefinition() {
        // Retrieve the metadata definition in the XML document
        $propdefs = $this->doc->xpath('/docman/propdefs/propdef');

        foreach ($propdefs as $propdef) {
            $name = (string)$propdef['name'];

            $type = self::typeStringToCode((string)$propdef['type']);
             
            // Check if the metadata type is the same in the XML document and on the server
            if ($type == $this->metadataMap[$name]['type']) {
                if ($type == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                    $values = array();
                    foreach ($propdef->value as $value) {
                        $values[] = (string)$value;
                    }

                    $server_values = array_keys($this->metadataMap[$name]['values']);
                    if ($values != $server_values) {
                        $diff1 = array_diff($values, $server_values);
                        $diff2 = array_diff($server_values, $values);
                        $errorMsg = "The property '$name' doesn't declare the same list of values as in the server:".PHP_EOL;
                        if (count($diff1)) {
                            $errorMsg .= "\tIncorrect:\t".implode(', ', $diff1).PHP_EOL;
                        }
                        if (count($diff2)) {
                            $errorMsg .= "\tMissing:\t".implode(', ', $diff2).PHP_EOL;
                        }
                    }
                }
            } else if ($type === null) {
               $errorMsg .= "The property '$name' has an incorrect type: '".$propdef['type']."' should be '".self::typeCodeToString($this->metadataMap[$name]['type'])."'".PHP_EOL; 
            } else {
                $errorMsg .= "The property '$name' has not the same type as in the server: '".$propdef['type']."' should be '".self::typeCodeToString($this->metadataMap[$name]['type'])."'".PHP_EOL;
            }

            // Check if the metadata value can be empty
            $empty = (string)$propdef['empty'];
            // 'true' is the default value if nothing is specified
            if (($empty == 'true' || $empty == null) && !$this->metadataMap[$name]['isEmptyAllowed']) {
                $errorMsg .= "The property '$name' doesn't allow empty values. Please set the attribute empty to \"false\" to the corresponding propdef element.".PHP_EOL;
            } else if ($empty == 'false' && $this->metadataMap[$name]['isEmptyAllowed']) {
                $errorMsg .= "The property '$name' allows empty values. Please set the attribute empty to \"true\", or remove this attribute (\"true\" is implicit).".PHP_EOL;
            }

            // Check if multiple values are allowed
            if ($type == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                $multival = (string)$propdef['multivalue'];
                // 'false' is the default value if nothing is specified
                if (($multival == 'false' || $multival == null) && $this->metadataMap[$name]['isMultipleValuesAllowed']) {
                    $errorMsg .= "The property '$name' allows multiple values. Please set the attribute multivalue to \"true\" to the corresponding propdef element.".PHP_EOL;
                } else if ($multival == 'true' && !$this->metadataMap[$name]['isMultipleValuesAllowed']) {
                    $errorMsg .= "The property '$name' deosn't allow multiple values. Please set the attribute empty to \"false\", or remove this attribute (\"false\" is implicit).".PHP_EOL;
                }
            }
        }

        // Check that no metadata defined on the server is forgotten in this definition

        if (isset($errorMsg)) {
            $this->exitError($errorMsg);
        }
    }

    /**
     * Converts a type code to a string
     */
    private static function typeCodeToString($type) {
        switch ($type) {
            case PLUGIN_DOCMAN_METADATA_TYPE_STRING: return 'string';
            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:   return 'text';
            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:   return 'date';
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:   return 'list';
            default:                                 return null;
        }
    }

    /**
     * Converts a string to a type code
     */
    private static function typeStringToCode($type) {
        switch ($type) {
            case 'string': return PLUGIN_DOCMAN_METADATA_TYPE_STRING;
            case 'text':   return PLUGIN_DOCMAN_METADATA_TYPE_TEXT;
            case 'date':   return PLUGIN_DOCMAN_METADATA_TYPE_DATE;
            case 'list':   return PLUGIN_DOCMAN_METADATA_TYPE_LIST;
            default:       return null;
        }
    }

    /**
     * Displays an error and terminates execution of the script
     */
    private function exitError($error) {
        exit (PHP_EOL."Fatal error: ".PHP_EOL.$error.PHP_EOL);
    }

    private function warn($msg) {
        echo "Warning: $msg".PHP_EOL;
    }

    /**
     * Import all the items to the specified parent folder
     */
    public function import($xmlDoc, $parentId) {
        $this->loadXML($xmlDoc);

        $nodes = $this->doc->xpath('/docman/item');
        foreach ($nodes as $node) {
            $this->recurseOnNode($node, $parentId);
        }
    }

    /**
     * Import an item to the specified parent folder
     */
    public function importPath($xmlDoc, $parentId, $path, $opt=self::CHILDREN_ONLY) {
        $this->loadXML($xmlDoc);

        $rootNode = $this->findPath($path);
        if ($rootNode instanceof SimpleXMLElement) {
            if($opt == self::CHILDREN_ONLY) {
                foreach($rootNode->xpath('item') as $child) {
                    $this->recurseOnNode($child, $parentId);
                }
            } else {
                $this->recurseOnNode($rootNode, $parentId);
            }
        }
    }

    /**
     * Returns the node that corresponds to the given unix-like path
     */
    private function findPath($path) {
        // Transform "Unix path" to XPath
        $xpath = '/docman/item[properties/title="'.str_replace('/','"]/item[properties/title="', $path).'"]';
        $nodeList = $this->doc->xpath($xpath);
        if($nodeList !== false && count($nodeList) == 1) {
            return $nodeList[0];
        } else {
            throw new MultiplePathFoundException("$path ($xpath) found more than one target element.");
        }
    }

    private function raiseImportError(SimpleXMLElement $node, $parentId) {
        $type  = strtoupper((string) $node['type']);
        $title = (string) $node->properties->title;
        $error = "<strong>$type CREATION FAILURE: '$title' into $parentId</strong>".PHP_EOL;
        file_put_contents('import_error.log', $error, FILE_APPEND);
        echo $error;
    }

    private function raiseImportVersionError($fileName, $itemId, $parentId) {
        $error = "<strong>VERSION CREATION FAILURE: '$fileName' of $itemId in $parentId</strong>".PHP_EOL;
        file_put_contents('import_error.log', $error, FILE_APPEND);
    }

    /**
     * Processes the given node and its childs
     *
     * @param SimpleXMLElement $node     The node to process
     * @param int              $parentId The parent folder ID where the node will be imported
     */
    private function recurseOnNode(SimpleXMLElement $node, $parentId) {
        // Static metadata
        $title            = (string) $node->properties->title;
        $description      = isset($node->properties->description)       ? (string) $node->properties->description       : '';
        $status           = isset($node->properties->status)            ? (string) $node->properties->status            : '';
        $obsolescenceDate = isset($node->properties->obsolescence_date) ? (string) $node->properties->obsolescence_date : '0';
        $ordering         = 'end';
        $owner            = (string) $node->properties->owner;

        // Dynamic metadata
        $metadata = array();
        foreach($node->xpath('properties/property') as $property) {
            $this->extractOneMetadata($property, $metadata);
        }

        // Permissions
        $permissions = array();
        foreach($node->xpath('permissions/permission') as $permission) {
            $this->extractOnePermission($permission, $permissions);
        }

        switch($node['type']) {
            case 'file':
                $iFiles = 0;
                $itemId = false;

                foreach($node->xpath('versions/version') as $version) {
                    $file      = (string)$version->content;
                    $label     = (string)$version->label;
                    $changeLog = (string)$version->changelog;
                    $fileName  = (string)$version->filename;
                    $fileType  = (string)$version->filetype;
                    $author    = (string)$version->author;
                    $date      = (string)$version->date;

                    if($iFiles == 0) {
                        // First version
                        $itemId = $this->createFile($parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $file, $fileName, $fileType, $author, $date, $owner);
                    } else {
                        if($itemId !== false) {
                            // Update
                            $this->createFileVersion($itemId, $label, $changeLog, $file, $fileName, $fileType, $author, $date);
                        }
                    }
                    $iFiles++;
                }
                break;

            case 'embeddedfile':
                $iFiles = 0;
                $itemId = false;
                foreach($node->xpath('versions/version') as $version) {
                    $file      = (string)$version->content;
                    $label     = (string)$version->label;
                    $changeLog = (string)$version->changelog;
                    $author    = (string)$version->author;
                    $date      = (string)$version->date;

                    if($iFiles == 0) {
                        // First version
                        $itemId = $this->createEmbeddedFile($parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $file, $author, $date, $owner);
                    } else {
                        if($itemId !== false) {
                            // Update
                            $this->createEmbeddedFileVersion($itemId, $label, $changeLog, $file, $author, $date);
                        }
                    }
                    $iFiles++;
                }
                break;

            case 'wiki':
                $pagename = (string) $node->pagename;
                $this->createWiki($parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $pagename, $owner);
                break;

            case 'link':
                $url = (string) $node->url;
                $this->createLink($parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $url, $owner);
                break;

            case 'empty':
                $this->createEmpty($parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $owner);
                break;

            case 'folder':
                $newParentId = $this->createFolder($parentId, $title, $description, $ordering, $status, $permissions, $metadata, $owner);
                foreach($node->xpath('item') as $child) {
                    $this->recurseOnNode($child, $newParentId);
                }
                break;

            default:
                //error
        }
    }

    /**
     * Extract real metadata information in one <property> node
     */
    private function extractOneMetadata(SimpleXMLElement $property, array &$metadata) {
        $propTitle = (string)$property['title'];
        $dstMetadataLabel = $this->metadataMap[$propTitle]['label'];
        $values = $property->xpath('value');
        if($values !== false && count($values) > 0) {
            foreach($values as $value) {
                $val = (string) $value;
                if($val == 'None') {
                    $metadata[] = array('label' => $dstMetadataLabel, 'value' => '100');
                } else {
                    $metadata[] = array('label' => $dstMetadataLabel, 'value' => $this->metadataMap[$propTitle]['values'][$val]);
                }
            }
        } else {
            $metadata[] = array('label' => $dstMetadataLabel, 'value' => (string) $property);
        }
    }

    /**
     * Extract a permission in one <permission> node
     */
    private function extractOnePermission(SimpleXMLElement $permission, array &$permissions) {
        $ugroupName = (string)$permission['ugroup'];
        switch ((string)$permission) {
            case 'read':    $type = 'PLUGIN_DOCMAN_READ'; break;
            case 'write':   $type = 'PLUGIN_DOCMAN_WRITE'; break;
            case 'manage':  $type = 'PLUGIN_DOCMAN_MANAGE'; break;
            case 'none':
            default:        $type = 'PLUGIN_DOCMAN_NONE';
        }

        $permissions[] = array('ugroup_id' => $this->ugroupMap[$ugroupName]['ugroup_id'], 'type' => $type);
         
    }

    /**
     * Creates a folder
     */
    private function createFolder($parentId, $title, $description, $ordering, $status, array $permissions, array $metadata, $owner) {
        echo "Folder            '$title'";

        try {
            $id = $this->soap->createDocmanFolder($this->hash, $this->groupId, $parentId, $title, $description, $ordering, $status, $permissions, $metadata, $owner);
            echo " #$id".PHP_EOL;
            return $id;
        } catch (Exception $e){
            $this->printSoapErrorAndDie($e);
        }
    }

    /**
     * Creates an empty document
     */
    private function createEmpty($parentId, $title, $description, $ordering, $status, $obsolescenceDate, array $permissions, array $metadata, $owner) {
        echo "Empty document    '$title'";

        try {
            $id = $this->soap->createDocmanEmptyDocument($this->hash, $this->groupId, $parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $owner);
            echo " #$id".PHP_EOL;
            return $id;
        } catch (Exception $e){
            $this->printSoapErrorAndDie($e);
        }
    }

    /**
     * Creates a wiki page
     */
    private function createWiki($parentId, $title, $description, $ordering, $status, $obsolescenceDate, array $permissions, array $metadata, $pagename, $owner) {
        echo "Wikipage          '$title' ($pagename)";

        try {
            $id = $this->soap->createDocmanWikiPage($this->hash, $this->groupId, $parentId, $title, $description, $ordering, $status, $obsolescenceDate, $pagename, $permissions, $metadata, $owner);
            echo " #$id".PHP_EOL;
            return $id;
        } catch (Exception $e){
            $this->printSoapErrorAndDie($e);
        }
    }

    /**
     * Creates a link
     */
    private function createLink($parentId, $title, $description, $ordering, $status, $obsolescenceDate, array $permissions, array $metadata, $url, $owner) {
        echo "Link              '$title' ($url)";

        try {
            $id = $this->soap->createDocmanLink($this->hash, $this->groupId, $parentId, $title, $description, $ordering, $status, $obsolescenceDate, $url, $permissions, $metadata, $owner);
            echo " #$id".PHP_EOL;
            return $id;
        } catch (Exception $e){
            $this->printSoapErrorAndDie($e);
        }
    }

    /**
     * Creates an embedded file
     */
    private function createEmbeddedFile($parentId, $title, $description, $ordering, $status, $obsolescenceDate, array $permissions, array $metadata, $file, $author, $date, $owner) {
        echo "Embedded file     '$title' ($file)";
        $fullPath = $this->dataBaseDir.'/'.$file;
        $contents = file_get_contents($fullPath);

        try {
            $id = $this->soap->createDocmanEmbeddedFile($this->hash, $this->groupId, $parentId, $title, $description, $ordering, $status, $obsolescenceDate, $contents, $permissions, $metadata, $author, $date, $owner);
            echo " #$id".PHP_EOL;
            return $id;
        } catch (Exception $e){
            $this->printSoapErrorAndDie($e);
        }
    }

    /**
     * Creates a file
     */
    private function createFile($parentId, $title, $description, $ordering, $status, $obsolescenceDate, array $permissions, array $metadata, $file, $fileName, $fileType, $author, $date, $owner) {
        $infoStr = "File              '$title' ($file, $fileName, $fileType)";

        $fullPath = $this->dataBaseDir.'/'.$file;
        $fileSize = filesize($fullPath);
        
        // The following is inspired from CLI_Action_Docman_CreateFile.class.php
        $chunk_size = 6000000; // ~6 Mo

        // How many chunks do we have to send
        if ($fileSize == 0) {
            $chunk_count = 1;
        } else {
            $chunk_count = ceil($fileSize / $chunk_size);
        }

        for ($chunk_offset = 0; $chunk_offset < $chunk_count; $chunk_offset++) {
            // Display progression indicator
            echo "\r$infoStr ". intval($chunk_offset / $chunk_count * 100) ."%";

            // Retrieve the current chunk of the file
            $contents = base64_encode(file_get_contents($fullPath, null, null, $chunk_offset * $chunk_size, $chunk_size));

            // Send the chunk
            if (!$chunk_offset) {
                // If this is the first chunk, then use the original soapCommand...
                try {
                    $item_id = $this->soap->createDocmanFile($this->hash, $this->groupId, $parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $fileSize, $fileName, $fileType, $contents, $chunk_offset, $chunk_size, $author, $date, $owner);
                } catch (Exception $e){
                    $this->printSoapErrorAndDie($e);
                }
            } else {
                try {
                    // If this is not the first chunk, then we have to append the chunk
                    $this->soap->appendDocmanFileChunk($this->hash, $this->groupId, $item_id, $contents, $chunk_offset, $chunk_size);
                } catch (Exception $e){
                    $this->printSoapErrorAndDie($e);
                }
            }
        }
        // Finish!
        echo "\r$infoStr #$item_id".PHP_EOL;

        // Check that the local and remote file are the same
        try {
            if ($this->checkChecksum($item_id, $fullPath) === true) {
                return $item_id;
            } else {
                echo "ERROR: Checksum error".PHP_EOL;
                return false;
            }
        } catch (Exception $e){
            $this->printSoapErrorAndDie($e);
        }
    }

    /**
     * Compares the local and the distant checksums
     *
     * @return true if they are the same
     */
    private function checkChecksum($item_id, $filename) {

        $local_checksum = md5_file($filename);

        // For very big files, the checksum can take several minutes to be computed, so we set the socket timeout to 10 minutes
        $default_socket_timeout = ini_set('default_socket_timeout', 600);

        $distant_checksum = $this->soap->getDocmanFileMD5sum($this->hash, $this->groupId, $item_id);

        // Revert default_socket_timeout
        if ($default_socket_timeout !== false) {
            ini_set('default_socket_timeout', $default_socket_timeout);
        }

        return $local_checksum == $distant_checksum;
    }

    /**
     * Create a new file version
     */
    private function createFileVersion($itemId, $label, $changeLog, $file, $fileName, $fileType, $author, $date) {
        $infoStr = "                      Version '$label' ($file, $fileName, $fileType)";

        $fullPath = $this->dataBaseDir.'/'.$file;
        $fileSize = filesize($fullPath);

        // The following is inspired from CLI_Action_Docman_CreateFile.class.php
        $chunk_size = 6000000; // ~6 Mo

        // How many chunks do we have to send
        $chunk_count = ceil($fileSize / $chunk_size);

        for ($chunk_offset = 0; $chunk_offset < $chunk_count; $chunk_offset++) {
            // Display progression indicator
            echo "\r$infoStr ". intval($chunk_offset / $chunk_count * 100) ."%";

            // Retrieve the current chunk of the file
            $contents = base64_encode(file_get_contents($fullPath, null, null, $chunk_offset * $chunk_size, $chunk_size));

            // Send the chunk
            if (!$chunk_offset) {
                // If this is the first chunk, then use the original soapCommand...
                try {
                    $this->soap->createDocmanFileVersion($this->hash, $this->groupId, $itemId, $label, $changeLog, $fileSize, $fileName, $fileType, $contents, $chunk_offset, $chunk_size, $author, $date);
                } catch (Exception $e){
                    $this->printSoapErrorAndDie($e);
                }

            } else {
                // If this is not the first chunk, then we have to append the chunk
                try {
                    $this->soap->appendDocmanFileChunk($this->hash, $this->groupId, $itemId, $contents, $chunk_offset, $chunk_size, $version);
                } catch (Exception $e){
                    $this->printSoapErrorAndDie($e);
                }
            }
        }
        // Finish!
        echo "\r$infoStr     ".PHP_EOL;

        // Check that the local and remote file are the same
        try {
            if ($this->checkChecksum($itemId, $fullPath) === true) {
                return true;
            } else {
                echo "ERROR: Checksum error".PHP_EOL;
                return false;
            }
        } catch (Exception $e){
            $this->printSoapErrorAndDie($e);
        }
    }

    private function createEmbeddedFileVersion($itemId, $label, $changeLog, $file, $author, $date) {
        echo "                      Version '$label' ($file)".PHP_EOL;
        $fullPath = "$this->dataBaseDir/$file";

        $contents = file_get_contents($fullPath);

        try {
            $this->soap->createDocmanEmbeddedFileVersion($this->hash, $this->groupId, $itemId, $label, $changeLog, $contents, $author, $date);
        } catch (Exception $e){
            $this->printSoapErrorAndDie($e);
        }
    }
}
?>