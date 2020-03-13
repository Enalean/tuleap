<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class XMLDocmanImport
{

    // Directory where content files are located
    protected $dataBaseDir;

    // Metadata map
    private $metadataMap = array();

    // List of hardcoded metadata enabled on the target project
    private $hardCodedMetadata = array();

    // Group map
    private $ugroupMap = array(
        100 => array(
            'ugroup_name' => 'nobody',
            'members' => array(),
        ),
        1 => array(
            'ugroup_name' => 'all_users',
            'members' => array(),
        ),
        2 => array(
            'ugroup_name' => 'registered_users',
            'members' => array(),
        ),
        3 => array(
            'ugroup_name' => 'project_members',
            'members' => array(),
        ),
        4 => array(
            'ugroup_name' => 'project_admins',
            'members' => array(),
        ),
    );

    // User map (identifier => "unix" user name)
    private $userMap = array();

    // ID of the project
    protected $groupId;

    // XML document
    protected $doc;

    // Soap client
    protected $soap;

    // Session hash
    protected $hash;

    // When force is true, continue even if some users don't exist on the remote server
    private $force;

    // Whether the items will be reordered or not (folder, docs - alphabetical)
    protected $reorder;

    // The import messages will be appended in the following metadata
    protected $importMessageMetadata;

    // If true: in case of error, retry 5 times
    protected $autoRetry;

    protected $retryCounter;

    protected $logger;

    // File to be uploaded chunk size
    private $chunk_size = 10000000; // ~10MB

    /**
     * XMLDocmanImport constructor
     *
     * @param int    $groupId  Group ID
     * @param string $wsdl     WSDL location
     * @param string $login    Login
     * @param string $password Password
     * @param Logger $logger   Logger
     */
    public function __construct($command, $project, $projectId, $wsdl, $login, $password, $force, $reorder, $importMessageMetadata, $autoRetry, Logger $logger)
    {
        $xml_security = new XML_Security();
        $xml_security->enableExternalLoadOfEntities();

        $this->force = $force;
        $this->reorder = $reorder;
        $this->importMessageMetadata = $importMessageMetadata;
        $this->autoRetry = $autoRetry;
        $this->logger = $logger;

        $this->logger->info("Init Import process");

        try {
            $this->soap = new SoapClient($wsdl, array('trace' => true));
            $this->hash = $this->soap->login($login, $password, "3.6")->session_hash;
            if ($projectId === null) {
                $this->groupId = $this->soap->getGroupByName($this->hash, $project)->group_id;
            } else {
                $this->groupId = $projectId;
            }
        } catch (SoapFault $e) {
            $this->printSoapResponseAndThrow($e);
        }

        $this->logger->info("Connected to $wsdl as $login.");
    }

    public function __destruct()
    {
        $this->soap->logout($this->hash);
    }

    /**
     * Loads and checks an XML document
     */
    protected function loadXML($rootPath)
    {
        $archiveName = basename($rootPath);
        $this->dataBaseDir = $rootPath . '/' . $archiveName;

        // DTD validation
        $dom = new DOMDocument();
        if (!$dom->load($rootPath . '/' . $archiveName . '.xml')) {
            $this->logger->error("Failed to load XML document.");
            throw new Exception("Unable to load the following XML document : " . $rootPath . "/" . $archiveName . ".xml");
        }

        if (!@$dom->validate()) {
            $this->logger->warning("DTD Validation failed.");
        }

        $this->doc = simplexml_import_dom($dom);

        // Build the maps
        $this->buildMetadataMap();

        // Import message metadata checks
        if ($this->importMessageMetadata != '') {
            if (!array_key_exists($this->importMessageMetadata, $this->metadataMap)) {
                throw new Exception("You specified an incorrect import message metadata: " . $this->importMessageMetadata);
            } else {
                $type = $this->metadataMap[$this->importMessageMetadata]['type'];
                if ($type != PLUGIN_DOCMAN_METADATA_TYPE_TEXT && $type != PLUGIN_DOCMAN_METADATA_TYPE_STRING) {
                    throw new Exception("The import message metadata type must be 'string' or 'text'");
                }
            }
        }

        $this->buildUserMap();
        $this->buildUgroupMap();

        // Sanity checks
        $this->logger->info("Checking the XML document... ");
        $this->checkMetadataDefinition();
        $this->checkHardCodedMetadataUsage();
        $this->checkMetadataUsage();
        $this->checkUgroupDefinition();
        $this->checkUgroupsUsage();
        $this->logger->info("Done.");
    }

    /**
     * Compare two item nodes using folder, document and alphabetical order
     */
    private static function compareItemNodes($node1, $node2)
    {
        if (($node1['type'] == 'folder') && ($node2['type'] != 'folder')) {
            return -1;
        } elseif (($node2['type'] == 'folder') && ($node1['type'] != 'folder')) {
            return 1;
        } else {
            $title1 = (string) $node1->properties->title;
            $title2 = (string) $node2->properties->title;
            return strnatcasecmp($title1, $title2);
        }
    }

    /**
     * Reorder an array of item nodes
     */
    private function reorderItemNodes(array $nodes)
    {
        if (isset($this->reorder) && ($this->reorder == true)) {
            usort(
                $nodes,
                static function ($node1, $node2) {
                    return self::compareItemNodes($node1, $node2);
                }
            );
        }
        return $nodes;
    }

    /**
     * Import an item to the specified parent folder
     */
    public function importPath($xmlDoc, $parentId, $path)
    {
        // If the parentId is not defined, import into the root folder
        if ($parentId === null) {
            try {
                $parentId = $this->soap->getRootFolder($this->hash, $this->groupId);
            } catch (SoapFault $e) {
                $this->printSoapResponseAndThrow($e);
            }
        }

        $this->loadXML($xmlDoc);
        $rootNode = $this->findPath($path);
        if ($rootNode instanceof SimpleXMLElement) {
            foreach ($this->reorderItemNodes($rootNode->xpath('item')) as $child) {
                $this->recurseOnNode($child, $parentId);
            }
        }
    }

    /**
     * Retrieves the ugroups of this project, and build the map that make the correspondance between
     * the group name and the group ID. The members are also retrieved.
     */
    private function buildUgroupMap()
    {
        $this->logger->info("Retrieving ugroups... ");
        try {
            $ugroups = $this->soap->getGroupUgroups($this->hash, $this->groupId);
            foreach ($ugroups as $ugroup) {
                if ($this->doc->xpath("/docman/ugroups/ugroup[@name='$ugroup->name']")) {
                    $this->ugroupMap[$ugroup->ugroup_id]['ugroup_name'] = $ugroup->name;
                    $this->ugroupMap[$ugroup->ugroup_id]['members'] = array();
                    foreach ($ugroup->members as $member) {
                        $this->ugroupMap[$ugroup->ugroup_id]['members'][$member->user_name] = $member->user_id;
                    }
                }
            }
        } catch (SoapFault $e) {
            $this->printSoapResponseAndThrow($e);
        }

        $this->logger->info("Done.");
    }

    /**
     * Retrieve the metadata of this project, and build the map that make the correspondance between
     * the metadata name and the metadata label. For list of values, the values are also retrieved.
     */
    private function buildMetadataMap()
    {
        $this->logger->info("Retrieving metadata definition... ");

        $hardCodedMetadataLabels = array('title', 'description', 'owner' , 'create_date', 'update_date' , 'status', 'obsolescence_date');

        try {
            $metadataList = $this->soap->getDocmanProjectMetadata($this->hash, $this->groupId);

            foreach ($metadataList as $metadata) {
                if (in_array($metadata->label, $hardCodedMetadataLabels)) {
                    $this->hardCodedMetadata[] = $metadata->label;
                } else {
                    if ($this->doc->xpath("/docman/propdefs/propdef[@name='$metadata->name']")) {
                        $this->metadataMap[$metadata->name]['label'] = $metadata->label;
                        $this->metadataMap[$metadata->name]['type'] = $metadata->type;
                        $this->metadataMap[$metadata->name]['isEmptyAllowed'] = $metadata->isEmptyAllowed;
                        if ($metadata->type == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                            $this->metadataMap[$metadata->name]['isMultipleValuesAllowed'] = $metadata->isMultipleValuesAllowed;
                            $lov = $metadata->listOfValues;
                            $this->metadataMap[$metadata->name]['values'] = array();
                            foreach ($lov as $val) {
                                if ($val->id != 100) {
                                    $this->metadataMap[$metadata->name]['values'][$val->name] = $val->id;
                                }
                            }
                        }
                    } elseif (!$metadata->isEmptyAllowed) {
                        $missingProp[] = $metadata->name;
                    }
                }
            }
        } catch (SoapFault $e) {
            $this->printSoapResponseAndThrow($e);
        }

        if (isset($missingProp)) {
             throw new Exception("The following propert" . ((count($missingProp) > 1) ? "ies don't" : "y doesn't") . " allow empty values and must be defined in the <propdefs> node: " . implode(", ", $missingProp));
        }

        $this->logger->info("Done.");
    }

    private function buildUserMap()
    {
        $userIdentifiers = array();
        foreach (array_unique($this->doc->xpath('//author | //owner')) as $userIdentifier) {
            if ($userIdentifier != '') {
                $userIdentifiers[] = self::userNodeToIdentifier($userIdentifier);
            }
        }

        if (count($userIdentifiers) > 0) {
            $this->logger->info("Retrieving users... ");

            try {
                $res = $this->soap->checkUsersExistence($this->hash, $userIdentifiers);
                foreach ($res as $userInfo) {
                    $this->userMap[$userInfo->identifier] = $userInfo->username;
                }
            } catch (SoapFault $e) {
                $this->printSoapResponseAndThrow($e);
            }

            $absentUsers = array_diff($userIdentifiers, array_keys($this->userMap));
            if (count($absentUsers) != 0) {
                $msg = "Can't find the users referenced by the following identifiers: " . implode(', ', $absentUsers);
                if ($this->force) {
                    $this->logger->warning($msg);

                    // Record item owners loss
                    foreach ($absentUsers as $absentUser) {
                        $nodes = $this->doc->xpath("//item[properties/owner=\"$absentUser\" or concat(properties/owner/@type, \":\", properties/owner)=\"$absentUser\"]");
                        foreach ($nodes as $node) {
                            $this->addImportMessageOnItem($node, "previous owner ($absentUser) not found");
                        }
                    }

                    // Record version authors loss
                    $versionedItems = $this->doc->xpath("//item[versions/version]");
                    foreach ($versionedItems as $versionedItem) {
                        $versionNumber = 0;
                        foreach ($versionedItem->xpath('versions/version') as $version) {
                            if ($version->author['type'] != '') {
                                $author = $version->author['type'] . ':' . $version->author;
                            } else {
                                $author = $version->author;
                            }

                            if (in_array($author, $absentUsers)) {
                                $this->addImportMessageOnItem($versionedItem, "version $versionNumber previous author ($author) not found");
                            }

                            $versionNumber++;
                        }
                    }
                } else {
                    throw new Exception($msg);
                }
            }

            $this->logger->info("Done.");
        }
    }

    /**
     * Appends the given message to the description node of the item
     */
    private function addImportMessageOnItem($item, $message)
    {
        $appendText = "Import information: $message";

        if ($this->importMessageMetadata == '') {
            if (!isset($item->properties->description)) {
                $item->properties->addChild('description');
            }
            $node = $item->properties->description;
        } else {
            $nodes = $item->xpath('properties/property[@title="' . $this->importMessageMetadata . '"]');
            if (count($nodes) > 0) {
                $node = $nodes[0];
            } else {
                $node = $item->properties->addChild('property');
                $node->addAttribute('title', $this->importMessageMetadata);
            }
        }

        if ((string) $node != '') {
            $appendText = "\n$appendText";
        }

        $this->appendTextToNode($node, $appendText);
    }

    /**
     * Appends a text to a node
     */
    private function appendTextToNode(SimpleXMLElement $node, $text)
    {
        $domNode = dom_import_simplexml($node);
        $dom = $domNode->ownerDocument;
        $textNode = $dom->createTextNode($text);
        $domNode->appendChild($textNode);
    }

    protected function printSoapResponseAndThrow(SoapFault $e)
    {
        throw new Exception("Response: " . $this->soap->__getLastResponse() . "\n" . $e);
    }

    private function checkHardCodedMetadataUsage()
    {
        if ($this->doc->xpath('//item/properties/obsolescence_date') != null && !in_array('obsolescence_date', $this->hardCodedMetadata)) {
            throw new Exception("The Obsolescence Date property is not used on the target project.");
        }

        if ($this->doc->xpath('//item/properties/status') != null && !in_array('status', $this->hardCodedMetadata)) {
            throw new Exception("The Status property is not used on the target project.");
        }
    }

    /**
     * Checks if the metadata used in the XML document reference metadata defined in the propdefs node of the document
     */
    private function checkMetadataUsage()
    {
        $propertyList = $this->doc->xpath('//item/properties/property');

        // Check the values set to the properties
        foreach ($propertyList as $property) {
            $item_nodes = $property->xpath('../..');
            $item_name = $item_nodes[0]->properties->title;
            $title = (string) $property['title'];

            if (isset($this->metadataMap[$title])) {
                $metadataDef =  $this->metadataMap[$title];
                $type = $metadataDef['type'];
                switch ($type) {
                    case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                        $values = $property->value;

                        // Check if the defined values exist
                        foreach ($values as $value) {
                            if (!isset($metadataDef['values'][(string) $value])) {
                                throw new Exception("Item '$item_name':\tThe list property '$title' is set to an incorrect value: '$value'");
                            }
                        }

                        // Check that just one value is given to a list that allow only one value
                        if (count($values) > 1 && !$metadataDef['isMultipleValuesAllowed']) {
                            throw new Exception("Item '$item_name':\tThe list property '$title' allows only one value, but " . count($values) . " values are given.");
                        } elseif (count($values) == 0 && !$metadataDef['isEmptyAllowed']) { // Check if no value is given to a list that require a value
                            throw new Exception("Item '$item_name':\tThe list property '$title' is required, but no <value> element is found.");
                        }
                }
            } else {
                throw new Exception("Item '$item_name':\tThe property '$title' is not defined in a 'propdef' element.");
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
                    throw new Exception("Item '$item_name':\tThe required property '$requiredProperty' is not set.");
                } elseif ((string) $searchedProperty[0] == '') {
                    throw new Exception("Item '$item_name':\tThe required property '$requiredProperty' is set to an empty value.");
                }
            }
        }
    }

    /**
     * Checks if the permissions defined in the XML document reference ugroups defined in the ugroups node of the document
     */
    private function checkUgroupsUsage()
    {
        $permissions = $this->doc->xpath('//item/permissions/permission');

        foreach ($permissions as $permission) {
            $ugroup_id = (string) $permission['ugroup'];

            if (!isset($this->ugroupMap[$ugroup_id])) {
                $item_nodes = $permission->xpath('../..');
                $item_name  = $item_nodes[0]->properties->title;
                throw new Exception("Item '$item_name':\tThe permission references an undefined group: '$ugroup_id'");
            }
        }
    }

    /**
     * Checks the definition of the ugroups
     */
    private function checkUgroupDefinition()
    {
        $ugroups = $this->doc->xpath('/docman/ugroups/ugroup');

        $errorMsg = '';

        foreach ($ugroups as $ugroup) {
            $ugroup_id   = (string) $ugroup['id'];
            $ugroup_name = (string) $ugroup['name'];

            if (isset($this->ugroupMap[$ugroup_id])) {
                foreach ($ugroup->member as $member) {
                    $user_name = (string) $member;
                    $members[] = $user_name;
                    if (!isset($this->ugroupMap[$ugroup_id]['members'][$user_name])) {
                        $this->logger->warning("The user '$user_name' is not a member of the ugroup '$ugroup_name' on the target project.");
                    }
                }

                foreach ($this->ugroupMap[$ugroup_id]['members'] as $user_name => $member) {
                    if (!in_array($user_name, $members)) {
                        $this->logger->warning("The user '$user_name' is a member of the ugroup '$ugroup_name' on the target project, but he's not inside the ugroup definition in the XML document.");
                    }
                }
            } else {
                throw new Exception("The ugroup '$ugroup_name' doesn't exist on the target project.");
            }
        }

        if ($errorMsg != '') {
            $this->logger->error($errorMsg);
        }
    }

    /**
     * Checks the metadata definitions. The definitions in the XML document must correspond on the server.
     * The type is checked using the metadata name. If this is a list, the allowed values are also checked.
     */
    private function checkMetadataDefinition()
    {
        // Retrieve the metadata definition in the XML document
        $propdefs = $this->doc->xpath('/docman/propdefs/propdef');

        $errorMsg = '';

        foreach ($propdefs as $propdef) {
            $name = (string) $propdef['name'];

            $type = self::typeStringToCode((string) $propdef['type']);
            // First check if the metadata exists on the server
            if (isset($this->metadataMap[$name])) {
                // Check if the metadata type is the same in the XML document and on the server
                if ($type == $this->metadataMap[$name]['type']) {
                    if ($type == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                        $values = array();
                        foreach ($propdef->value as $value) {
                            $values[] = (string) $value;
                        }

                        $server_values = array_keys($this->metadataMap[$name]['values']);
                        if ($values != $server_values) {
                            $diff1 = array_diff($values, $server_values);
                            $diff2 = array_diff($server_values, $values);
                            $errorMsg = "The property '$name' doesn't declare the same list of values as in the target project:";
                            if (count($diff1)) {
                                $errorMsg .= "\tNot on the target:\t" . implode(', ', $diff1);
                            }
                            if (count($diff2)) {
                                $errorMsg .= "\tNot on the archive:\t" . implode(', ', $diff2);
                            }
                            throw new Exception($errorMsg);
                        }
                    }
                } elseif ($type === null) {
                      throw new Exception("The property '$name' has an incorrect type: '" . $propdef['type'] . "' should be '" . self::typeCodeToString($this->metadataMap[$name]['type']) . "'");
                } else {
                     throw new Exception("The property '$name' has not the same type as in the target project: '" . $propdef['type'] . "' should be '" . self::typeCodeToString($this->metadataMap[$name]['type']) . "'");
                }

                // Check if the metadata value can be empty
                $empty = (string) $propdef['empty'];
                // 'true' is the default value if nothing is specified
                if (($empty == 'true' || $empty == null) && !$this->metadataMap[$name]['isEmptyAllowed']) {
                    throw new Exception("The property '$name' doesn't allow empty values in the target project. Please set the \"empty\" attribute to \"false\" to the corresponding propdef element.");
                } elseif ($empty == 'false' && $this->metadataMap[$name]['isEmptyAllowed']) {
                    throw new Exception("The property '$name' allows empty values in the target project. Please set the \"empty\" attribute to \"true\", or remove this attribute (\"true\" is implicit).");
                }

                // Check if multiple values are allowed
                if ($type == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                    $multival = (string) $propdef['multivalue'];
                    // 'false' is the default value if nothing is specified
                    if (($multival == 'false' || $multival == null) && $this->metadataMap[$name]['isMultipleValuesAllowed']) {
                        throw new Exception("The property '$name' allows multiple values. Please set the attribute multivalue to \"true\" to the corresponding propdef element.");
                    } elseif ($multival == 'true' && !$this->metadataMap[$name]['isMultipleValuesAllowed']) {
                        throw new Exception("The property '$name' doesn't allow multiple values. Please set the attribute empty to \"false\", or remove this attribute (\"false\" is implicit).");
                    }
                }
            } else {
                throw new Exception("The property '$name' (" . (string) $propdef['type'] . " metadata) doesn't exist on the target project.");
            }
        }
    }

    /**
     * Converts a type code to a string
     */
    private static function typeCodeToString($type)
    {
        switch ($type) {
            case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                return 'string';
            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                return 'text';
            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                return 'date';
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                return 'list';
            default:
                return null;
        }
    }

    /**
     * Converts a string to a type code
     */
    private static function typeStringToCode($type)
    {
        switch ($type) {
            case 'string':
                return PLUGIN_DOCMAN_METADATA_TYPE_STRING;
            case 'text':
                return PLUGIN_DOCMAN_METADATA_TYPE_TEXT;
            case 'date':
                return PLUGIN_DOCMAN_METADATA_TYPE_DATE;
            case 'list':
                return PLUGIN_DOCMAN_METADATA_TYPE_LIST;
            default:
                return null;
        }
    }

    /**
     * Returns the node that corresponds to the given unix-like path
     */
    protected function findPath($path)
    {
        // Transform "Unix path" to XPath
        $xpath = '/docman' . preg_replace('/\/([^\/]+)/', '/item[properties/title="$1"]', $path);
        $nodeList = $this->doc->xpath($xpath);

        if ($nodeList === false || count($nodeList) == 0) {
            throw new Exception("Can't find the element \"$path\" ($xpath)");
        } else {
            if (count($nodeList) == 1) {
                return $nodeList[0];
            } else {
                throw new Exception("$path ($xpath) found more than one target element");
            }
        }
    }

    /**
     * userNodeToIdentifier
     *
     * @param $userNode A node containing a user (author, owner)
     * @return string   The user identifier formatted as "type:value" for this node
     */
    private static function userNodeToIdentifier($userNode)
    {
        if ((string) $userNode == '') {
            return null;
        } else {
            if (isset($userNode['type'])) {
                return $userNode['type'] . ":$userNode";
            } else {
                return (string) $userNode;
            }
        }
    }

    /**
     * userNodeToUsername
     *
     * @param $userNode A node containing a user (author, owner)
     * @return string   The username according to the userMap
     */
    private function userNodeToUsername($userNode)
    {
        if ((string) $userNode == '' || !isset($this->userMap[self::userNodeToIdentifier($userNode)])) {
            return null;
        } else {
            return $this->userMap[self::userNodeToIdentifier($userNode)];
        }
    }

    /**
     * Parse a date given as an ISO8601 date or a timestamp
     * @return The corresponding timestamp
     */
    private function parseDate($s)
    {
        if (is_numeric($s)) {
            return $s;
        } else {
            return DateParser::parseIso8601($s);
        }
    }

    /**
     * Returns an array containing the properties for an item (metadata + permissions)
     */
    protected function getItemInformation(SimpleXMLElement $node)
    {
        $information = array(
                          (string) $node->properties->title,
                          (string) $node->properties->description,
                          (string) $node->properties->status,
                          $this->parseDate((string) (string) $node->properties->obsolescence_date),
                          $this->userNodeToUsername($node->properties->owner),
                          $this->parseDate((string) $node->properties->create_date),
                          $this->parseDate((string) $node->properties->update_date),
        );

        // Dynamic metadata
        $metadata = array();
        foreach ($node->xpath('properties/property') as $property) {
            $this->extractOneMetadata($property, $metadata);
        }
        $information[] = $metadata;

        // Permissions
        $permissions = array();
        foreach ($node->xpath('permissions/permission') as $permission) {
            $this->extractOnePermission($permission, $permissions);
        }
        $information[] = $permissions;

        return $information;
    }

    /**
     * Returns an array containing the data of a version
     */
    protected function getVersionInformation(SimpleXMLElement $node)
    {
        $version = array(
                       (string) $node->content,
                       (string) $node->label,
                       (string) $node->changelog,
                       $this->userNodeToUsername($node->author),
                       $this->parseDate((string) $node->date),
        );

        return $version;
    }

    /**
     * Processes the given node and its childs
     *
     * @param SimpleXMLElement $node     The node to process
     * @param int              $parentId The parent folder ID where the node will be imported
     */
    protected function recurseOnNode(SimpleXMLElement $node, $parentId)
    {
        list(
             $title,
             $description,
             $status,
             $obsolescenceDate,
             $owner,
             $createDate,
             $updateDate,
             $metadata,
             $permissions
        ) = $this->getItemInformation($node);

        $ordering         = 'end';

        switch ($node['type']) {
            case 'file':
                $iFiles = 0;
                $itemId = false;

                foreach ($node->xpath('versions/version') as $version) {
                    list(
                        $file,
                        $label,
                        $changelog,
                        $author,
                        $date
                    ) = $this->getVersionInformation($version);

                    $fileName = (string) $version->filename;
                    $fileType = (string) $version->filetype;

                    // If this is the initial version
                    if ($iFiles == 0) {
                        $itemId = $this->createFile($parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $file, $fileName, $fileType, $author, $date, $owner, $createDate, $updateDate);
                    } else {
                        if ($itemId !== false) {
                            $this->createFileVersion($itemId, $label, $changelog, $file, $fileName, $fileType, $author, $date);
                        }
                    }
                    $iFiles++;
                }

                break;

            case 'embeddedfile':
                $iFiles = 0;
                $itemId = false;
                foreach ($node->xpath('versions/version') as $version) {
                    list(
                        $file,
                        $label,
                        $changelog,
                        $author,
                        $date
                    ) = $this->getVersionInformation($version);

                    // If this is the initial version
                    if ($iFiles == 0) {
                        $itemId = $this->createEmbeddedFile($parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $file, $author, $date, $owner, $createDate, $updateDate);
                    } else {
                        if ($itemId !== false) {
                            $this->createEmbeddedFileVersion($itemId, $label, $changelog, $file, $author, $date);
                        }
                    }
                    $iFiles++;
                }
                break;

            case 'wiki':
                $pagename = (string) $node->pagename;
                $this->createWiki($parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $pagename, $owner, $createDate, $updateDate);
                break;

            case 'link':
                $url = (string) $node->url;
                $this->createLink($parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $url, $owner, $createDate, $updateDate);
                break;

            case 'empty':
                $this->createEmpty($parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $owner, $createDate, $updateDate);
                break;

            case 'folder':
                $newParentId = $this->createFolder($parentId, $title, $description, $ordering, $status, $permissions, $metadata, $owner, $createDate, $updateDate);
                foreach ($this->reorderItemNodes($node->xpath('item')) as $child) {
                    $this->recurseOnNode($child, $newParentId);
                }
                break;
        }
    }

    /**
     * Extract real metadata information in one <property> node
     */
    private function extractOneMetadata(SimpleXMLElement $property, array &$metadata)
    {
        $propTitle = (string) $property['title'];
        $dstMetadataLabel = $this->metadataMap[$propTitle]['label'];

        if ($this->metadataMap[$propTitle]['type'] == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $values = $property->xpath('value');
            if ($values !== false && count($values) > 0) {
                foreach ($values as $value) {
                    $val = (string) $value;
                    $metadata[] = array('label' => $dstMetadataLabel, 'value' => $this->metadataMap[$propTitle]['values'][$val]);
                }
            } else {
                $metadata[] = array('label' => $dstMetadataLabel, 'value' => '100');
            }
        } else {
            $value = (string) $property;

            if ($this->metadataMap[$propTitle]['type'] == PLUGIN_DOCMAN_METADATA_TYPE_DATE) {
                $value = $this->parseDate($value);
            }

            $metadata[] = array('label' => $dstMetadataLabel, 'value' => $value);
        }
    }

    /**
     * Extract a permission in one <permission> node
     */
    private function extractOnePermission(SimpleXMLElement $permission, array &$permissions)
    {
        $ugroup_id = (string) $permission['ugroup'];
        switch ((string) $permission) {
            case 'read':
                $type = 'PLUGIN_DOCMAN_READ';
                break;
            case 'write':
                $type = 'PLUGIN_DOCMAN_WRITE';
                break;
            case 'manage':
                $type = 'PLUGIN_DOCMAN_MANAGE';
                break;
            case 'none':
            default:
                $type = '';
        }

        $permissions[] = array('ugroup_id' => $ugroup_id, 'type' => $type);
    }

    protected function askWhatToDo($e)
    {
        self::printException($e);

        if ($this->autoRetry == true && $this->retryCounter-- > 0) {
            $this->logger->info("Auto-retrying in 5s... ($this->retryCounter auto-retries left)");
            sleep(5);
            $retry = true;
        } else {
            do {
                $this->logger->info("(R)etry, (A)bort, (C)ontinue? [R] ");
                $op = strtoupper(trim(fgets(STDIN)));
            } while ($op != '' && $op != 'R' && $op != 'C' && $op != 'A');

            if ($op == 'A') {
                $this->logger->info('Import aborted.');
                die;
            } elseif ($op == 'C') {
                $this->logger->info('Continuing...');
                $retry = false;
            } else {
                $this->logger->info('Retrying...');
                $retry = true;
            }
        }

        return $retry;
    }

    protected function initRetryCounter()
    {
        $this->retryCounter = 5;
    }

    private function printException($e)
    {
        $this->logger->error($e->__toString());
    }

    /**
     * Creates a folder
     */
    private function createFolder($parentId, $title, $description, $ordering, $status, array $permissions, array $metadata, $owner, $createDate, $updateDate)
    {
        $this->initRetryCounter();
        do {
            $retry = false;

            $this->logger->info("Creating folder            '$title'");

            try {
                $id = $this->soap->createDocmanFolder($this->hash, $this->groupId, $parentId, $title, $description, $ordering, $status, $permissions, $metadata, $owner, $createDate, $updateDate);
                $this->logger->info(" #$id");
                return $id;
            } catch (Exception $e) {
                $retry = $this->askWhatToDo($e);
            }
        } while ($retry);
    }

    /**
     * Creates an empty document
     */
    private function createEmpty($parentId, $title, $description, $ordering, $status, $obsolescenceDate, array $permissions, array $metadata, $owner, $createDate, $updateDate)
    {
        $this->initRetryCounter();
        do {
            $retry = false;

            $this->logger->info("Creating empty document    '$title'");

            try {
                $id = $this->soap->createDocmanEmptyDocument($this->hash, $this->groupId, $parentId, $title, $description, $ordering, $status, $obsolescenceDate, $permissions, $metadata, $owner, $createDate, $updateDate);
                $this->logger->info(" #$id");
                return $id;
            } catch (Exception $e) {
                $retry = $this->askWhatToDo($e);
            }
        } while ($retry);
    }

    /**
     * Creates a wiki page
     */
    private function createWiki($parentId, $title, $description, $ordering, $status, $obsolescenceDate, array $permissions, array $metadata, $pagename, $owner, $createDate, $updateDate)
    {
        $this->initRetryCounter();
        do {
            $retry = false;

            $this->logger->info("Creating wiki page         '$title' ($pagename)");

            try {
                $id = $this->soap->createDocmanWikiPage($this->hash, $this->groupId, $parentId, $title, $description, $ordering, $status, $obsolescenceDate, $pagename, $permissions, $metadata, $owner, $createDate, $updateDate);
                $this->logger->info(" #$id");
                return $id;
            } catch (Exception $e) {
                $retry = $this->askWhatToDo($e);
            }
        } while ($retry);
    }

    /**
     * Creates a link
     */
    private function createLink($parentId, $title, $description, $ordering, $status, $obsolescenceDate, array $permissions, array $metadata, $url, $owner, $createDate, $updateDate)
    {
        $this->initRetryCounter();
        do {
            $retry = false;

            $this->logger->info("Creating link              '$title' ($url)");

            try {
                $id = $this->soap->createDocmanLink($this->hash, $this->groupId, $parentId, $title, $description, $ordering, $status, $obsolescenceDate, $url, $permissions, $metadata, $owner, $createDate, $updateDate);
                $this->logger->info(" #$id");
                return $id;
            } catch (Exception $e) {
                $retry = $this->askWhatToDo($e);
            }
        } while ($retry);
    }

    /**
     * Creates an embedded file
     */
    private function createEmbeddedFile($parentId, $title, $description, $ordering, $status, $obsolescenceDate, array $permissions, array $metadata, $file, $author, $date, $owner, $createDate, $updateDate)
    {
        $this->initRetryCounter();
        do {
            $retry = false;

            $this->logger->info("Creating embedded file     '$title' ($file)");
            $fullPath = $this->dataBaseDir . '/' . $file;
            $contents = file_get_contents($fullPath);

            try {
                $id = $this->soap->createDocmanEmbeddedFile($this->hash, $this->groupId, $parentId, $title, $description, $ordering, $status, $obsolescenceDate, $contents, $permissions, $metadata, $author, $date, $owner, $createDate, $updateDate);
                $this->logger->info(" #$id");
                return $id;
            } catch (Exception $e) {
                $retry = $this->askWhatToDo($e);
            }
        } while ($retry);
    }

    /**
     * Creates a file
     */
    private function createFile($parentId, $title, $description, $ordering, $status, $obsolescenceDate, array $permissions, array $metadata, $file, $fileName, $fileType, $author, $date, $owner, $createDate, $updateDate)
    {
        $infoStr = "Creating file              '$title' ($file, $fileName, $fileType)";

        $fullPath = $this->dataBaseDir . '/' . $file;
        $fileSize = filesize($fullPath);

        $chunk_offset = 0;
        $item_id      = 0;

        $this->initRetryCounter();
        do {
            $continue = true;

            // Retrieve the current chunk of the file
            $contents = file_get_contents($fullPath, null, null, $chunk_offset * $this->chunk_size, $this->chunk_size);

            if (strlen($contents) < $this->chunk_size) {
                $continue = false;
            }

            // Send the chunk
            try {
                if ($item_id === 0) {
                    $this->logger->info("\r$infoStr attempt to upload first chunk");
                    // If this is the first chunk, then use the original soapCommand...
                    $item_id = $this->soap->createDocmanFile(
                        $this->hash,
                        $this->groupId,
                        $parentId,
                        $title,
                        $description,
                        $ordering,
                        $status,
                        $obsolescenceDate,
                        $permissions,
                        $metadata,
                        $fileSize,
                        $fileName,
                        $fileType,
                        base64_encode($contents),
                        $chunk_offset,
                        $this->chunk_size,
                        $author,
                        $date,
                        $owner,
                        $createDate,
                        $updateDate
                    );
                } else {
                    // Display progress bar
                    $chunk_count = ceil($fileSize / $this->chunk_size);
                    $this->logger->info("\r$infoStr " . floor($chunk_offset / $chunk_count * 100)  . "%");

                    // If this is not the first chunk, then we have to append the chunk
                    $this->soap->appendDocmanFileChunk(
                        $this->hash,
                        $this->groupId,
                        $item_id,
                        base64_encode($contents),
                        $chunk_offset,
                        $this->chunk_size
                    );
                }
                $chunk_offset++;
                unset($contents);
            } catch (Exception $e) {
                unset($contents);
                if ($this->requestEntityTooLarge($e)) {
                    $continue = $this->adjustChunkSize();
                } else {
                    $continue = $this->askWhatToDo($e);
                }
            }
        } while ($continue);

        // Finish!
        $this->logger->info("\r$infoStr #$item_id");

        // Check that the local and remote file are the same
        try {
            if ($this->checkChecksum($item_id, $fullPath) === true) {
                return $item_id;
            } else {
                $this->logger->error("ERROR: Checksum error");
                return false;
            }
        } catch (Exception $e) {
            $this->printException($e);
        }
    }

    private function adjustChunkSize()
    {
        $this->logger->warning("Request entity too large, need to adjust upload chunk size");
        $this->chunk_size = floor($this->chunk_size / 2);
        if ($this->chunk_size > 1000) {
            $this->logger->info("New chunk size: " . $this->chunk_size);
            return true;
        } else {
            throw new Exception('Unable to upload content, chunk size too large');
        }
    }

    private function requestEntityTooLarge(SoapFault $fault)
    {
        return isset($fault->faultcode) && strtolower($fault->faultcode) == 'http' && strpos(strtolower($fault->getMessage()), 'request entity too large') !== false;
    }

    /**
     * Compares the local and the distant checksums
     *
     * @return true if they are the same
     */
    private function checkChecksum($item_id, $filename)
    {
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
    protected function createFileVersion($itemId, $label, $changeLog, $file, $fileName, $fileType, $author, $date)
    {
        $infoStr = "                      Version '$label' ($file, $fileName, $fileType)";

        $fullPath = $this->dataBaseDir . '/' . $file;
        $fileSize = filesize($fullPath);

        $chunk_offset = 0;

        $this->initRetryCounter();
        do {
            $continue = true;

            // Retrieve the current chunk of the file
            $contents = file_get_contents($fullPath, null, null, $chunk_offset * $this->chunk_size, $this->chunk_size);

            if (strlen($contents) < $this->chunk_size) {
                $continue = false;
            }

            // Send the chunk
            try {
                if ($chunk_offset === 0) {
                    $this->logger->info("\r$infoStr attempt to upload first chunk");
                    // If this is the first chunk, then use the original soapCommand...
                    $this->soap->createDocmanFileVersion($this->hash, $this->groupId, $itemId, $label, $changeLog, $fileSize, $fileName, $fileType, base64_encode($contents), $chunk_offset, $this->chunk_size, $author, $date);
                } else {
                    // Display progress bar
                    $chunk_count = ceil($fileSize / $this->chunk_size);
                    $this->logger->info("\r$infoStr " . floor($chunk_offset / $chunk_count * 100)  . "%");

                    // If this is not the first chunk, then we have to append the chunk
                    $this->soap->appendDocmanFileChunk($this->hash, $this->groupId, $itemId, base64_encode($contents), $chunk_offset, $this->chunk_size);
                }
                $chunk_offset++;
                unset($contents);
            } catch (Exception $e) {
                unset($contents);
                if ($this->requestEntityTooLarge($e)) {
                    $continue = $this->adjustChunkSize();
                } else {
                    $continue = $this->askWhatToDo($e);
                }
            }
        } while ($continue);

        // Finish!
        $this->logger->info("\r$infoStr     ");

        // Check that the local and remote file are the same
        try {
            if ($this->checkChecksum($itemId, $fullPath) == true) {
                return true;
            } else {
                $this->logger->error("ERROR: Checksum error");
                return false;
            }
        } catch (Exception $e) {
            $this->printException($e);
        }
    }

    protected function createEmbeddedFileVersion($itemId, $label, $changeLog, $file, $author, $date)
    {
        $this->initRetryCounter();
        do {
            $retry = false;

            $this->logger->info("                      Version '$label' ($file)");
            $fullPath = "$this->dataBaseDir/$file";

            $contents = file_get_contents($fullPath);

            try {
                $this->soap->createDocmanEmbeddedFileVersion($this->hash, $this->groupId, $itemId, $label, $changeLog, $contents, $author, $date);
            } catch (Exception $e) {
                $retry = $this->askWhatToDo($e);
            }
        } while ($retry);
    }
}
