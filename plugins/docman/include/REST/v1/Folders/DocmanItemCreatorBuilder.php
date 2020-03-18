<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1\Folders;

use Docman_FileStorage;
use Docman_ItemDao;
use Docman_ItemFactory;
use Docman_MetadataFactory;
use Docman_MetadataListOfValuesElementDao;
use Docman_MetadataValueDao;
use Docman_MIMETypeDetector;
use Docman_SettingsBo;
use Docman_VersionFactory;
use EventManager;
use PermissionsManager;
use PluginManager;
use Project;
use ProjectManager;
use ReferenceManager;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\Metadata\DocmanMetadataInputValidator;
use Tuleap\Docman\Metadata\DocmanMetadataTypeValueFactory;
use Tuleap\Docman\Metadata\ListOfValuesElement\MetadataListOfValuesElementListBuilder;
use Tuleap\Docman\Metadata\MetadataValueCreator;
use Tuleap\Docman\Metadata\MetadataValueObjectFactory;
use Tuleap\Docman\Metadata\MetadataValueStore;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Docman\REST\v1\AfterItemCreationVisitor;
use Tuleap\Docman\REST\v1\DocmanItemCreator;
use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Docman\REST\v1\Files\EmptyFileToUploadFinisher;
use Tuleap\Docman\REST\v1\Links\DocmanLinksValidityChecker;
use Tuleap\Docman\REST\v1\Metadata\CustomMetadataCollectionBuilder;
use Tuleap\Docman\REST\v1\Metadata\CustomMetadataRepresentationRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetdataObsolescenceDateChecker;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\NullResponseFeedbackWrapper;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetFactory;
use Tuleap\Docman\Upload\Document\DocumentMetadataCreator;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\Document\DocumentToUploadCreator;
use Tuleap\Docman\Upload\Document\DocumentUploadFinisher;
use Tuleap\Docman\Upload\UploadPathAllocatorBuilder;
use Tuleap\Project\REST\UserGroupRetriever;
use UGroupManager;
use UserManager;

class DocmanItemCreatorBuilder
{
    public static function build(Project $project): DocmanItemCreator
    {
        $document_on_going_upload_dao   = new DocumentOngoingUploadDAO();
        $document_upload_path_allocator = (new UploadPathAllocatorBuilder())->getDocumentUploadPathAllocator();

        $docman_plugin = PluginManager::instance()->getPluginByName('docman');
        $docman_root   = $docman_plugin->getPluginInfo()->getPropertyValueForName('docman_root');

        $docman_file_storage = new Docman_FileStorage($docman_root);

        $item_factory                                 = new Docman_ItemFactory($project->getID());
        $permission_manager                           = PermissionsManager::instance();
        $version_factory                              = new Docman_VersionFactory();
        $event_manager                                = EventManager::instance();
        $transaction_executor                         = new DBTransactionExecutorWithConnection(
            DBFactory::getMainTuleapDBConnection()
        );
        $docman_setting_bo                            = new Docman_SettingsBo($project->getGroupId());
        $hardcoded_metadata_obsolescence_date_checker = new HardcodedMetdataObsolescenceDateChecker(
            $docman_setting_bo
        );

        $metadata_factory    = new Docman_MetadataFactory($project->getGroupId());
        $list_values_builder = new MetadataListOfValuesElementListBuilder(new Docman_MetadataListOfValuesElementDao());
        $custom_checker      = new CustomMetadataRepresentationRetriever(
            $metadata_factory,
            $list_values_builder,
            new CustomMetadataCollectionBuilder($metadata_factory, $list_values_builder)
        );

        $ugroup_manager = new UGroupManager();

        $permission_item_updater = new PermissionItemUpdater(
            new NullResponseFeedbackWrapper(),
            $item_factory,
            \Docman_PermissionsManager::instance($project->getID()),
            $permission_manager,
            $event_manager
        );

        $metadata_value_factory = new \Docman_MetadataValueFactory($project->getID());
        return new DocmanItemCreator(
            $item_factory,
            new DocumentOngoingUploadRetriever($document_on_going_upload_dao),
            new DocumentToUploadCreator(
                $document_on_going_upload_dao,
                $transaction_executor,
                new DocumentMetadataCreator(
                    new MetadataValueCreator(
                        new DocmanMetadataInputValidator(),
                        new MetadataValueObjectFactory(
                            new DocmanMetadataTypeValueFactory()
                        ),
                        new MetadataValueStore(
                            new \Docman_MetadataValueDao(),
                            ReferenceManager::instance()
                        )
                    ),
                    new \Docman_MetadataDao(\CodendiDataAccess::instance())
                ),
                $permission_manager,
                $permission_item_updater
            ),
            new AfterItemCreationVisitor(
                $permission_manager,
                $event_manager,
                new \Docman_LinkVersionFactory(),
                $docman_file_storage,
                $version_factory,
                $metadata_value_factory,
                $permission_item_updater
            ),
            new EmptyFileToUploadFinisher(
                new DocumentUploadFinisher(
                    \BackendLogger::getDefaultLogger(),
                    $document_upload_path_allocator,
                    $item_factory,
                    $version_factory,
                    $event_manager,
                    $document_on_going_upload_dao,
                    new Docman_ItemDao(),
                    new Docman_FileStorage($docman_root),
                    new Docman_MIMETypeDetector(),
                    UserManager::instance(),
                    $transaction_executor,
                    new DocmanItemsEventAdder($event_manager),
                    ProjectManager::instance()
                ),
                $document_upload_path_allocator
            ),
            new DocmanLinksValidityChecker(),
            new ItemStatusMapper($docman_setting_bo),
            new HardcodedMetadataObsolescenceDateRetriever(
                $hardcoded_metadata_obsolescence_date_checker
            ),
            $custom_checker,
            new Docman_MetadataValueDao(),
            new DocmanItemPermissionsForGroupsSetFactory(
                $ugroup_manager,
                new UserGroupRetriever($ugroup_manager),
                ProjectManager::instance()
            )
        );
    }
}
