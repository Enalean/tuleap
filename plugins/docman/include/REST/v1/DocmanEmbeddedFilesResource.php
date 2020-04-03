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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1;

use DateTimeImmutable;
use Docman_EmbeddedFile;
use Docman_FileStorage;
use Docman_ItemFactory;
use Docman_LockFactory;
use Docman_Log;
use Docman_PermissionsManager;
use Docman_VersionFactory;
use Luracast\Restler\RestException;
use PermissionsManager;
use PluginManager;
use Project;
use ProjectManager;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ApprovalTable\ApprovalTableException;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedFileVersionPOSTRepresentation;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedFileVersionCreator;
use Tuleap\Docman\REST\v1\Lock\RestLockUpdater;
use Tuleap\Docman\REST\v1\Metadata\MetadataUpdatorBuilder;
use Tuleap\Docman\REST\v1\Metadata\PUTMetadataRepresentation;
use Tuleap\Docman\REST\v1\MoveItem\BeforeMoveVisitor;
use Tuleap\Docman\REST\v1\MoveItem\DocmanItemMover;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetFactory;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetRepresentation;
use Tuleap\Docman\REST\v1\Permissions\PermissionItemUpdaterFromRESTContext;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use UGroupManager;
use UserManager;

class DocmanEmbeddedFilesResource extends AuthenticatedResource
{
    /**
     * @var \EventManager
     */
    private $event_manager;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var DocmanItemsRequestBuilder
     */
    private $request_builder;

    public function __construct()
    {
        $this->user_manager    = UserManager::instance();
        $this->request_builder = new DocmanItemsRequestBuilder($this->user_manager, ProjectManager::instance());
        $this->event_manager   = \EventManager::instance();
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsDocumentItems($id)
    {
        $this->setHeaders();
    }

    /**
     * Move an existing embedded file document
     *
     *
     * @url    PATCH {id}
     * @access hybrid
     *
     * @param int                           $id             Id of the item
     * @param DocmanPATCHItemRepresentation $representation {@from body}
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */

    public function patch(int $id, DocmanPATCHItemRepresentation $representation): void
    {
        $this->checkAccess();
        $this->setHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);
        $project      = $item_request->getProject();
        $this->addAllEvent($project);

        $item_factory = new Docman_ItemFactory();
        $item_mover   = new DocmanItemMover(
            $item_factory,
            new BeforeMoveVisitor(
                new DoesItemHasExpectedTypeVisitor(Docman_EmbeddedFile::class),
                $item_factory,
                new DocumentOngoingUploadRetriever(new DocumentOngoingUploadDAO())
            ),
            $this->getPermissionManager($project),
            $this->event_manager
        );

        $item_mover->moveItem(
            new DateTimeImmutable(),
            $item_request->getItem(),
            UserManager::instance()->getCurrentUser(),
            $representation->move
        );
    }

    /**
     * Delete an existing embedded file document
     *
     * @url    DELETE {id}
     * @access hybrid
     *
     * @param int $id Id of the embedded file
     *
     * @status 200
     * @throws RestException 401
     * @throws I18NRestException 403
     * @throws RestException 404
     */
    public function delete(int $id): void
    {
        $this->checkAccess();
        $this->setHeaders();

        $item_request   = $this->request_builder->buildFromItemId($id);
        $item_to_delete = $item_request->getItem();
        $current_user   = $this->user_manager->getCurrentUser();
        $project        = $item_request->getProject();

        $validator = $this->getValidator($project, $current_user, $item_to_delete);
        $item_to_delete->accept($validator, []);

        $this->addAllEvent($project);

        try {
            (new Docman_ItemFactory())->deleteSubTree($item_to_delete, $current_user, false);
        } catch (DeleteFailedException $exception) {
            throw new I18NRestException(
                403,
                $exception->getI18NExceptionMessage()
            );
        }

        $this->event_manager->processEvent('send_notifications', []);
    }

    private function getDocmanItemsEventAdder(): DocmanItemsEventAdder
    {
        return new DocmanItemsEventAdder($this->event_manager);
    }

    private function setHeaders(): void
    {
        Header::allowOptionsPatchDelete();
    }

