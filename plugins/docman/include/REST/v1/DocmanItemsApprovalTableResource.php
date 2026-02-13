<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

use Docman_ApprovalTable;
use Docman_ApprovalTableFactoriesFactory;
use Docman_ApprovalTableReviewerFactory;
use Docman_ApprovalTableVersionnedFactory;
use Docman_Item;
use Docman_PermissionsManager;
use Docman_VersionFactory;
use EventManager;
use Lcobucci\Clock\SystemClock;
use Luracast\Restler\RestException;
use Project;
use ProjectManager;
use Psr\Log\LoggerInterface;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\Docman\Item\VersionOpenHrefVisitor;
use Tuleap\Docman\Notifications\NotificationBuilders;
use Tuleap\Docman\ResponseFeedbackWrapper;
use Tuleap\Docman\REST\v1\ApprovalTable\ApprovalTableAction;
use Tuleap\Docman\REST\v1\ApprovalTable\ApprovalTablePatchRepresentation;
use Tuleap\Docman\REST\v1\ApprovalTable\ApprovalTablePostRepresentation;
use Tuleap\Docman\REST\v1\ApprovalTable\ApprovalTablePutRepresentation;
use Tuleap\Docman\REST\v1\ApprovalTable\ApprovalTableReviewPutRepresentation;
use Tuleap\Docman\REST\v1\ApprovalTable\ApprovalTableReviewUpdater;
use Tuleap\Docman\REST\v1\ApprovalTable\ApprovalTableUpdater;
use Tuleap\Docman\Version\VersionRetrieverFromApprovalTableVisitor;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\RESTLogger;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use Tuleap\User\REST\MinimalUserRepresentation;
use UserManager;
use WikiPageVersionFactory;
use WikiVersionDao;

final class DocmanItemsApprovalTableResource extends AuthenticatedResource
{
    public const int MAX_LIMIT = 50;

    private DocmanItemsRequestBuilder $request_builder;
    private LoggerInterface $logger;
    private Docman_VersionFactory $version_factory;
    private Docman_ApprovalTableFactoriesFactory $factories_factory;
    private ApprovalTableRetriever $approval_table_retriever;
    private UserManager $user_manager;
    private UserAvatarUrlProvider $user_avatar_url_provider;

