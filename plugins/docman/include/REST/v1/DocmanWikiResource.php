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

use Docman_LockFactory;
use Docman_Log;
use Docman_PermissionsManager;
use Docman_SettingsBo;
use Docman_Wiki;
use Luracast\Restler\RestException;
use Project;
use ProjectManager;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\REST\v1\Lock\RestLockUpdater;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetdataObsolescenceDateChecker;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Metadata\MetadataUpdatorBuilder;
use Tuleap\Docman\REST\v1\Metadata\PUTMetadataRepresentation;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiPATCHRepresentation;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiVersionCreator;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiVersionPOSTRepresentation;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\UserManager as RestUserManager;

class DocmanWikiResource extends AuthenticatedResource
{
    /**
     * @var ProjectManager
     */
    private $project_manager;
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
        $this->project_manager   = ProjectManager::instance();
        $this->request_builder   = new DocmanItemsRequestBuilder($this->rest_user_manager, $this->project_manager);
        $this->event_manager     = \EventManager::instance();
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsDocumentItems($id): void
    {
        $this->setHeaders();
    }

    /**
     * Create a new version of an existing wiki document
     *
     * <pre>
     * /!\ This route is <strong> deprecated </strong> <br/>
     * /!\ To create a wiki file version, please use the {id}/version route instead !
     * </pre>
     *
     * <br>
     *
     * @url    PATCH {id}
     * @access hybrid
     *
     * @param int                           $id             Id of the item
     * @param DocmanWikiPATCHRepresentation $representation {@from body}
     *
     * @status 200
     * @throws I18NRestException 400
     * @throws I18NRestException 403
     */

    public function patch(int $id, DocmanWikiPATCHRepresentation $representation): void
    {
        $this->checkAccess();
        $this->setHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);
        $project      = $item_request->getProject();
        $this->addAllEvent($project);

        try {
            $status_id                    = $this->getItemStatusMapper($project)->getItemStatusIdFromItemStatusString(
                $representation->status
            );
            $obsolescence_date_time_stamp = $this->getHardcodedMetadataObsolescenceDateRetriever($project)->getTimeStampOfDate(
                $representation->obsolescence_date,
                new \DateTimeImmutable()
            );
        } catch (Metadata\HardCodedMetadataException $e) {
            throw new I18NRestException(400, $e->getI18NExceptionMessage());
        }

