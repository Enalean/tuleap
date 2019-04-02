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
use Docman_MIMETypeDetector;
use Docman_VersionFactory;
use EventManager;
use PermissionsManager;
use PluginManager;
use Project;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\REST\v1\AfterItemCreationVisitor;
use Tuleap\Docman\REST\v1\DocmanItemCreator;
use Tuleap\Docman\REST\v1\EmptyFileToUploadFinisher;
use Tuleap\Docman\REST\v1\Links\DocmanLinksValidityChecker;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\Document\DocumentToUploadCreator;
use Tuleap\Docman\Upload\Document\DocumentUploadFinisher;
use Tuleap\Docman\Upload\Document\DocumentUploadPathAllocator;
use UserManager;

class DocmanItemCreatorBuilder
{
    public static function build(Project $project): DocmanItemCreator
    {
        $document_on_going_upload_dao   = new DocumentOngoingUploadDAO();
        $document_upload_path_allocator = new DocumentUploadPathAllocator();

        $docman_plugin = PluginManager::instance()->getPluginByName('docman');
        $docman_root   = $docman_plugin->getPluginInfo()->getPropertyValueForName('docman_root');

        $docman_file_storage = new Docman_FileStorage($docman_root);

        $item_factory         = new Docman_ItemFactory($project->getID());
        $permission_manager   = PermissionsManager::instance();
        $version_factory      = new Docman_VersionFactory();
        $event_manager        = EventManager::instance();
        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

        return new DocmanItemCreator(
            $item_factory,
            new DocumentOngoingUploadRetriever($document_on_going_upload_dao),
            new DocumentToUploadCreator(
                $document_on_going_upload_dao,
                $transaction_executor
            ),
            new AfterItemCreationVisitor(
                $permission_manager,
                $event_manager,
                new \Docman_LinkVersionFactory(),
                $docman_file_storage,
                $version_factory
            ),
            new EmptyFileToUploadFinisher(
                new DocumentUploadFinisher(
                    new \BackendLogger(),
                    $document_upload_path_allocator,
                    $item_factory,
                    $version_factory,
                    $permission_manager,
                    $event_manager,
                    $document_on_going_upload_dao,
                    new Docman_ItemDao(),
                    new Docman_FileStorage($docman_root),
                    new Docman_MIMETypeDetector(),
                    UserManager::instance(),
                    $transaction_executor
                ),
                $document_upload_path_allocator
            ),
            new DocmanLinksValidityChecker()
        );
    }
}
