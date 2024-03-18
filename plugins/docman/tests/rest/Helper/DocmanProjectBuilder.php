<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

namespace Tuleap\Docman\Test\rest\Helper;

use Docman_ApprovalTableItemDao;
use Docman_ItemFactory;
use Docman_MetadataValueFactory;
use ProjectUGroup;
use REST_TestDataBuilder;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Docman\Test\rest\DocmanDatabaseInitialization;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;

class DocmanProjectBuilder extends REST_TestDataBuilder
{
    private const DOCMAN_REGULAR_USER_PASSWORD = 'welcome0';

    /**
     * @var int
     */
    private $docman_user_id;

    /**
     * @var \Docman_ItemFactory
     */
    private $docman_item_factory;

    /**
     * @var \Project
     */
    protected $project;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var Docman_MetadataValueFactory
     */
    private $metadata_value_factory;

    public function __construct(string $project_name)
    {
        parent::__construct();
        $this->project                = $this->project_manager->getProjectByUnixName($project_name);
        $this->docman_item_factory    = Docman_ItemFactory::instance($this->project->getID());
        $this->metadata_value_factory = new Docman_MetadataValueFactory($this->project->getID());
    }

    /**
     *
     * @return bool|int
     */
    public function createItemWithVersion(
        int $user_id,
        int $docman_root_id,
        string $title,
        int $item_type,
        string $link_url = '',
        string $wiki_page = '',
    ) {
        $item_id = $this->createItem($user_id, $docman_root_id, $title, $item_type, $link_url, $wiki_page);

        switch ($item_type) {
            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
                $file_path = __DIR__ . '/../_fixtures/docmanFile/embeddedFile';
                $file_type = 'text/html';
                $this->addFileVersion($item_id, $title, $file_type, $file_path);
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                $file_path = __DIR__ . '/../_fixtures/docmanFile/file.txt';
                $file_type = 'application/pdf';
                $this->addFileVersion($item_id, $title, $file_type, $file_path);
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_LINK:
                $this->addLinkVersion($item_id);
                break;
            default:
                $file_type = null;
                break;
        }
        return $item_id;
    }

    /**
     * @return bool|int
     */
    public function createItem(
        int $user_id,
        int $docman_root_id,
        string $title,
        int $item_type,
        string $link_url = '',
        string $wiki_page = '',
    ) {
        $item = [
            'parent_id'         => $docman_root_id,
            'group_id'          => $this->project->getID(),
            'title'             => $title,
            'description'       => '',
            'create_date'       => time(),
            'update_date'       => time(),
            'user_id'           => $user_id,
            'status'            => 100,
            'obsolescence_date' => 0,
            'rank'              => 1,
            'item_type'         => $item_type,
            'link_url'          => $link_url,
            'wiki_page'         => $wiki_page,
            'file_is_embedded'  => '',
        ];

        return $this->docman_item_factory->create($item, 1);
    }

    /**
     * @return bool|int
     */
    public function addFileVersion(int $item_id, string $title, string $item_type, string $file_path)
    {
        $version         = [
            'item_id'   => $item_id,
            'number'    => 1,
            'user_id'   => 102,
            'label'     => '',
            'changelog' => '',
            'date'      => time(),
            'filename'  => $title,
            'filesize'  => 3,
            'filetype'  => $item_type,
            'path'      => $file_path,
        ];
        $version_factory = new \Docman_VersionFactory();
        return $version_factory->create($version);
    }

    public function getProject(): \Project
    {
        return $this->project;
    }

    private function addLinkVersion(int $item_id): int
    {
        $docman_link = $this->docman_item_factory->getItemFromDb($item_id);
        $docman_link->setUrl('https://my.example.test');
        $version_link_factory = new \Docman_LinkVersionFactory();
        $version_link_factory->create($docman_link, 'changset1', 'test rest Change', time());
        $link_version = $version_link_factory->getLatestVersion($docman_link);
        return $link_version->getId();
    }