    public function __construct()
    {
        $this->user_manager             = UserManager::instance();
        $this->request_builder          = new DocmanItemsRequestBuilder($this->user_manager, ProjectManager::instance());
        $this->logger                   = RESTLogger::getLogger();
        $this->version_factory          = new Docman_VersionFactory();
        $this->factories_factory        = new Docman_ApprovalTableFactoriesFactory();
        $this->approval_table_retriever = new ApprovalTableRetriever($this->factories_factory, $this->version_factory);
        $this->user_avatar_url_provider = new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash());
    }

    /**
     * @url OPTIONS {id}/approval_tables
     */
    public function optionsApprovalTables(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get all item approval tables
     *
     * Table reviewers are not retrieved
     *
     * @url    GET {id}/approval_tables
     * @access hybrid
     *
     * @param int $id ID of the item
     * @param int $limit Number of elements to fetch {@from query}{@min 1}{@max 50}
     * @param int $offset Position of the first element to fetch {@from query}{@min 0}
     *
     * @return list<ItemApprovalTableRepresentation>
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getAllApprovalTables(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        $items_request = $this->buildFromItemIdForApprovalTables($id);
        $item          = $items_request->getItem();
        $project       = $items_request->getProject();

        $approval_tables = $this->approval_table_retriever->retrieveAllApprovalTables($item, $limit, $offset);

        Header::sendPaginationHeaders($limit, $offset, $this->approval_table_retriever->getCountOfApprovalTable($item), self::MAX_LIMIT);
        return array_map(
            fn(Docman_ApprovalTable $table) => $this->buildApprovalTableRepresentation($table, $item, $project),
            $approval_tables,
        );
    }

    /**
     * @url OPTIONS {id}/approval_table/{version}
     */
    public function optionsApprovalTableVersion(int $id, int $version): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get specific item approval table
     *
     * @url    GET {id}/approval_table/{version}
     * @access hybrid
     *
     * @param int $id ID of the item
     * @param int $version Version number of the table
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getApprovalTableVersion(int $id, int $version): ItemApprovalTableRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        $items_request = $this->buildFromItemIdForApprovalTables($id);
        $item          = $items_request->getItem();
        $project       = $items_request->getProject();

        $table = $this->approval_table_retriever->retrieveSpecificTable($item, $version);
        if ($table === null) {
            throw new I18NRestException(404, dgettext('tuleap-docman', 'Table does not exist'));
        }

        return $this->buildApprovalTableRepresentation($table, $item, $project);
    }

    /**
     * @url OPTIONS {id}/approval_table
     */
    public function optionsPostPutPatchDeleteApprovalTable(int $id): void
    {
        Header::allowOptionsPostPutPatchDelete();
    }

    /**
     * Create an approval table for item if none exists
     *
     * @url    POST {id}/approval_table
     * @access hybrid
     *
     * @param int $id ID of the item {@from path}
     * @param ApprovalTablePostRepresentation $representation Reviewers to add to the approval table {@from body}
     *
     * @status 201
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function postApprovalTable(int $id, ApprovalTablePostRepresentation $representation): void
    {
        $this->checkAccess();
        Header::allowOptionsPostPutPatchDelete();

        $items_request = $this->buildFromItemIdForApprovalTables($id);
        $item          = $items_request->getItem();
        $project       = $items_request->getProject();
        $user          = $items_request->getUser();

        $docman_permissions_manager = Docman_PermissionsManager::instance($project->getGroupId());
        $user_can_write             = $docman_permissions_manager->userCanWrite($user, $item->getId());

        if (! $user_can_write) {
            throw new RestException(404);
        }

        $factory = $this->factories_factory->getFromItem($item);
        if ($factory === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'Cannot create approval table for document'));
        }

        $factory->newTableEmpty($user->getId());
        $table = $factory->getTable();
        if ($table === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'Failed to create approval table'));
        }

        $reviewer_factory = new Docman_ApprovalTableReviewerFactory($table, $item);
        $reviewer_factory->addUsers($representation->users);
        foreach ($representation->user_groups as $user_group) {
            $reviewer_factory->addUgroup($user_group);
        }
    }

    /**
     * Update the current approval table of the document
     *
     * @url    PUT {id}/approval_table
     * @access hybrid
     *
     * @param int $id ID of the item {@from path}
     * @param ApprovalTablePutRepresentation $representation New settings of the approval table {@from body}
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function putApprovalTable(int $id, ApprovalTablePutRepresentation $representation): void
    {
        $this->checkAccess();
        Header::allowOptionsPostPutPatchDelete();

        $items_request = $this->buildFromItemIdForApprovalTables($id);
        $item          = $items_request->getItem();
        $project       = $items_request->getProject();
        $user          = $items_request->getUser();

        $docman_permissions_manager = Docman_PermissionsManager::instance($project->getGroupId());
        $user_can_write             = $docman_permissions_manager->userCanWrite($user, $item->getId());

        if (! $user_can_write) {
            throw new RestException(404);
        }

        $factory = $this->factories_factory->getFromItem($item);
        if ($factory === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'There is no approval table to update'));
        }

        if ($factory instanceof Docman_ApprovalTableVersionnedFactory) {
            $table = $factory->getLastTableForItemWithReviewers();
        } else {
            $table = $factory->getTable(true);
        }
        if ($table === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'There is no approval table to update'));
        }

        new ApprovalTableUpdater(
            $this->user_manager,
            $factory,
            new Docman_ApprovalTableReviewerFactory($table, $item),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
        )->update($table, $representation);
    }

    /**
     * Perform asked action on approval table
     *
     * @url    PATCH {id}/approval_table
     * @access hybrid
     *
     * @param int $id ID of the item {@from path}
     * @param ApprovalTablePatchRepresentation $representation Action to perform {@from body}
     *
     * @status 200
     * @throws RestException 400
     */
    public function patchApprovalTable(int $id, ApprovalTablePatchRepresentation $representation): void
    {
        $this->checkAccess();
        Header::allowOptionsPostPutPatchDelete();

        $items_request = $this->buildFromItemIdForApprovalTables($id);
        $item          = $items_request->getItem();
        $project       = $items_request->getProject();
        $user          = $items_request->getUser();

        $docman_permissions_manager = Docman_PermissionsManager::instance($project->getGroupId());
        $user_can_write             = $docman_permissions_manager->userCanWrite($user, $item->getId());

        if (! $user_can_write) {
            throw new RestException(404);
        }

        $factory = $this->factories_factory->getFromItem($item);
        if ($factory === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'There is no approval table to perform an action on'));
        }

        if ($factory->getLastTableForItem() === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'There is no approval table to perform an action on'));
        }

        $action = ApprovalTableAction::fromString($representation->action);
        if (! $factory->createTable((int) $user->getId(), $action->value)) {
            throw new I18NRestException(500, sprintf(
                dgettext('tuleap-docman', 'Failed to perform action "%s" on approval table'),
                $action->value,
            ));
        }
    }

    /**
     * Delete the last approval table for item
     *
     * @url    DELETE {id}/approval_table
     * @access hybrid
     *
     * @param int $id ID of the item {@from path}
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function deleteApprovalTable(int $id): void
    {
        $this->checkAccess();
        Header::allowOptionsPostPutPatchDelete();

        $items_request = $this->buildFromItemIdForApprovalTables($id);
        $item          = $items_request->getItem();
        $project       = $items_request->getProject();
        $user          = $items_request->getUser();

        $docman_permissions_manager = Docman_PermissionsManager::instance($project->getGroupId());
        $user_can_delete            = $docman_permissions_manager->userCanDelete($user, $item);

        if (! $user_can_delete) {
            throw new RestException(404);
        }

        $factory = $this->factories_factory->getFromItem($item);
        if ($factory === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'There is no approval table to delete'));
        }

        $table = $factory->getLastTableForItem();
        if ($table === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'There is no approval table to delete'));
        }

        if (! $factory->deleteTable()) {
            throw new I18NRestException(500, dgettext('tuleap-docman', 'Failed to delete approval table'));
        }
    }

    /**
     * @url OPTIONS {id}/approval_table/review
     */
    public function optionsPutApprovalTableReview(int $id): void
    {
        Header::allowOptionsPut();
    }

    /**
     * Change user review on approval table
     *
     * @url    PUT {id}/approval_table/review
     * @access hybrid
     *
     * @param int $id ID of the item {@from path}
     * @param ApprovalTableReviewPutRepresentation $representation Review of the user {@from body}
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function putApprovalTableReview(int $id, ApprovalTableReviewPutRepresentation $representation): void
    {
        $this->checkAccess();
        Header::allowOptionsPut();

        $items_request = $this->buildFromItemIdForApprovalTables($id);
        $item          = $items_request->getItem();
        $project       = $items_request->getProject();
        $user          = $items_request->getUser();

        $permissions_manager = Docman_PermissionsManager::instance((int) $project->getID());

        if (! $permissions_manager->userCanRead($user, $item->getId())) {
            throw new RestException(404);
        }

        $table_factory = $this->factories_factory->getFromItem($item);
        if ($table_factory === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'Cannot review an item without approval table'));
        }

        $table = $table_factory->getTable();
        if (! ($table instanceof Docman_ApprovalTable)) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'This document does not have an approval table. Please create one before'));
        }

        $reviewer_factory      = new Docman_ApprovalTableReviewerFactory($table, $item);
        $notifications_manager = new NotificationBuilders(new ResponseFeedbackWrapper(), $project)->buildNotificationManager();
        $reviewer_factory->setNotificationManager($notifications_manager);

        new ApprovalTableReviewUpdater(
            $reviewer_factory,
            $notifications_manager,
            EventManager::instance(),
            SystemClock::fromSystemTimezone(),
        )->update($item, $user, $table, $representation);
    }

    /**
     * @url OPTIONS {id}/approval_table/reminder
     */
    public function optionsPostApprovalTableReminder(int $id): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Send a reminder to all approvers according to table notification type
     *
     * @url    POST {id}/approval_table/reminder
     * @access hybrid
     *
     * @param int $id ID of the item {@from path}
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function postApprovalTableReminder(int $id): void
    {
        $this->checkAccess();
        Header::allowOptionsPost();

        $items_request = $this->buildFromItemIdForApprovalTables($id);
        $item          = $items_request->getItem();
        $project       = $items_request->getProject();
        $user          = $items_request->getUser();

        $docman_permissions_manager = Docman_PermissionsManager::instance($project->getGroupId());
        $user_can_write             = $docman_permissions_manager->userCanWrite($user, $item->getId());

        if (! $user_can_write) {
            throw new RestException(404);
        }

        $factory = $this->factories_factory->getFromItem($item);
        if ($factory === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'The document has no approval table'));
        }

        $table = $factory->getLastTableForItemWithReviewers();
        if ($table === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'The document has no approval table'));
        }

        $reviewer_factory = new Docman_ApprovalTableReviewerFactory($table, $item);
        $reviewer_factory->setNotificationManager(new NotificationBuilders(new ResponseFeedbackWrapper(), $project)->buildNotificationManager());
        if (! $reviewer_factory->notifyReviewers()) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'No notification sent'));
        }
    }

    /**
     * @url OPTIONS {id}/approval_table/reminder/{reviewer_id}
     */
    public function optionsPostApprovalTableReviewerReminder(int $id, int $reviewer_id): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Force send a reminder to a specific approver
     *
     * @url    POST {id}/approval_table/reminder/{reviewer_id}
     * @access hybrid
     *
     * @param int $id ID of the item {@from path}
     * @param int $reviewer_id User ID of the reviewer {@from path}
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function postApprovalTableReviewerReminder(int $id, int $reviewer_id): void
    {
        $this->checkAccess();
        Header::allowOptionsPost();

        $items_request = $this->buildFromItemIdForApprovalTables($id);
        $item          = $items_request->getItem();
        $project       = $items_request->getProject();
        $user          = $items_request->getUser();

        $docman_permissions_manager = Docman_PermissionsManager::instance($project->getGroupId());
        $user_can_write             = $docman_permissions_manager->userCanWrite($user, $item->getId());

        if (! $user_can_write) {
            throw new RestException(404);
        }

        $factory = $this->factories_factory->getFromItem($item);
        if ($factory === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'The document has no approval table'));
        }

        $table = $factory->getLastTableForItemWithReviewers();
        if ($table === null) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'The document has no approval table'));
        }

        $reviewer_factory = new Docman_ApprovalTableReviewerFactory($table, $item);
        $reviewer_factory->setNotificationManager(new NotificationBuilders(new ResponseFeedbackWrapper(), $project)->buildNotificationManager());
        if (! $reviewer_factory->isReviewer($reviewer_id)) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'No notification sent'));
        }
        if (! $reviewer_factory->getApprovalTableNotificationCycle()->notifyIndividual($reviewer_id)) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'No notification sent'));
        }
    }

    /**
     * @throws RestException
     */
    private function buildApprovalTableRepresentation(Docman_ApprovalTable $table, Docman_Item $item, Project $project): ItemApprovalTableRepresentation
    {
        $owner = $this->user_manager->getUserById((int) $table->getOwner());
        if ($owner === null) {
            $this->logger->error('An approval table has a non-existing user as owner', [
                'table' => $table->getId(),
                'user'  => (int) $table->getOwner(),
            ]);
            throw new RestException(404);
        }

        return ItemApprovalTableRepresentation::build(
            $item,
            $table,
            MinimalUserRepresentation::build(
                $owner,
                $this->user_avatar_url_provider,
            ),
            new ApprovalTableStateMapper(),
            $this->user_manager,
            $this->user_avatar_url_provider,
            $this->version_factory,
            new NotificationBuilders(new ResponseFeedbackWrapper(), $project)->buildNotificationManager(),
            \Codendi_HTMLPurifier::instance(),
            new VersionOpenHrefVisitor(),
            new VersionRetrieverFromApprovalTableVisitor(new \Docman_VersionFactory(), new \Docman_LinkVersionFactory(), new WikiVersionDao(), new WikiPageVersionFactory()),
            ProjectManager::instance()
        );
    }

    /**
     * @throws RestException
     */
    public function buildFromItemIdForApprovalTables(int $id): DocmanItemsRequest
    {
        $item_request = $this->request_builder->buildFromItemId($id);
        if ($item_request->getItem()->getParentId() === 0) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'Root item can not have approval tables'));
        }

        if ($item_request->getItem() instanceof \Docman_Empty) {
            throw new I18NRestException(400, dgettext('tuleap-docman', 'Item type empty can not have approval tables'));
        }

        return $item_request;
    }
}
