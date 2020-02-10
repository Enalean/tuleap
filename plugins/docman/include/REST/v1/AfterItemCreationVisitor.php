<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Docman\REST\v1;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Docman\REST\v1\Metadata\POSTCustomMetadataRepresentation;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSet;

/**
 * @template-implements ItemVisitor<void>
 */
class AfterItemCreationVisitor implements ItemVisitor
{
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var \PermissionsManager
     */
    private $permission_manager;
    /**
     * @var \Docman_LinkVersionFactory
     */
    private $docman_link_version_factory;
    /**
     * @var \Docman_FileStorage
     */
    private $docman_file_storage;
    /**
     * @var \Docman_VersionFactory
     */
    private $docman_version_factory;
    /**
     * @var \Docman_MetadataValueFactory
     */
    private $metadata_value_factory;
    /**
     * @var PermissionItemUpdater
     */
    private $permission_item_updater;

    public function __construct(
        \PermissionsManager $permission_manager,
        \EventManager $event_manager,
        \Docman_LinkVersionFactory $docman_link_version_factory,
        \Docman_FileStorage $docman_file_storage,
        \Docman_VersionFactory $docman_version_factory,
        \Docman_MetadataValueFactory $metadata_value_factory,
        PermissionItemUpdater $permission_item_updater
    ) {
        $this->permission_manager          = $permission_manager;
        $this->event_manager               = $event_manager;
        $this->docman_link_version_factory = $docman_link_version_factory;
        $this->docman_file_storage         = $docman_file_storage;
        $this->docman_version_factory      = $docman_version_factory;
        $this->metadata_value_factory      = $metadata_value_factory;
        $this->permission_item_updater     = $permission_item_updater;
    }

    public function visitFolder(Docman_Folder $item, array $params = [])
    {
        $this->instantiatePermissions($item, $params['permissions_for_groups']);
        $this->storeCustomMetadata($item, $params['formatted_metadata']);
        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_FOLDER, $params);
        $this->triggerPostCreationEvents($params);
    }

    public function visitWiki(Docman_Wiki $item, array $params = [])
    {
        $this->instantiatePermissions($item, $params['permissions_for_groups']);
        $this->storeCustomMetadata($item, $params['formatted_metadata']);
        $params['wiki_page'] = $item->getPagename();
        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_PHPWIKI_PAGE, $params);
        $this->triggerPostCreationEvents($params);
    }

    public function visitLink(Docman_Link $item, array $params = [])
    {
        $creation_time = $params['creation_time'];

        $this->docman_link_version_factory->create(
            $item,
            dgettext('tuleap-docman', 'Initial version'),
            dgettext('tuleap-docman', 'Initial version'),
            $creation_time->getTimestamp()
        );
        $this->instantiatePermissions($item, $params['permissions_for_groups']);
        $this->storeCustomMetadata($item, $params['formatted_metadata']);
        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_LINK, $params);
        $this->triggerPostCreationEvents($params);
    }

    public function visitFile(Docman_File $item, array $params = [])
    {
        throw new CannotCreateThisItemTypeException();
    }


    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = [])
    {
        $initial_version_number = 1;

        $created_file_path = $this->docman_file_storage->store(
            $params['content'],
            $item->getGroupId(),
            $item->getId(),
            $initial_version_number
        );

        $new_embedded_version_row = [
            'item_id'   => $item->getId(),
            'number'    => $initial_version_number,
            'user_id'   => $params['user']->getId(),
            'label'     => '',
            'changelog' => dgettext('tuleap-docman', 'Initial version'),
            'date'      => $item->getCreateDate(),
            'filename'  => basename($created_file_path),
            'filesize'  => filesize($created_file_path),
            'filetype'  => 'text/html',
            'path'      => $created_file_path
        ];

        $this->docman_version_factory->create($new_embedded_version_row);
        $this->instantiatePermissions($item, $params['permissions_for_groups']);
        $this->storeCustomMetadata($item, $params['formatted_metadata']);
        $params['version'] = $this->docman_version_factory->getCurrentVersionForItem($item);
        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_FILE, $params);
        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_FILE_VERSION, $params);
        $this->triggerPostCreationEvents($params);
    }

    public function visitEmpty(Docman_Empty $item, array $params = [])
    {
        $this->instantiatePermissions($item, $params['permissions_for_groups']);
        $this->storeCustomMetadata($item, $params['formatted_metadata']);
        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_EMPTY, $params);
        $this->triggerPostCreationEvents($params);
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
        throw new CannotCreateThisItemTypeException();
    }

    private function triggerPostCreationEvents($params): void
    {
        $this->event_manager->processEvent('plugin_docman_event_add', $params);
        $this->event_manager->processEvent('send_notifications', []);
    }

    private function instantiatePermissions(
        Docman_Item $item,
        ?DocmanItemPermissionsForGroupsSet $permissions_for_groups
    ): void {
        if ($permissions_for_groups === null) {
            $this->permission_manager->clonePermissions(
                $item->getParentId(),
                $item->getId(),
                ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE']
            );
        } else {
            $this->permission_item_updater->initPermissionsOnNewlyCreatedItem(
                $item,
                $permissions_for_groups->toPermissionsPerUGroupIDAndTypeArray()
            );
        }
    }

    /**
     * @param POSTCustomMetadataRepresentation[] $metadata_representations
     */
    private function storeCustomMetadata(Docman_Item $item, array $metadata_representations): void
    {
        $this->metadata_value_factory->createFromRow($item->getId(), $metadata_representations);
    }
}
