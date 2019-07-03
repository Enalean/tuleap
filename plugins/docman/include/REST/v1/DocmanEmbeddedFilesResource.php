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

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1;

use Docman_EmbeddedFile;
use Docman_FileStorage;
use Docman_LockFactory;
use Docman_Log;
use Docman_PermissionsManager;
use Docman_SettingsBo;
use Docman_VersionFactory;
use Luracast\Restler\RestException;
use PluginManager;
use Project;
use ProjectManager;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\Actions\OwnerRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableException;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\Metadata\MetadataEventProcessor;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedFilesPATCHRepresentation;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedFileVersionPOSTRepresentation;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedFileVersionCreator;
use Tuleap\Docman\REST\v1\Lock\RestLockUpdater;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetdataObsolescenceDateChecker;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Metadata\MetadataUpdator;
use Tuleap\Docman\REST\v1\Metadata\PUTMetadataRepresentation;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\UserManager as RestUserManager;

class DocmanEmbeddedFilesResource extends AuthenticatedResource
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
    public function optionsDocumentItems($id)
    {
        $this->setHeaders();
    }

    /**
     * Create a new version of an existing embedded file document
     *
     * <pre>
     * /!\ This route is <strong> deprecated </strong> <br/>
     * /!\ To create an embedded file version, please use the {id}/version route instead !
     * </pre>
     *
     * <br>
     * <pre>
     * approval_table_action should be provided only if item has an existing approval table.<br>
     * Possible values:<br>
     *  * copy: Creates an approval table based on the previous one<br>
     *  * reset: Reset the current approval table<br>
     *  * empty: No approbation needed for the new version of this document<br>
     * </pre>
     *
     * @url    PATCH {id}
     * @access hybrid
     *
     * @param int                                    $id             Id of the item
     * @param DocmanEmbeddedFilesPATCHRepresentation $representation {@from body}
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 409
     * @throws RestException 501
     */

    public function patch(int $id, DocmanEmbeddedFilesPATCHRepresentation $representation)
    {
        $this->checkAccess();
        $this->setHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);
        $project      = $item_request->getProject();
        $this->addAllEvent($project);

        try {
            $status_id          = $this->getItemStatusMapper($project)->getItemStatusIdFromItemStatusString(
                $representation->status
            );
            $obsolescence_date_time_stamp = $this->getHardcodedMetadataObsolescenceDateRetriever($project)->getTimeStampOfDate(
                $representation->obsolescence_date,
                new \DateTimeImmutable()
            );
        } catch (Metadata\HardCodedMetadataException $e) {
            throw new I18NRestException(400, $e->getI18NExceptionMessage());
        }
        $this->createNewEmbeddedFileVersion(
            $representation,
            $item_request,
            $status_id,
            $obsolescence_date_time_stamp,
            $representation->title,
            $representation->description
        );
    }

    /**
     * Delete an embedded file document in the document manager
     *
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

    private function getDocmanItemsEventAdder(): DocmanItemsEventAdder
    {
        return new DocmanItemsEventAdder($this->event_manager);
    }

    private function setHeaders(): void
    {
        Header::allowOptionsPatchDelete();
    }

    /**
     * Lock a specific embedded file
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
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
     * Unlock an already locked embedded file
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
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
     * Create a version of an embedded file
     *
     * Create a version of an existing embedded file document
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
            (int)$item->getStatus(),
            (int)$item->getObsolescenceDate(),
            (string)$item->getTitle(),
            (string)$item->getDescription()
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
     * Update the embedded file metadata
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
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

        $current_user = $this->rest_user_manager->getCurrentUser();

        $project = $item_request->getProject();

        $validator = $this->getValidator($project, $current_user, $item);
        $item->accept($validator, []);

        $this->addAllEvent($project);

        $updator = $this->getHardcodedMetadataUpdator($project);
        $updator->updateDocumentMetadata(
            $representation,
            $item,
            new \DateTimeImmutable(),
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

    private function getPermissionManager(\Project $project): Docman_PermissionsManager
    {
        return Docman_PermissionsManager::instance($project->getGroupId());
    }

    private function getValidator(Project $project, \PFUser $current_user, \Docman_Item $item): DocumentBeforeModificationValidatorVisitor
    {
        return new DocumentBeforeModificationValidatorVisitor($this->getPermissionManager($project), $current_user, $item, \Docman_EmbeddedFile::class);
    }

    private function getRestLockUpdater(\Project $project): RestLockUpdater
    {
        return new RestLockUpdater(new Docman_LockFactory(new \Docman_LockDao(), new Docman_Log()), $this->getPermissionManager($project));
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


    private function getVersionValidator(\Project $project, \PFUser $current_user, \Docman_Item $item): DocumentBeforeVersionCreationValidatorVisitor
    {
        $docman_approval_table_retriever = new ApprovalTableRetriever(
            new \Docman_ApprovalTableFactoriesFactory(),
            new Docman_VersionFactory()
        );
        $approval_check                  = new ApprovalTableUpdateActionChecker($docman_approval_table_retriever);
        return new DocumentBeforeVersionCreationValidatorVisitor(
            $this->getPermissionManager($project),
            $current_user,
            $item,
            Docman_EmbeddedFile::class,
            $approval_check
        );
    }

    private function getEmbeddedFileVersionCreator(): EmbeddedFileVersionCreator
    {
        $docman_plugin        = PluginManager::instance()->getPluginByName('docman');
        $docman_root          = $docman_plugin->getPluginInfo()->getPropertyValueForName('docman_root');
        $item_updator_builder = new DocmanItemUpdatorBuilder();
        return new EmbeddedFileVersionCreator(
            new Docman_FileStorage($docman_root),
            new Docman_VersionFactory(),
            new \Docman_ItemFactory(),
            $item_updator_builder->build($this->event_manager),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
        );
    }

    private function getHardcodedMetadataUpdator(\Project $project): MetadataUpdator
    {
        $user_manager = \UserManager::instance();
        return new MetadataUpdator(
            new \Docman_ItemFactory(),
            $this->getItemStatusMapper($project),
            $this->getHardcodedMetadataObsolescenceDateRetriever($project),
            $user_manager,
            new OwnerRetriever($user_manager),
            new MetadataEventProcessor($this->event_manager)
        );
    }

    private function getItemStatusMapper(\Project $project): ItemStatusMapper
    {
        return new ItemStatusMapper(new Docman_SettingsBo($project->getID()));
    }

    private function getHardcodedMetadataObsolescenceDateRetriever(
        \Project $project
    ): HardcodedMetadataObsolescenceDateRetriever {
        return new HardcodedMetadataObsolescenceDateRetriever(
            new HardcodedMetdataObsolescenceDateChecker(
                new Docman_SettingsBo($project->getID())
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
        $current_user = $this->rest_user_manager->getCurrentUser();

        try {
            $validator = $this->getVersionValidator($project, $current_user, $item);
            $item->accept(
                $validator,
                ['user' => $current_user, 'approval_table_action' => $representation->approval_table_action]
            );
            /** @var \Docman_File $item */
        } catch (ExceptionItemIsLockedByAnotherUser $exception) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-docman', 'Document is locked by another user.')
            );
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
                new \DateTimeImmutable(),
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