    /**
     * Lock a specific embedded file document
     *
     * @param int $id Id of the embedded  file file you want to lock
     *
     * @url    POST {id}/lock
     * @access hybrid
     * @status 201
     *
     * @throws I18NRestException 400
     * @throws RestException 401
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     *
     */
    public function postLock(int $id): void
    {
        $this->checkAccess();
        $this->setHeadersForLock();

        $current_user = $this->user_manager->getCurrentUser();

        $item_request = $this->request_builder->buildFromItemId($id);
        $item         = $item_request->getItem();
        $project      = $item_request->getProject();

        $validator = $this->getValidator($project, $current_user, $item);
        $item->accept($validator, []);

        $updator = $this->getRestLockUpdater($project);
        $updator->lockItem($item, $current_user);
    }

    /**
     * Unlock an already locked embedded file document
     *
     * @param int $id Id of the embbeded file you want to unlock
     *
     * @url    DELETE {id}/lock
     * @access hybrid
     * @status 200
     *
     * @throws I18NRestException 400
     * @throws RestException 401
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     */
    public function deleteLock(int $id): void
    {
        $this->checkAccess();
        $this->setHeadersForLock();

        $current_user = $this->user_manager->getCurrentUser();

        $item_request = $this->request_builder->buildFromItemId($id);
        $item         = $item_request->getItem();
        $project      = $item_request->getProject();

        $validator = $this->getValidator($project, $current_user, $item);
        $item->accept($validator, []);

        $updator = $this->getRestLockUpdater($project);
        $updator->unlockItem($item, $current_user);
    }

    /**
     * Create a version of an embedded file document
     *
     * <pre>
     * approval_table_action should be provided only if item has an existing approval table.<br>
     * Possible values:<br>
     *  * copy: Creates an approval table based on the previous one<br>
     *  * reset: Reset the current approval table<br>
     *  * empty: No approbation needed for the new version of this document<br>
     * </pre>
     *
     * @url    POST {id}/version
     * @access hybrid
     *
     * @param int                                         $id             Id of the file
     * @param DocmanEmbeddedFileVersionPOSTRepresentation $representation {@from body}
     *
     * @status 200
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 409
     * @throws RestException 501
     */

    public function postVersion(
        int $id,
        DocmanEmbeddedFileVersionPOSTRepresentation $representation
    ) {
        $this->checkAccess();
        $this->setVersionHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);
        $this->addAllEvent($item_request->getProject());

        $item = $item_request->getItem();