        $this->createNewWikiVersion(
            $representation,
            $item_request,
            $status_id,
            $obsolescence_date_time_stamp,
            $representation->title,
            $representation->description
        );
    }

    /**
     * Delete a wiki document in the document manager
     *
     * Delete an existing wiki document
     *
     * @url    DELETE {id}
     * @access hybrid
     *
     * @param int $id Id of the wiki
     * @param bool $delete_associated_wiki_page {@from query} {@type bool} {@required false}
     *
     * @status 200
     * @throws I18NRestException 400
     * @throws RestException 401
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     */
    public function delete(int $id, bool $delete_associated_wiki_page = false) : void
    {
        $this->checkAccess();
        $this->setHeaders();

        $item_request      = $this->request_builder->buildFromItemId($id);
        $item_to_delete    = $item_request->getItem();
        $current_user      = $this->rest_user_manager->getCurrentUser();
        $project           = $item_request->getProject();
        $validator = $this->getValidator($project, $current_user, $item_to_delete);
        $item_to_delete->accept($validator);
        /** @var \Docman_Wiki $item_to_delete */

        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);

        try {
            (new \Docman_ItemFactory())->deleteSubTree($item_to_delete, $current_user, $delete_associated_wiki_page);
        } catch (DeleteFailedException $exception) {
            throw new I18NRestException(
                403,
                $exception->getI18NExceptionMessage()
            );
        }

        $this->event_manager->processEvent('send_notifications', []);
    }

    /**
     * Create a version of a wiki
     *
     * Create a version of an existing wiki document
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
     * @url    POST {id}/version
     * @access hybrid
     *
     * @param int                                 $id             Id of the file
     * @param DocmanWikiVersionPOSTRepresentation $representation {@from body}
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
        DocmanWikiVersionPOSTRepresentation $representation
    ) {
        $this->checkAccess();
        $this->setVersionHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);
        $this->addAllEvent($item_request->getProject());

        $item = $item_request->getItem();

        $this->createNewWikiVersion(
            $representation,
            $item_request,
            (int)$item->getStatus(),
            (int)$item->getObsolescenceDate(),
            (string)$item->getTitle(),
            (string)$item->getDescription()
        );
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
     * Update the wiki metadata
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
     * @url    PUT {id}/metadata
     * @access hybrid
     *
     * @param int                       $id             Id of the wiki
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

    private function getDocmanItemsEventAdder(): DocmanItemsEventAdder
    {
        return new DocmanItemsEventAdder($this->event_manager);
    }

    private function setHeaders(): void
    {
        Header::allowOptionsPatchDelete();
    }

    /**
     * Lock a specific wiki
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
     * @param int $id Id of the wiki you want to lock
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
     * Unlock an already locked wiki
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
     * @param int  $id Id of the wiki you want to unlock
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

    private function setHeadersForLock(): void
    {
        Header::allowOptionsPostDelete();
    }

    private function getRestLockUpdater(\Project $project): RestLockUpdater
    {
        return new RestLockUpdater(new Docman_LockFactory(new \Docman_LockDao(), new Docman_Log()), $this->getPermissionManager($project));
    }

    private function getPermissionManager(\Project $project): Docman_PermissionsManager
    {
        return Docman_PermissionsManager::instance($project->getGroupId());
    }

    /**
     * @param              $project
     * @param              $current_user
     * @param \Docman_Item $item
     *
     * @return DocumentBeforeModificationValidatorVisitor
     */
    private function getValidator(Project $project, \PFUser $current_user, \Docman_Item $item): DocumentBeforeModificationValidatorVisitor
    {
        return new DocumentBeforeModificationValidatorVisitor(
            $this->getPermissionManager($project),
            $current_user,
            $item,
            new DoesItemHasExpectedTypeVisitor(Docman_Wiki::class)
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

    private function getItemStatusMapper(\Project $project): ItemStatusMapper
    {
        return new ItemStatusMapper(new Docman_SettingsBo($project->getID()));
    }

    private function getHardcodedMetadataObsolescenceDateRetriever(\Project $project): HardcodedMetadataObsolescenceDateRetriever
    {
        return new HardcodedMetadataObsolescenceDateRetriever(
            new HardcodedMetdataObsolescenceDateChecker(
                new Docman_SettingsBo($project->getID())
            )
        );
    }

    private function getWikiVersionCreator(): DocmanWikiVersionCreator
    {
        $updator = (new DocmanItemUpdatorBuilder())->build($this->event_manager);

        return new DocmanWikiVersionCreator(
            new \Docman_VersionFactory(),
            new \Docman_ItemFactory(),
            \EventManager::instance(),
            $updator,
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
        );
    }

    private function createNewWikiVersion(
        DocmanWikiVersionPOSTRepresentation $representation,
        DocmanItemsRequest $item_request,
        int $status,
        int $obsolesence_date,
        string $title,
        ?string $description
    ): void {
        $project      = $item_request->getProject();
        $item         = $item_request->getItem();
        $current_user = $this->rest_user_manager->getCurrentUser();

        $validator = DocumentBeforeVersionCreationValidatorVisitorBuilder::build($project);
        $item->accept(
            $validator,
            [
                'user'          => $current_user,
                'document_type' => \Docman_Wiki::class,
                'title'         => $title,
                'project'       => $project
            ]
        );
        /** @var \Docman_Wiki $item */

        $docman_item_version_creator = $this->getWikiVersionCreator();
        $docman_item_version_creator->createWikiVersion(
            $item,
            $current_user,
            $representation,
            $status,
            $obsolesence_date,
            $title,
            $description
        );
    }
}
