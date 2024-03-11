<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
use Docman_File;
use Docman_ItemFactory;
use Docman_LockDao;
use Docman_LockFactory;
use Docman_Log;
use Docman_PermissionsManager;
use Luracast\Restler\RestException;
use PermissionsManager;
use Project;
use ProjectManager;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ApprovalTable\ApprovalTableException;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\FilenamePattern\FilenameBuilder;
use Tuleap\Docman\FilenamePattern\FilenamePatternRetriever;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Docman\REST\v1\Files\CreatedItemFilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Files\DocmanFileVersionCreator;
use Tuleap\Docman\REST\v1\Files\DocmanFileVersionPOSTRepresentation;
use Tuleap\Docman\REST\v1\Lock\RestLockUpdater;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Metadata\MetadataUpdatorBuilder;
use Tuleap\Docman\REST\v1\Metadata\PUTMetadataRepresentation;
use Tuleap\Docman\REST\v1\MoveItem\BeforeMoveVisitor;
use Tuleap\Docman\REST\v1\MoveItem\DocmanItemMover;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetFactory;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetRepresentation;
use Tuleap\Docman\REST\v1\Permissions\PermissionItemUpdaterFromRESTContext;
use Tuleap\Docman\Settings\SettingsDAO;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Tuleap\Docman\Upload\Version\DocumentOnGoingVersionToUploadDAO;
use Tuleap\Docman\Upload\Version\VersionToUploadCreator;
use Tuleap\Docman\Version\CoAuthorDao;
use Tuleap\Docman\Version\VersionDao;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use UGroupManager;
use UserManager;

final class DocmanFilesResource extends AuthenticatedResource
{
    private const MAX_LIMIT = 50;

    private \EventManager $event_manager;
    private UserManager $user_manager;
    private DocmanItemsRequestBuilder $request_builder;