        $this->createNewEmbeddedFileVersion(
            $representation,
            $item_request,
            (int) $item->getStatus(),
            (int) $item->getObsolescenceDate(),
            (string) $item->getTitle(),
            (string) $item->getDescription()
        );
    }

    /**
     * @url OPTIONS {id}/metadata
     */
    public function optionsMetadata(int $id): void
    {
        $this->setMetadataHeaders();
    }

    private function setMetadataHeaders()
    {
        Header::allowOptionsPut();
    }

    /**
     * Update the embedded file document metadata
     *
     * @url    PUT {id}/metadata
     * @access hybrid
     *
     * @param int                       $id             Id of the embedded file
     * @param PUTMetadataRepresentation $representation {@from body}
     *
     * @status 200
     * @throws I18NRestException 400
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     * @throws RestException 404
     */
    public function putMetadata(
        int $id,
        PUTMetadataRepresentation $representation
    ): void {
        $this->checkAccess();
        $this->setMetadataHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);
        $item         = $item_request->getItem();

        $current_user = $this->user_manager->getCurrentUser();

        $project = $item_request->getProject();

        $validator = $this->getValidator($project, $current_user, $item);
        $item->accept($validator, []);

        $this->addAllEvent($project);

        $updator = MetadataUpdatorBuilder::build($project, $this->event_manager);
        $updator->updateDocumentMetadata(
            $representation,
            $item,
            $current_user
        );
    }

    private function setHeadersForLock(): void
    {
        Header::allowOptionsPostDelete();
    }

    /**
     * @url OPTIONS {id}/version
     */
    public function optionsNewVersion(int $id): void
    {
        $this->setVersionHeaders();
    }

    private function setVersionHeaders(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * @url OPTIONS {id}/lock
     */
    public function optionsIdLock(int $id): void
    {
        $this->setHeadersForLock();
    }

    /**
     * @url OPTIONS {id}/permissions
     */
    public function optionsPermissions(int $id): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Update permissions of an embedded file document
     *
     * @url    PUT {id}/permissions
     * @access hybrid
     *
     * @param int $id Id of the embedded file document
     * @param DocmanItemPermissionsForGroupsSetRepresentation $representation {@from body}
     *
     * @status 200
     *
     * @throws RestException 400
     */
    public function putPermissions(int $id, DocmanItemPermissionsForGroupsSetRepresentation $representation): void
    {
        $this->checkAccess();
        $this->optionsPermissions($id);

        $item_request = $this->request_builder->buildFromItemId($id);
        $project      = $item_request->getProject();
        $item         = $item_request->getItem();
        $user         = $item_request->getUser();

        $item->accept($this->getValidator($project, $user, $item), []);

        $this->addAllEvent($project);

        $docman_permission_manager     = $this->getPermissionManager($project);
        $ugroup_manager                = new UGroupManager();
        $permissions_rest_item_updater = new PermissionItemUpdaterFromRESTContext(
            new PermissionItemUpdater(
                new NullResponseFeedbackWrapper(),
                Docman_ItemFactory::instance($project->getID()),
                $docman_permission_manager,
                PermissionsManager::instance(),
                $this->event_manager
            ),
            $docman_permission_manager,
            new DocmanItemPermissionsForGroupsSetFactory(
                $ugroup_manager,
                new UserGroupRetriever($ugroup_manager),
                ProjectManager::instance()
            )
        );
        $permissions_rest_item_updater->updateItemPermissions($item, $user, $representation);
    }

    private function getPermissionManager(\Project $project): Docman_PermissionsManager
    {
        return Docman_PermissionsManager::instance($project->getGroupId());
    }

    private function getValidator(Project $project, \PFUser $current_user, \Docman_Item $item): DocumentBeforeModificationValidatorVisitor
    {
        return new DocumentBeforeModificationValidatorVisitor(
            $this->getPermissionManager($project),
            $current_user,
            $item,
            new DoesItemHasExpectedTypeVisitor(Docman_EmbeddedFile::class)
        );
    }

    private function getRestLockUpdater(\Project $project): RestLockUpdater
    {
        return new RestLockUpdater(new Docman_LockFactory(new \Docman_LockDao(), new Docman_Log()), $this->getPermissionManager($project));
    }

    private function addAllEvent(\Project $project): void
    {
        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);
    }

    private function getEmbeddedFileVersionCreator(): EmbeddedFileVersionCreator
    {
        $docman_plugin        = PluginManager::instance()->getPluginByName('docman');
        $docman_root          = $docman_plugin->getPluginInfo()->getPropertyValueForName('docman_root');
        $item_updator_builder = new DocmanItemUpdatorBuilder();
        return new EmbeddedFileVersionCreator(
            new Docman_FileStorage($docman_root),
            new Docman_VersionFactory(),
            new Docman_ItemFactory(),
            $item_updator_builder->build($this->event_manager),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            new PostUpdateEventAdder(
                \ProjectManager::instance(),
                new DocmanItemsEventAdder($this->event_manager),
                $this->event_manager
            )
        );
    }

    private function createNewEmbeddedFileVersion(
        DocmanEmbeddedFileVersionPOSTRepresentation $representation,
        DocmanItemsRequest $item_request,
        int $status,
        int $obsolesence_date,
        string $title,
        ?string $description
    ) {
        $project      = $item_request->getProject();
        $item         = $item_request->getItem();
        $current_user = $this->user_manager->getCurrentUser();

        try {
            $validator = DocumentBeforeVersionCreationValidatorVisitorBuilder::build($project);
            $item->accept(
                $validator,
                [
                    'user'                  => $current_user,
                    'approval_table_action' => $representation->approval_table_action,
                    'document_type'         => Docman_EmbeddedFile::class,
                    'title'                 => $title,
                    'project'               => $project
                ]
            );
            assert($item instanceof Docman_EmbeddedFile);
        } catch (ApprovalTableException $exception) {
            throw new I18NRestException(
                400,
                $exception->getI18NExceptionMessage()
            );
        }

        try {
            $docman_item_version_creator = $this->getEmbeddedFileVersionCreator();
            $docman_item_version_creator->createEmbeddedFileVersion(
                $item,
                $current_user,
                $representation,
                new DateTimeImmutable(),
                $status,
                $obsolesence_date,
                $title,
                $description
            );
        } catch (UploadMaxSizeExceededException $exception) {
            throw new RestException(
                400,
                $exception->getMessage()
            );
        }
    }
}