    public function generateDocmanRegularUser(): void
    {
        $this->user = $this->user_manager->getUserByUserName(DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME);
        $this->user->setPassword(new ConcealedString(self::DOCMAN_REGULAR_USER_PASSWORD));
        $this->user_manager->updateDb($this->user);
    }

    public function addReadPermissionOnItem(int $object_id, int $ugroup_name): void
    {
        permission_add_ugroup(
            $this->project->getID(),
            'PLUGIN_DOCMAN_READ',
            $object_id,
            $ugroup_name,
            true
        );
    }

    public function addWritePermissionOnItem(int $object_id, int $ugroup_name): void
    {
        permission_add_ugroup(
            $this->project->getID(),
            'PLUGIN_DOCMAN_WRITE',
            $object_id,
            $ugroup_name,
            true
        );
    }

    public function appendCustomMetadataValueToItem(int $item_id, string $value): void
    {
        $metadata_value = new \Docman_MetadataValueScalar();

        $metadata_value->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $metadata_value->setItemId($item_id);
        $metadata_value->setFieldId(1);
        $metadata_value->setValueString($value);

        $this->metadata_value_factory->create($metadata_value);
    }

    public function activateWikiServiceForTheProject(): void
    {
        $initializer = new DocmanDatabaseInitialization();
        $initializer->setup($this->project);
    }

    public function addApprovalTable(string $title, int $version_id, int $status, string $field): void
    {
        $dao      = new Docman_ApprovalTableItemDao();
        $table_id = $dao->createTable(
            $field,
            $version_id,
            $this->docman_user_id,
            $title,
            time(),
            $status,
            false
        );

        $reviewer_dao = new \Docman_ApprovalTableReviewerDao(\CodendiDataAccess::instance());
        $reviewer_dao->addUser($table_id, $this->docman_user_id);
        $reviewer_dao->updateReview($table_id, $this->docman_user_id, time(), 1, "", 1);
    }

    public function lockItem(int $item_id, int $user_id)
    {
        $dao = new \Docman_LockDao();
        $dao->addLock(
            $item_id,
            $user_id,
            time()
        );
    }

    public function createItemWithApprovalTable(
        int $folder_id,
        string $file_name,
        string $file_version_title,
        int $approval_status,
        int $user_id,
        int $item_type,
    ): void {
        $item_id = $this->createItem(
            $user_id,
            $folder_id,
            $file_name,
            $item_type
        );

        $file_path  = __DIR__ . '/../_fixtures/docmanFile/embeddedFile';
        $version_id = $this->addFileVersion($item_id, $file_version_title, 'application/pdf', $file_path);

        $this->addApprovalTable($file_name, (int) $version_id, $approval_status, 'version_id');
    }

    /**
     * @param $folder_id
     */
    public function createAndLockItem(int $folder_id, int $item_owner_id, int $lock_owner_id, string $item_title, int $docman_item_type): void
    {
        $file_id = $this->createItem(
            $item_owner_id,
            $folder_id,
            $item_title,
            $docman_item_type
        );
        $this->addWritePermissionOnItem($file_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->lockItem($file_id, $lock_owner_id);
    }

    /**
     * @param $folder_id
     */
    public function createAdminOnlyItem(int $folder_id, string $title, int $item_type): void
    {
        $read_only_file_id = $this->createItem(
            \REST_TestDataBuilder::DEFAULT_TEMPLATE_PROJECT_ID,
            $folder_id,
            $title,
            $item_type
        );
        $this->addReadPermissionOnItem($read_only_file_id, ProjectUGroup::DOCUMENT_ADMIN);
    }

    public function getUserByName(string $user_name): int
    {
        $this->user = $this->user_manager->getUserByUserName($user_name);
        return $this->user->getId();
    }

    public function getRoot(): ?\Docman_Item
    {
        return $this->docman_item_factory->getRoot($this->project->getID());
    }
}