    public function __construct()
    {
        $this->user_manager    = UserManager::instance();
        $this->request_builder = new DocmanItemsRequestBuilder($this->user_manager, ProjectManager::instance());
        $this->event_manager   = \EventManager::instance();
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsDocumentItems(int $id): void
    {
        $this->setHeaders();
    }

    /**
     * Lock a specific file document
     *
     * @param int $id Id of the file you want to lock
     *
     * @throws I18NRestException 400
     * @throws RestException 401
     * @throws I18NRestException 403
     * @throws RestException 404
     *
     * @url    POST {id}/lock
     * @access hybrid
     * @status 201
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
     * Unlock an already locked file document
     *
     * @param int  $id Id of the file you want to unlock
     *
     * @url    DELETE {id}/lock
     * @access hybrid
     * @status 200
     *
     * @throws I18NRestException 400
     * @throws RestException 401
     * @throws I18NRestException 403
     * @throws RestException 404
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
     * @url OPTIONS {id}/lock
     */
    public function optionsIdLock(int $id): void
    {
        $this->setHeadersForLock();
    }

    /**
     * Move an existing file document
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
                new DoesItemHasExpectedTypeVisitor(Docman_File::class),
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
     * Delete an existing file document
     *
     * @url    DELETE {id}
     * @access hybrid
     *
     * @param int $id Id of the item
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
            (new \Docman_ItemFactory())->deleteSubTree($item_to_delete, $current_user, false);
        } catch (DeleteFailedException $exception) {
            throw new I18NRestException(
                403,
                $exception->getI18NExceptionMessage()
            );
        }

        $this->event_manager->processEvent('send_notifications', []);
    }

    /**
     * @url OPTIONS {id}/version
     */
    public function optionsNewVersion(int $id): void
    {
        Header::allowOptionsPost();
    }

    /**
     * @url    POST {id}/version
     * @access protected
     *
     * @param DocmanFileVersionPOSTRepresentation $representation {@from body}
     *
     * @status 201
     * @hide Only exist for backward compatibility
     */
    public function postVersion(
        int $id,
        DocmanFileVersionPOSTRepresentation $representation,
    ): CreatedItemFilePropertiesRepresentation {
        return $this->postVersions($id, $representation);
    }

    /**
     * Create a version of a file
     *
     * <pre>
     * approval_table_action should be provided only if item has an existing approval table.<br>
     * Possible values:<br>
     *  * copy: Creates an approval table based on the previous one<br>
     *  * reset: Reset the current approval table<br>
     *  * empty: No approbation needed for the new version of this document<br>
     * </pre>
     *
     * @url    POST {id}/versions
     * @access protected
     *
     * @param int                                 $id             Id of the file
     * @param DocmanFileVersionPOSTRepresentation $representation {@from body}
     *
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 409
     * @throws RestException 501
     */

    public function postVersions(
        int $id,
        DocmanFileVersionPOSTRepresentation $representation,
    ): CreatedItemFilePropertiesRepresentation {
        $this->checkAccess();

        $item_request = $this->request_builder->buildFromItemId($id);
        $this->addAllEvent($item_request->getProject());

        $item = $item_request->getItem();
        return $this->createNewFileVersion(
            $representation,
            $item_request,
            (int) $item->getStatus(),
            (int) $item->getObsolescenceDate(),
            (string) $item->getTitle()
        );
    }

    /**
     * @url OPTIONS {id}/metadata
     */
    public function optionsMetadata(int $id): void
    {
        $this->setMetadataHeaders();
    }

    /**
     * Update the file document metadata
     *
     * @url    PUT {id}/metadata
     * @access hybrid
     *
     * @param int                       $id             Id of the file
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
        PUTMetadataRepresentation $representation,
    ): void {
        $this->checkAccess();
        $this->setMetadataHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);
        $item         = $item_request->getItem();

        $current_user = $this->user_manager->getCurrentUser();

        $project = $item_request->getProject();

        if (! $this->getPermissionManager($project)->userCanUpdateItemProperties($current_user, $item)) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-docman', 'You are not allowed to write this item.')
            );
        }

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

    /**
     * @url OPTIONS {id}/permissions
     */
    public function optionsPermissions(int $id): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Update permissions of a file document
     *
     * @url    PUT {id}/permissions
     * @access hybrid
     *
     * @param int $id Id of the file document
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

    private function getDocmanItemsEventAdder(): DocmanItemsEventAdder
    {
        return new DocmanItemsEventAdder($this->event_manager);
    }

    private function setMetadataHeaders()
    {
        Header::allowOptionsPut();
    }

    private function setHeaders(): void
    {
        Header::allowOptionsPatchDelete();
    }

    private function setVersionHeaders(): void
    {
        Header::allowOptionsPost();
    }

    private function setHeadersForLock(): void
    {
        Header::allowOptionsPostDelete();
    }

    private function getFileVersionCreator(Project $project): DocmanFileVersionCreator
    {
        return new DocmanFileVersionCreator(
            new VersionToUploadCreator(
                new DocumentOnGoingVersionToUploadDAO(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new Docman_LockFactory(new Docman_LockDao(), new Docman_Log()),
            new FilenameBuilder(new FilenamePatternRetriever(new SettingsDAO()), new ItemStatusMapper(new \Docman_SettingsBo($project->getID())))
        );
    }

    private function getRestLockUpdater(\Project $project): RestLockUpdater
    {
        return new RestLockUpdater(new Docman_LockFactory(new \Docman_LockDao(), new Docman_Log()), $this->getPermissionManager($project));
    }

    private function getPermissionManager(\Project $project): Docman_PermissionsManager
    {
        return Docman_PermissionsManager::instance($project->getGroupId());
    }

    private function getValidator(\Project $project, \PFUser $current_user, \Docman_Item $item): DocumentBeforeModificationValidatorVisitor
    {
        return new DocumentBeforeModificationValidatorVisitor(
            $this->getPermissionManager($project),
            $current_user,
            $item,
            new DoesItemHasExpectedTypeVisitor(Docman_File::class),
        );
    }

    private function addAllEvent(\Project $project): void
    {
        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    private function createNewFileVersion(
        DocmanFileVersionPOSTRepresentation $representation,
        DocmanItemsRequest $item_request,
        int $status,
        int $obsolesence_date,
        string $title,
    ): CreatedItemFilePropertiesRepresentation {
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
                    'document_type'         => Docman_File::class,
                    'title'                 => $title,
                    'project'               => $project,
                ]
            );
        } catch (ApprovalTableException $exception) {
            throw new I18NRestException(
                400,
                $exception->getI18NExceptionMessage()
            );
        }

        try {
            $docman_item_version_creator = $this->getFileVersionCreator($project);
            return $docman_item_version_creator->createFileVersion(
                $item,
                $current_user,
                $representation,
                new \DateTimeImmutable(),
                $status,
                $obsolesence_date
            );
        } catch (UploadMaxSizeExceededException $exception) {
            throw new RestException(
                400,
                $exception->getMessage()
            );
        }
    }

    /**
     * Get the versions of an item
     *
     * Versions are sorted from newest to oldest.
     *
     * @url    GET {id}/versions
     * @access hybrid
     *
     * @param int $id Id of the item
     * @param int $limit Number of elements displayed {@from path}{@min 0}{@max 100}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type \Tuleap\Docman\REST\v1\Files\FileVersionRepresentation}
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     *
     */
    public function getVersions(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $this->checkAccess();
        $this->optionsDocumentVersions($id);

        $items_request = $this->request_builder->buildFromItemId($id);
        $item          = $items_request->getItem();
        $user          = $items_request->getUser();
        $project       = $items_request->getProject();

        $item->accept($this->getValidator($project, $user, $item), []);
        // validator make sure that we have a File but we still need $item to have the correct type
        assert($item instanceof Docman_File);

        $item_representation_builder = new VersionRepresentationCollectionBuilder(
            new VersionDao(),
            new CoAuthorDao(),
            UserManager::instance(),
            new \Docman_ApprovalTableFactoriesFactory()
        );

        $items_representation = $item_representation_builder->buildVersionsCollection($item, $limit, $offset);

        Header::sendPaginationHeaders($limit, $offset, $items_representation->getTotalSize(), self::MAX_LIMIT);

        return $items_representation->getPaginatedFileversionrepresentations();
    }

    /**
     * @url OPTIONS {id}/versions
     */
    public function optionsDocumentVersions(int $id): void
    {
        Header::allowOptionsGetPost();
    }
}
