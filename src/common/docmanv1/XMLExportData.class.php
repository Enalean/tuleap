<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class DocmanV1_XMLExportData {

    const ROOT_FOLDER_NAME = 'Legacy documentation';

    const FOLDER_TYPE = 'folder';
    const FILE_TYPE   = 'file';

    const FOLDER_PERMISSION_TYPE   = 'DOCGROUP_READ';
    const DOCUMENT_PERMISSION_TYPE = 'DOCUMENT_READ';

    const V2_SOAP_PERM_MANAGE = 'manage';
    const V2_SOAP_PERM_READ   = 'read';
    const V2_SOAP_PERM_NONE   = '';


    /**
     * @var DocmanV1_XMLExportDao
     */
    private $dao;

    /**
     * @var DOMDocument
     */
    private $doc;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    private $file_counter = 0;

    private $data_path;

    private $ugroups = array();

    private $minimal_permissions = array();

    public function __construct(DocmanV1_XMLExportDao $dao, UserManager $user_manager, UGroupManager $ugroup_manager, DOMDocument $doc, $data_path) {
        $this->dao                 = $dao;
        $this->doc                 = $doc;
        $this->user_manager        = $user_manager;
        $this->data_path           = $data_path;
        $this->ugroup_manager      = $ugroup_manager;
        $this->minimal_permissions = array(
            ProjectUGroup::$normalized_names[ProjectUGroup::ANONYMOUS]       => array(),
            ProjectUGroup::$normalized_names[ProjectUGroup::REGISTERED]      => array(),
            ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS] => array(),
            ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_ADMIN]   => array(self::V2_SOAP_PERM_MANAGE),
        );
    }

    public function appendUGroups(DOMElement $ugroups, Project $project) {
        foreach ($this->ugroups as $id => $name) {
            $ugroup      = $this->ugroup_manager->getUGroupWithMembers($project, $id);
            $ugroup_node = $this->createUGroupNode($ugroups, $name);
            $this->appendMembersForStaticGroups($ugroup_node, $ugroup);
        }
    }

    private function createUGroupNode(DOMElement $ugroups, $name) {
        $ugroup_node = $this->doc->createElement('ugroup');
        $ugroup_node->setAttribute('name', $name);
        $ugroups->appendChild($ugroup_node);
        return $ugroup_node;
    }

    private function appendMembersForStaticGroups(DOMElement $ugroup_node, ProjectUGroup $ugroup) {
        if ($ugroup->getId() > ProjectUGroup::NONE) {
            foreach ($ugroup->getMembersUserName() as $user_name) {
                $this->appendChild($ugroup_node, 'member', $user_name);
            }
        }
    }

    public function getTree(Project $project) {
        $root = $this->createFolder(self::ROOT_FOLDER_NAME, "Documentation imported from Docman v1");
        $this->appendGroups($root, $project);
        return $root;
    }

    private function createFolder($title, $description) {
        return $this->createItem(self::FOLDER_TYPE, $title, $description, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], 'admin');
    }

    private function createDocument($title, $description, $create_date, $update_date, $owner_name) {
        return $this->createItem(self::FILE_TYPE, $title, $description, $create_date, $update_date, $owner_name);
    }

    private function createItem($type, $title, $description, $create_date, $update_date, $owner_name) {
        $item = $this->doc->createElement('item');
        $item->setAttribute('type', $type);

        $properties = $this->doc->createElement('properties');
        $item->appendChild($properties);

        $this->appendChild($properties, 'title', $title);
        $this->appendChild($properties, 'description', $description);
        $this->appendChild($properties, 'create_date', date('c', $create_date));
        $this->appendChild($properties, 'update_date', date('c', $update_date));
        $this->appendChild($properties, 'owner', $owner_name);

        return $item;
    }

    private function appendPermissions(DOMElement $node, $object_id, $permission_type) {
        $results = $this->dao->searchUGroupForObjectPermission($permission_type, $object_id);
        if (count($results) > 0) {
            $permissions = $this->doc->createElement('permissions');
            $this->appendPermissionsFor($permissions, $this->getDocumentPermissions($results));
            $node->appendChild($permissions);
        }
    }

    private function getDocumentPermissions(DataAccessResult $results) {
        $perms = $this->minimal_permissions;
        foreach($results as $row) {
            if ($row['id'] < ProjectUGroup::PROJECT_ADMIN || $row['id'] > ProjectUGroup::NONE) {
                $ugroup_name = util_translate_name_ugroup($row['name']);
                $this->ugroups[$row['id']] = $ugroup_name;
                $perms[$ugroup_name][] = self::V2_SOAP_PERM_READ;
            }
        }
        return $perms;
    }

    private function appendPermissionsFor(DOMElement $permissions, array $perms) {
        foreach ($perms as $ugroup_name => $permission_types) {
            if (count($permission_types) > 0) {
                $this->appendPermissionsTypesFor($permissions, $permission_types, $ugroup_name);
            } else {
                $permissions->appendChild($this->createPermissionFor(self::V2_SOAP_PERM_NONE, $ugroup_name));
            }
        }
    }

    private function appendPermissionsTypesFor(DOMElement $permissions, array $permission_types, $ugroup_name) {
        foreach ($permission_types as $type) {
            $permissions->appendChild($this->createPermissionFor($type, $ugroup_name));
        }
    }

    private function createPermissionFor($type, $ugroup_name) {
        $permission = $this->doc->createElement('permission');
        $permission->setAttribute('ugroup', $ugroup_name);
        $permission->appendChild($this->doc->createTextNode($type));
        return $permission;
    }

    private function appendChild(DOMElement $node, $label, $value) {
        $sub_node = $this->doc->createElement($label);
        $sub_node->appendChild($this->doc->createTextNode($value));
        $node->appendChild($sub_node);
    }

    private function appendGroups(DOMElement $parent_node, Project $project) {
        foreach ($this->dao->searchAllGroups($project->getID()) as $row) {
            $folder = $this->createFolder($row['groupname'], '');
            $this->appendPermissions($folder, $row['doc_group'], self::FOLDER_PERMISSION_TYPE);
            $this->appendDocuments($folder, $row['doc_group']);
            $parent_node->appendChild($folder);
        }
    }

    private function appendDocuments(DOMElement $parent_node, $doc_group_id) {
        foreach ($this->dao->searchAllDocs($doc_group_id) as $row) {
            $creator_name = 'admin';
            $creator = $this->user_manager->getUserById($row['created_by']);
            if ($creator !== null) {
                $creator_name = $creator->getUnixName();
            }

            $document = $this->createDocument($row['title'], $row['description'], $row['createdate'], $row['updatedate'], $creator_name);
            $this->appendPermissions($document, $row['docid'], self::DOCUMENT_PERMISSION_TYPE);
            $this->appendFile($document, $row, $creator_name);
            $parent_node->appendChild($document);
        }
    }

    private function appendFile(DOMElement $file_node, array $row, $creator_name) {
        $versions = $this->doc->createElement('versions');
        $file_node->appendChild($versions);
        $version = $this->doc->createElement('version');
        $versions->appendChild($version);

        $file_name = sprintf('content%05d.bin', $this->file_counter++);

        $this->appendChild($version, 'author', $creator_name);
        $this->appendChild($version, 'changelog', '');
        $this->appendChild($version, 'date', date('c', $row['updatedate']));
        $this->appendChild($version, 'filename', $row['filename']);
        $this->appendChild($version, 'filetype', $row['filetype']);
        $this->appendChild($version, 'content', $file_name);

        file_put_contents($this->data_path . DIRECTORY_SEPARATOR . $file_name, $row['data']);
    }
}
