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

use Docman_LinkVersionFactory;
use Docman_LockFactory;
use Docman_Log;
use Docman_PermissionsManager;
use Docman_SettingsBo;
use Docman_VersionFactory;
use Luracast\Restler\RestException;
use Project;
use ProjectManager;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableException;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\REST\v1\Links\DocmanLinkPATCHRepresentation;
use Tuleap\Docman\REST\v1\Links\DocmanLinksValidityChecker;
use Tuleap\Docman\REST\v1\Links\DocmanLinkUpdator;
use Tuleap\Docman\REST\v1\Lock\RestLockUpdater;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetdataObsolescenceDateChecker;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\UserManager as RestUserManager;

class DocmanLinksResource extends AuthenticatedResource
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
     * Patch an link of document manager
     *
     * Create a new version of an existing link document
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
     * @url    PATCH {id}
     * @access hybrid
     *
     * @param int                           $id             Id of the item
     * @param DocmanLinkPATCHRepresentation $representation {@from body}
     *
     * @status 200
     * @throws I18NRestException 400
     * @throws 403
     * @throws 501
     */

    public function patch(int $id, DocmanLinkPATCHRepresentation $representation)
    {
        $this->checkAccess();
        $this->setHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);


        $item = $item_request->getItem();

        $current_user = $this->rest_user_manager->getCurrentUser();

        $project = $item_request->getProject();

        $validator = $this->getValidator($project, $current_user, $item);
        $item->accept($validator, []);
        /** @var \Docman_Link $item */

        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);

        $docman_approval_table_retriever = new ApprovalTableRetriever(new \Docman_ApprovalTableFactoriesFactory(), new Docman_VersionFactory());
        $docman_item_updator             = $this->getLinkUpdator($project);

        try {
            $approval_check = new ApprovalTableUpdateActionChecker($docman_approval_table_retriever);
            $approval_check->checkApprovalTableForItem($representation->approval_table_action, $item);
            $docman_item_updator->updateLink(
                $item,
                $current_user,
                $representation,
                new \DateTimeImmutable()
            );
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
        } catch (Metadata\HardCodedMetadataException $e) {
            throw new I18NRestException(
                400,
                $e->getI18NExceptionMessage()
            );
        }
    }

    /**
     * Delete a link document in the document manager
     *
     * Delete an existing link document
     *
     * @url    DELETE {id}
     * @access hybrid
     *
     * @param int $id Id of the link
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
        $validator      = $this->getValidator($project, $current_user, $item_to_delete);
        $item_to_delete->accept($validator);

        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);

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

    private function getLinkUpdator(Project $project): DocmanLinkUpdator
    {
        $builder                                      = new DocmanItemUpdatorBuilder();
        $updator                                      = $builder->build($this->event_manager);
        $version_factory                              = new \Docman_VersionFactory();
        $docman_item_factory                          = new \Docman_ItemFactory();
        $docman_setting_bo                            = new Docman_SettingsBo($project->getGroupId());
        $hardcoded_metadata_obsolescence_date_checker = new HardcodedMetdataObsolescenceDateChecker(
            $docman_setting_bo
        );
        return new DocmanLinkUpdator(
            $version_factory,
            $updator,
            $docman_item_factory,
            \EventManager::instance(),
            new DocmanLinksValidityChecker(),
            new Docman_LinkVersionFactory(),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            new ItemStatusMapper($docman_setting_bo),
            new HardcodedMetadataObsolescenceDateRetriever(
                $hardcoded_metadata_obsolescence_date_checker
            ),
            $this->getPermissionManager($project)
        );
    }

    /**
     * Lock a specific link
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
     * @param int $id Id of the link you want to lock
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
     * Unlock an already locked link
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
     * @param int  $id Id of the link you want to unlock
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

    private function getValidator(Project $project, \PFUser $current_user, \Docman_Item $item): DocumentBeforeModificationValidatorVisitor
    {
        return new DocumentBeforeModificationValidatorVisitor($this->getPermissionManager($project), $current_user, $item, \Docman_Link::class);
    }
}
