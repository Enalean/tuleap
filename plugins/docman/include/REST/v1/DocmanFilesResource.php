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

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1;

use Docman_File;
use Docman_LockFactory;
use Docman_Log;
use Docman_PermissionsManager;
use Docman_SettingsBo;
use Luracast\Restler\RestException;
use ProjectManager;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ApprovalTable\ApprovalTableException;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\REST\v1\Files\CreatedItemFilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Files\DocmanFilesPATCHRepresentation;
use Tuleap\Docman\REST\v1\Files\DocmanFileVersionCreator;
use Tuleap\Docman\REST\v1\Files\DocmanFileVersionPOSTRepresentation;
use Tuleap\Docman\REST\v1\Lock\RestLockUpdater;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetdataObsolescenceDateChecker;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Metadata\MetadataUpdatorBuilder;
use Tuleap\Docman\REST\v1\Metadata\PUTMetadataRepresentation;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Tuleap\Docman\Upload\Version\DocumentOnGoingVersionToUploadDAO;
use Tuleap\Docman\Upload\Version\VersionToUploadCreator;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\UserManager as RestUserManager;

class DocmanFilesResource extends AuthenticatedResource
{
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var RestUserManager
     */
    private $rest_user_manager;
    /**
     * @var DocmanItemsRequestBuilder
     */
    private $request_builder;

    public function __construct()
    {
        $this->rest_user_manager = RestUserManager::build();
        $this->request_builder   = new DocmanItemsRequestBuilder($this->rest_user_manager, ProjectManager::instance());
        $this->event_manager     = \EventManager::instance();
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsDocumentItems(int $id): void
    {
        $this->setHeaders();
    }

    /**
     * Lock a specific file
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
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

        $current_user = $this->rest_user_manager->getCurrentUser();

        $item_request = $this->request_builder->buildFromItemId($id);
        $item         = $item_request->getItem();
        $project      = $item_request->getProject();

        $validator = $this->getValidator($project, $current_user, $item);
        $item->accept($validator, []);

        $updator = $this->getRestLockUpdater($project);
        $updator->lockItem($item, $current_user);
    }

    /**
     * Unlock an already locked file
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
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

        $current_user = $this->rest_user_manager->getCurrentUser();

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
     * Patch an element of document manager
     *
     * Create a new version of an existing file document
     * <pre>
     * /!\ This route is <strong> deprecated </strong> <br/>
     * /!\ To create a file version, please use the {id}/version route instead !
     * </pre>
     *
     * <pre>
     * approval_table_action should be provided only if item has an existing approval table.<br>
     * Possible values:<br>
     *  * copy: Creates an approval table based on the previous one<br>
     *  * reset: Reset the current approval table<br>
     *  * empty: No approbation needed for the new version of this document<br>
     * </pre>
     *
     * @param int                            $id             Id of the item
     * @param DocmanFilesPATCHRepresentation $representation {@from body}
     *
     * @return CreatedItemFilePropertiesRepresentation
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 501
     *
     * @url    PATCH {id}
     * @access hybrid
     *
     * @deprecated
     */
    public function patch(int $id, DocmanFilesPATCHRepresentation $representation)
    {
        $this->checkAccess();
        $this->setHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);
        $this->addAllEvent($item_request->getProject());

        try {
            $docman_settings_bo = new Docman_SettingsBo($item_request->getProject()->getID());
            $status_mapper      = new ItemStatusMapper($docman_settings_bo);
            $status_id          = $status_mapper->getItemStatusIdFromItemStatusString(
                $representation->status
            );

            $date_retriever               = new HardcodedMetadataObsolescenceDateRetriever(
                new HardcodedMetdataObsolescenceDateChecker($docman_settings_bo)
            );
            $obsolescence_date_time_stamp = $obsolescence_date_time_stamp = $date_retriever->getTimeStampOfDate(
                $representation->obsolescence_date,
                new \DateTimeImmutable()
            );
        } catch (Metadata\HardCodedMetadataException $e) {
            throw new I18NRestException(400, $e->getI18NExceptionMessage());
        }
        return $this->createNewFileVersion($representation, $item_request, $status_id, $obsolescence_date_time_stamp, $representation->title);
    }

    /**
     * Delete a file document in the document manager
     *
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
    public function delete(int $id) : void
    {
        $this->checkAccess();
        $this->setHeaders();

        $item_request   = $this->request_builder->buildFromItemId($id);
        $item_to_delete = $item_request->getItem();
        $current_user   = $this->rest_user_manager->getCurrentUser();
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
        $this->setVersionHeaders();
    }

    /**
     * Create a version of a file
     *
     * Create a version of an existing file document
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
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
     * @param int                                 $id             Id of the file
     * @param DocmanFileVersionPOSTRepresentation $representation {@from body}
     *
     * @return CreatedItemFilePropertiesRepresentation
     *
     * @status 201
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 409
     * @throws RestException 501
     */

    public function postVersion(
        int $id,
        DocmanFileVersionPOSTRepresentation $representation
    ): CreatedItemFilePropertiesRepresentation {
        $this->checkAccess();
        $this->setVersionHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);
        $this->addAllEvent($item_request->getProject());

        $item = $item_request->getItem();
        return $this->createNewFileVersion(
            $representation,
            $item_request,
            (int)$item->getStatus(),
            (int)$item->getObsolescenceDate(),
            (string)$item->getTitle()
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
     * Update the file metadata
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
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
        PUTMetadataRepresentation $representation
    ): void {

        $this->checkAccess();
        $this->setMetadataHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);
        $item         = $item_request->getItem();

        $current_user = $this->rest_user_manager->getCurrentUser();

        $project = $item_request->getProject();

        $validator = $this->getValidator($project, $current_user, $item);
        $item->accept($validator, []);

        $this->addAllEvent($project);

        $updator = MetadataUpdatorBuilder::build($project, $this->event_manager);
        $updator->updateDocumentMetadata(
            $representation,
            $item,
            new \DateTimeImmutable(),
            $current_user
        );
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

    private function getFileVersionCreator(): DocmanFileVersionCreator
    {
        return new DocmanFileVersionCreator(
            new VersionToUploadCreator(
                new DocumentOnGoingVersionToUploadDAO(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            )
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
            new DoesItemHasExpectedTypeVisitor(Docman_File::class)
        );
    }

    /**
     * @param \Project $project
     */
    private function addAllEvent(\Project $project): void
    {
        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);
    }

    /**
     * @return CreatedItemFilePropertiesRepresentation
     * @throws I18NRestException
     * @throws RestException
     */
    private function createNewFileVersion(
        DocmanFileVersionPOSTRepresentation $representation,
        DocmanItemsRequest $item_request,
        int $status,
        int $obsolesence_date,
        string $title
    ): CreatedItemFilePropertiesRepresentation {
        $project      = $item_request->getProject();
        $item         = $item_request->getItem();
        $current_user = $this->rest_user_manager->getCurrentUser();

        try {
            $validator = DocumentBeforeVersionCreationValidatorVisitorBuilder::build($project);
            $item->accept(
                $validator,
                [
                    'user'                  => $current_user,
                    'approval_table_action' => $representation->approval_table_action,
                    'document_type'         => Docman_File::class,
                    'title'                 => $title,
                    'project'               => $project
                ]
            );
        } catch (ApprovalTableException $exception) {
            throw new I18NRestException(
                400,
                $exception->getI18NExceptionMessage()
            );
        }

        try {
            $docman_item_version_creator = $this->getFileVersionCreator();
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
}
