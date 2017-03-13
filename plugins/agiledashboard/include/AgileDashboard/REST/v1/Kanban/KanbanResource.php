<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1\Kanban;

use DateTime;
use AgileDashboard_KanbanItemManager;
use Luracast\Restler\RestException;
use PlanningFactory;
use Tuleap\AgileDashboard\REST\v1\Kanban\CumulativeFlowDiagram\TooMuchPointsException;
use Tuleap\AgileDashboard\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\ScrumForMonoMilestoneDao;
use Tuleap\REST\Header;
use Tuleap\REST\AuthenticatedResource;
use AgileDashboard_PermissionsManager;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanDao;
use AgileDashboard_KanbanItemDao;
use AgileDashboard_KanbanNotFoundException;
use AgileDashboard_KanbanCannotAccessException;
use AgileDashboard_Kanban;
use AgileDashboard_KanbanColumnFactory;
use AgileDashboard_KanbanColumnDao;
use AgileDashboardStatisticsAggregator;
use UserManager;
use Exception;
use TrackerFactory;
use PFUser;
use Tracker_Artifact_PriorityHistoryChange;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_ArtifactFactory;
use Tracker_Artifact_PriorityManager;
use Tracker_NoChangeException;
use Tracker_FormElement_Field_List_Bind;
use Tracker_Semantic_Status;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\REST\v1\OrderRepresentation;
use Tuleap\AgileDashboard\REST\v1\OrderValidator;
use Tuleap\AgileDashboard\REST\v1\ArtifactLinkUpdater;
use Tuleap\AgileDashboard\REST\v1\ResourcesPatcher;
use AgileDashboard_KanbanUserPreferences;
use Kanban_SemanticStatusNotDefinedException;
use Kanban_SemanticStatusNotBoundToStaticValuesException;
use Kanban_SemanticStatusBasedOnASharedFieldException;
use Kanban_SemanticStatusAllColumnIdsNotProvidedException;
use Kanban_SemanticStatusColumnIdsNotInOpenSemanticException;
use AgileDashboard_KanbanColumnManager;
use AgileDashboard_KanbanActionsChecker;
use Tracker_FormElement_Field_List_Bind_Static_ValueDao;
use Tuleap\RealTime\NodeJSClient;
use Tracker_Workflow_GlobalRulesViolationException;
use Tracker_Permission_PermissionsSerializer;
use Tracker_Permission_PermissionRetrieveAssignee;
use Tuleap\RealTime\MessageDataPresenter;
use Tuleap\AgileDashboard\KanbanArtifactRightsPresenter;
use Tuleap\AgileDashboard\KanbanRightsPresenter;
use Tuleap\AgileDashboard\REST\v1\Kanban\CumulativeFlowDiagram\DiagramRepresentationBuilder;
use Tuleap\AgileDashboard\KanbanCumulativeFlowDiagramDao;

class KanbanResource extends AuthenticatedResource {

    const MAX_LIMIT = 100;
    const HTTP_CLIENT_UUID = 'HTTP_X_CLIENT_UUID';

    /** @var AgileDashboard_KanbanFactory */
    private $kanban_factory;

    /** @var ResourcesPatcher */
    private $resources_patcher;

    /** @var AgileDashboard_KanbanItemDao */
    private $kanban_item_dao;

    /** @var AgileDashboard_KanbanDao */
    private $kanban_dao;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var AgileDashboard_KankanColumnFactory */
    private $kanban_column_factory;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var AgileDashboard_PermissionsManager */
    private $permissions_manager;

    /** @var AgileDashboard_KanbanUserPreferences */
    private $user_preferences;

    /** @var AgileDashboardStatisticsAggregator */
    private $statistics_aggregator;
    /** @var AgileDashboard_KanbanColumnManager */
    private $kanban_column_manager;

    /** @var AgileDashboard_KanbanItemManager */
    private $kanban_item_manager;

    /** @var AgileDashboard_KanbanActionsChecker */
    private $kanban_actions_checker;

    /** @var NodeJSClient */
    private $node_js_client;

    /** @var Tracker_Permission_PermissionsSerializer */
    private $permissions_serializer;

    public function __construct() {
        $this->kanban_item_dao = new AgileDashboard_KanbanItemDao();
        $this->kanban_item_manager = new AgileDashboard_KanbanItemManager($this->kanban_item_dao);
        $this->tracker_factory = TrackerFactory::instance();

        $this->kanban_dao     = new AgileDashboard_KanbanDao();
        $this->kanban_factory = new AgileDashboard_KanbanFactory(
            $this->tracker_factory,
            $this->kanban_dao
        );

        $this->user_preferences = new AgileDashboard_KanbanUserPreferences();
        $this->kanban_column_factory = new AgileDashboard_KanbanColumnFactory(
            new AgileDashboard_KanbanColumnDao(),
            $this->user_preferences
        );

        $this->artifact_factory = Tracker_ArtifactFactory::instance();
        $priority_manager       = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            UserManager::instance(),
            $this->artifact_factory
        );

        $artifactlink_updater    = new ArtifactLinkUpdater(
            $priority_manager,
            new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), PlanningFactory::build())
        );
        $this->resources_patcher = new ResourcesPatcher(
            $artifactlink_updater,
            $this->artifact_factory,
            $priority_manager
        );

        $this->form_element_factory = Tracker_FormElementFactory::instance();
        $this->permissions_manager  = new AgileDashboard_PermissionsManager();

        $this->kanban_actions_checker = new AgileDashboard_KanbanActionsChecker(
            $this->tracker_factory,
            $this->permissions_manager,
            $this->form_element_factory
        );

        $this->kanban_representation_builder = new KanbanRepresentationBuilder(
            $this->user_preferences,
            $this->kanban_column_factory,
            $this->kanban_actions_checker
        );

        $this->kanban_column_manager = new AgileDashboard_KanbanColumnManager(
            new AgileDashboard_KanbanColumnDao(),
            new Tracker_FormElement_Field_List_Bind_Static_ValueDao(),
            $this->kanban_actions_checker
        );

        $this->statistics_aggregator = new AgileDashboardStatisticsAggregator();

        $this->node_js_client         = new NodeJSClient();
        $this->permissions_serializer = new Tracker_Permission_PermissionsSerializer(
            new Tracker_Permission_PermissionRetrieveAssignee(UserManager::instance())
        );
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptions();
    }

    /**
     * Get kanban
     *
     * Get the definition of a given kanban
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id Id of the kanban
     *
     * @return Tuleap\AgileDashboard\REST\v1\Kanban\KanbanRepresentation
     *
     * @throws 403
     * @throws 404
     */
    public function getId($id) {
        $this->checkAccess();
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, $id);

        $kanban_representation = $this->kanban_representation_builder->build($kanban, $user);

        Header::allowOptionsGetPatchDelete();
        return $kanban_representation;
    }

    /**
     * Patch kanban
     *
     * Patch properties of a given kanban
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * <br>
     * To update the label of a kanban:
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"label": "The new label"<br>
     * }
     * </pre>
     *
     * <br>
     * To collapse a column (will be saved in current user preferences):
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"collapse_column": {<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;"column_id": 1337,<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;"value": true      // false to expand<br>
     * &nbsp;&nbsp;}<br>
     * }
     * </pre>
     *
     * <br>
     * To collapse the backlog (the same for archive):
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"collapse_backlog": true  // false to expand<br>
     * }
     * </pre>
     *
     * @url PATCH {id}
     * @access protected
     *
     * @param int    $id    Id of the kanban
     * @param string $label The new label {@from body}
     * @param \Tuleap\AgileDashboard\REST\v1\Kanban\KanbanCollapseColumnRepresentation  $collapse_column The column to collapse (save in user prefs) {@from body}
     * @param bool $collapse_archive True to collapse the archive (save in user prefs) {@from body}
     * @param bool $collapse_backlog True to collapse the backlog (save in user prefs) {@from body}
     *
     * @throws 403
     * @throws 404
     */
    public function patchId(
        $id,
        $label = null,
        KanbanCollapseColumnRepresentation $collapse_column = null,
        $collapse_archive = null,
        $collapse_backlog = null
    ) {
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, $id);

        if ($label) {
            $this->checkUserCanUpdateKanban($user, $kanban);
            $this->kanban_dao->save($id, $label);
            $this->statistics_aggregator->addKanbanRenamingHit(
                $this->getProjectIdForKanban($kanban)
            );

            if(isset($_SERVER[self::HTTP_CLIENT_UUID]) && $_SERVER[self::HTTP_CLIENT_UUID]) {
                $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
                $rights = new KanbanRightsPresenter($tracker, $this->permissions_serializer);
                $message = new MessageDataPresenter(
                    $user->getId(),
                    $_SERVER[self::HTTP_CLIENT_UUID],
                    $kanban->getId(),
                    $rights,
                    'kanban:edit',
                    $label
                );

                $this->node_js_client->sendMessage($message);
            }
        }

        if ($collapse_column) {
            if (! $this->columnIsInTracker($kanban, $user, $collapse_column->column_id)) {
                throw new RestException(404, 'Cannot collapse unknown column');
            }
            if ($collapse_column->value == true) {
                $this->user_preferences->closeColumn($kanban, $collapse_column->column_id, $user);
            } else {
                $this->user_preferences->openColumn($kanban, $collapse_column->column_id, $user);
            }
            $this->statistics_aggregator->addExpandCollapseColumnHit(
                $this->getProjectIdForKanban($kanban)
            );
        }

        if ($collapse_archive !== null) {
            if ($collapse_archive) {
                $this->user_preferences->closeArchive($kanban, $user);
            } else {
                $this->user_preferences->openArchive($kanban, $user);
            }
            $this->statistics_aggregator->addExpandCollapseColumnHit(
                $this->getProjectIdForKanban($kanban)
            );
        }

        if ($collapse_backlog !== null) {
            if ($collapse_backlog) {
                $this->user_preferences->closeBacklog($kanban, $user);
            } else {
                $this->user_preferences->openBacklog($kanban, $user);
            }
            $this->statistics_aggregator->addExpandCollapseColumnHit(
                $this->getProjectIdForKanban($kanban)
            );
        }

        Header::allowOptionsGetPatchDelete();
    }

    /**
     * Return info about milestone if exists
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the Kanban
     */
    public function optionsId($id) {
        Header::allowOptionsGetPatchDelete();
    }

    /**
     * Get backlog
     *
     * Get the backlog of a given kanban
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/backlog
     * @access hybrid
     *
     * @param int $id Id of the kanban
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return Tuleap\AgileDashboard\REST\v1\Kanban\KanbanBacklogRepresentation
     *
     * @throws 403
     * @throws 404
     */
    public function getBacklog($id, $limit = 10, $offset = 0) {
        $this->checkAccess();
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, $id);

        $backlog_representation = new KanbanBacklogRepresentation();
        $backlog_representation->build($user, $kanban, $limit, $offset);

        Header::allowOptionsGet();
        Header::sendPaginationHeaders($limit, $offset, $backlog_representation->total_size, self::MAX_LIMIT);

        return $backlog_representation;
    }

    private function checkUserCanUpdateKanban(PFUser $user, AgileDashboard_Kanban $kanban) {
        if (! $this->isUserAdmin($user, $kanban)) {
            throw new RestException(403);
        }
    }

    private function isUserAdmin(PFUser $user, AgileDashboard_Kanban $kanban) {
        $tracker = $this->getTrackerForKanban($kanban);

        return $this->permissions_manager->userCanAdministrate(
            $user,
            $tracker->getGroupId()
        );
    }

    /**
     * Partial re-order of Kanban backlog items
     *
     * Partial re-order of Kanban backlog items
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url PATCH {id}/backlog
     *
     * @param int                                                            $id    Id of the Kanban
     * @param \Tuleap\AgileDashboard\REST\v1\OrderRepresentation             $order Order of the children {@from body}
     * @param \Tuleap\AgileDashboard\REST\v1\Kanban\KanbanAddRepresentation  $add   Ids to add to Kanban backlog {@from body}
     * @param string                                                         $from_column   Id of the column the item is coming from (when moving an item) {@from body}
     *
     * @throws 400
     * @throws 403
     */
    protected function patchBacklog(
        $id,
        OrderRepresentation $order = null,
        KanbanAddRepresentation $add = null,
        $from_column = null
    ) {
        $current_user = UserManager::instance()->getCurrentUser();
        $kanban       = $this->kanban_factory->getKanban($current_user, $id);

        if ($add) {
            $add->checkFormat();
            $this->validateIdsInAddAreInKanbanTracker($kanban, $add);
            $this->resources_patcher->startTransaction();

            try {
                $this->moveArtifactsInBacklog($kanban, $current_user, $add);
                $this->resources_patcher->commit();
            } catch (Tracker_NoChangeException $exception) {
                $this->resources_patcher->rollback();
            } catch (Exception $exception) {
                $this->resources_patcher->rollback();
                throw new RestException(500, $exception->getMessage());
            }
        }

        if ($order) {
            $order->checkFormat();
            $kanban_backlog_items = $this->getKanbanBacklogItemIds($kanban->getTrackerId());
            $order_validator      = new OrderValidator($kanban_backlog_items);

            try {
                $order_validator->validate($order);
            } catch (IdsFromBodyAreNotUniqueException $exception) {
                throw new RestException(409, $exception->getMessage());
            } catch (OrderIdOutOfBoundException $exception) {
                throw new RestException(409, $exception->getMessage());
            } catch (Exception $exception) {
                throw new RestException(500, $exception->getMessage());
            }

            $this->resources_patcher->updateArtifactPriorities(
                $order,
                Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT,
                $this->getProjectIdForKanban($kanban)
            );
        }

        if ($add || $order) {
            $this->statistics_aggregator->addCardDragAndDropHit(
                $this->getProjectIdForKanban($kanban)
            );
            $in_column = 'backlog';

            if (! is_null($from_column)) {
                if ($from_column !== 'backlog' && $from_column !== 'archive') {
                    $from_column = intval($from_column);

                    if ($from_column === 0 || ! $this->columnIsInTracker($kanban, $current_user, $from_column)) {
                        throw new RestException(400, 'Invalid from_column');
                    }
                }
            } else {
                $from_column = $in_column;
            }

            $this->sendMessageForDroppingItem($current_user, $kanban, $order, $add, $in_column, $from_column);
        }
    }

    private function getStatusField(AgileDashboard_Kanban $kanban, PFUser $user) {
        $tracker      = $this->getTrackerForKanban($kanban);
        $status_field = $tracker->getStatusField();

        if (! $status_field) {
            throw new RestException(403);
        }

        if (! $status_field->userCanRead($user)) {
            throw new RestException(403);
        }

        return $status_field;
    }

    private function moveArtifactsInBacklog(AgileDashboard_Kanban $kanban, PFUser $user, KanbanAddRepresentation $add) {
        $this->moveArtifactsInColumn($kanban, $user, $add, Tracker_FormElement_Field_List_Bind::NONE_VALUE);
    }

    private function moveArtifactsInArchive(AgileDashboard_Kanban $kanban, PFUser $user, KanbanAddRepresentation $add) {
        $a_closed_value_id = null;
        $status_field      = $this->getStatusField($kanban, $user);
        $open_values       = $this->getSemanticStatus($kanban)->getOpenValues();
        foreach ($status_field->getAllValues() as $value_id => $value) {
            if (! $value->isHidden() && ! in_array($value_id, $open_values)) {
                $a_closed_value_id = $value_id;
                break;
            }
        }

        if (! $a_closed_value_id) {
            throw new RestException(500, 'Cannot found any suitable value corresponding to the [archive] column.');
        }
        $this->moveArtifactsInColumn($kanban, $user, $add, $a_closed_value_id);
    }

    private function moveArtifactsInColumn(
        AgileDashboard_Kanban $kanban,
        PFUser $user,
        KanbanAddRepresentation $add,
        $column_id
    ) {
        foreach ($add->ids as $artifact_id) {
            $artifact        = $this->artifact_factory->getArtifactById($artifact_id);
            $status_field    = $this->getStatusField($kanban, $user);

            if (! $artifact->userCanView($user)) {
                throw new RestException(403, 'You cannot access this kanban item.');
            }

            $fields_data = array(
                $status_field->getId() => $column_id
            );

            $artifact->createNewChangeset($fields_data, '', $user);
        }
    }

    private function validateIdsInAddAreInKanbanTracker(AgileDashboard_Kanban $kanban, KanbanAddRepresentation $add) {
        $all_kanban_item_ids = array();
        foreach ($this->kanban_item_dao->getAllKanbanItemIds($kanban->getTrackerId()) as $row) {
            $all_kanban_item_ids[] = $row['id'];
        }
        return count(array_diff($add->ids, $all_kanban_item_ids)) === 0;
    }

    private function getKanbanBacklogItemIds($tracker_id) {
        $backlog_item_ids = array();
        foreach ($this->kanban_item_dao->getKanbanBacklogItemIds($tracker_id) as $artifact) {
            $backlog_item_ids[$artifact['id']] = true;
        }

        return $backlog_item_ids;
    }
    /**
     * @url OPTIONS {id}/backlog
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @param string $id Id of the Kanban
     */
    public function optionsBacklog($id) {
        Header::allowOptionsGetPatch();
    }

    /**
     * Get archive
     *
     * Get the archived items of a given kanban
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/archive
     * @access hybrid
     *
     * @param int $id Id of the kanban
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return Tuleap\AgileDashboard\REST\v1\Kanban\KanbanArchiveRepresentation
     *
     * @throws 403
     * @throws 404
     */
    public function getArchive($id, $limit = 10, $offset = 0) {
        $this->checkAccess();
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, $id);

        $items_representation = new KanbanArchiveRepresentation();
        $items_representation->build($user, $kanban, $limit, $offset);

        Header::allowOptionsGet();
        Header::sendPaginationHeaders($limit, $offset, $items_representation->total_size, self::MAX_LIMIT);

        return $items_representation;
    }

    /**
     * Partial re-order of Kanban archive items
     *
     * Partial re-order of Kanban archive items
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url PATCH {id}/archive
     *
     * @param int                                                            $id    Id of the Kanban
     * @param \Tuleap\AgileDashboard\REST\v1\OrderRepresentation             $order Order of the children {@from body}
     * @param \Tuleap\AgileDashboard\REST\v1\Kanban\KanbanAddRepresentation  $add   Ids to add to Kanban backlog {@from body}
     * @param string                                                         $from_column   Id of the column the item is coming from (when moving an item) {@from body}
     *
     * @throws 400
     * @throws 403
     */
    protected function patchArchive(
        $id,
        OrderRepresentation $order = null,
        KanbanAddRepresentation $add = null,
        $from_column = null
    ) {
        $current_user = UserManager::instance()->getCurrentUser();
        $kanban       = $this->kanban_factory->getKanban($current_user, $id);

        if ($add) {
            $add->checkFormat();
            $this->validateIdsInAddAreInKanbanTracker($kanban, $add);
            $this->resources_patcher->startTransaction();

            try {
                $this->moveArtifactsInArchive($kanban, $current_user, $add);
                $this->resources_patcher->commit();
            } catch (Tracker_NoChangeException $exception) {
                $this->resources_patcher->rollback();
            } catch (Exception $exception) {
                $this->resources_patcher->rollback();
                throw new RestException(500, $exception->getMessage());
            }
        }


        if ($order) {
            $order->checkFormat();
            $kanban_archive_items = $this->getKanbanArchiveItemIds($kanban->getTrackerId());
            $order_validator      = new OrderValidator($kanban_archive_items);

            try {
                $order_validator->validate($order);
            } catch (IdsFromBodyAreNotUniqueException $exception) {
                throw new RestException(409, $exception->getMessage());
            } catch (OrderIdOutOfBoundException $exception) {
                throw new RestException(409, $exception->getMessage());
            } catch (Exception $exception) {
                throw new RestException(500, $exception->getMessage());
            }

            $this->resources_patcher->updateArtifactPriorities(
                $order,
                Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT,
                $this->getProjectIdForKanban($kanban)
            );
        }

        if ($add || $order) {
            $this->statistics_aggregator->addCardDragAndDropHit(
                $this->getProjectIdForKanban($kanban)
            );

            $in_column = 'archive';

            if (! is_null($from_column)) {
                if ($from_column !== 'backlog' && $from_column !== 'archive') {
                    $from_column = intval($from_column);

                    if ($from_column === 0 || ! $this->columnIsInTracker($kanban, $current_user, $from_column)) {
                        throw new RestException(400, 'Invalid from_column');
                    }
                }
            } else {
                $from_column = $in_column;
            }

            $this->sendMessageForDroppingItem($current_user, $kanban, $order, $add, $in_column, $from_column);
        }
    }

    private function getKanbanArchiveItemIds($tracker_id) {
        $archive_item_ids = array();
        foreach ($this->kanban_item_dao->getKanbanArchiveItemIds($tracker_id) as $artifact) {
            $archive_item_ids[$artifact['id']] = true;
        }

        return $archive_item_ids;
    }

    /**
     * @url OPTIONS {id}/archive
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @param string $id Id of the Kanban
     */
    public function optionsArchive($id) {
        Header::allowOptionsGetPatch();
    }

    /**
     * Get items
     *
     * Get the items of a given kanban in a given column
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/items
     * @access hybrid
     *
     * @param int $id Id of the kanban
     * @param int $column_id Id of the column the item belongs to
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return Tuleap\AgileDashboard\REST\v1\Kanban\KanbanItemCollectionRepresentation
     *
     * @throws 403
     * @throws 404
     */
    public function getItems($id, $column_id, $limit = 10, $offset = 0) {
        $this->checkAccess();
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, $id);

        if (! $this->columnIsInTracker($kanban, $user, $column_id)) {
            throw new RestException(404);
        }

        $items_representation = new KanbanItemCollectionRepresentation();
        $items_representation->build($user, $kanban, $column_id, $limit, $offset);

        Header::allowOptionsGet();
        Header::sendPaginationHeaders($limit, $offset, $items_representation->total_size, self::MAX_LIMIT);

        return $items_representation;
    }

    private function columnIsInTracker(AgileDashboard_Kanban $kanban, PFUser $user, $column_id) {
        $status_field = $this->getStatusField($kanban, $user);

        return array_key_exists($column_id, $status_field->getAllValues());
    }

    /**
     * Partial re-order of Kanban items
     *
     * Partial re-order of Kanban items
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url PATCH {id}/items
     *
     * @param int                                                            $id    Id of the Kanban
     * @param int                                                            $column_id Id of the column the item belongs to {@from query}
     * @param \Tuleap\AgileDashboard\REST\v1\OrderRepresentation             $order Order of the items {@from body}
     * @param \Tuleap\AgileDashboard\REST\v1\Kanban\KanbanAddRepresentation  $add   Ids to add to the column {@from body}
     * @param string                                                         $from_column   Id of the column the item is coming from (when moving an item) {@from body}
     *
     * @throws 400
     * @throws 403
     */
    protected function patchItems(
        $id,
        $column_id,
        OrderRepresentation $order = null,
        KanbanAddRepresentation $add = null,
        $from_column = null
    ) {
        $current_user = UserManager::instance()->getCurrentUser();
        $kanban       = $this->kanban_factory->getKanban($current_user, $id);

        if (! $this->columnIsInTracker($kanban, $current_user, $column_id)) {
            throw new RestException(404);
        }

        if ($add) {
            $add->checkFormat();
            $this->validateIdsInAddAreInKanbanTracker($kanban, $add);
            $this->resources_patcher->startTransaction();

            try {
                $this->moveArtifactsInColumn($kanban, $current_user, $add, $column_id);
                $this->resources_patcher->commit();
            } catch (Tracker_NoChangeException $exception) {
                $this->resources_patcher->rollback();
            } catch (Tracker_Workflow_GlobalRulesViolationException $exception) {
                $this->resources_patcher->rollback();
                throw new RestException(400, $exception->getMessage());
            } catch (Exception $exception) {
                $this->resources_patcher->rollback();
                throw new RestException(500, $exception->getMessage());
            }
        }


        if ($order) {
            $order->checkFormat();
            $kanban_column_items = $this->getItemsInColumn($kanban->getTrackerId(), $column_id);
            $order_validator     = new OrderValidator($kanban_column_items);

            try {
                $order_validator->validate($order);
            } catch (IdsFromBodyAreNotUniqueException $exception) {
                throw new RestException(409, $exception->getMessage());
            } catch (OrderIdOutOfBoundException $exception) {
                throw new RestException(409, $exception->getMessage());
            } catch (Exception $exception) {
                throw new RestException(500, $exception->getMessage());
            }

            $this->resources_patcher->updateArtifactPriorities(
                $order,
                Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT,
                $this->getProjectIdForKanban($kanban)
            );
        }

        if ($add || $order) {
            $this->statistics_aggregator->addCardDragAndDropHit(
                $this->getProjectIdForKanban($kanban)
            );

            if (! is_null($from_column)) {
                if ($from_column !== 'backlog' && $from_column !== 'archive') {
                    $from_column = intval($from_column);

                    if ($from_column === 0 || ! $this->columnIsInTracker($kanban, $current_user, $from_column)) {
                        throw new RestException(400, 'Invalid from_column');
                    }
                }
            } else {
                $from_column = $column_id;
            }

            $this->sendMessageForDroppingItem($current_user, $kanban, $order, $add, $column_id, $from_column);
        }
    }

    private function getItemsInColumn($tracker_id, $column_id) {
        $column_item_ids = array();
        foreach ($this->kanban_item_dao->getItemsInColumn($tracker_id, $column_id) as $artifact) {
            $column_item_ids[$artifact['id']] = true;
        }

        return $column_item_ids;
    }

    /**
     * @url OPTIONS {id}/items
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @param string $id Id of the Kanban
     */
    public function optionsItems($id) {
        Header::allowOptionsGetPatch();
    }

    /**
     * Delete Kanban
     *
     * Delete Kanban
     *
     * @url DELETE {id}
     * @access protected
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @param string $id Id of the kanban
     */
    protected function delete($id) {
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, $id);

        $this->checkUserCanUpdateKanban($user, $kanban);
        $this->kanban_dao->delete($id);

        Header::allowOptionsGetPatchDelete();

        if(isset($_SERVER[self::HTTP_CLIENT_UUID]) && $_SERVER[self::HTTP_CLIENT_UUID]) {
            $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
            $rights  = new KanbanRightsPresenter($tracker, $this->permissions_serializer);
            $message = new MessageDataPresenter(
                $user->getId(),
                $_SERVER[self::HTTP_CLIENT_UUID],
                $kanban->getId(),
                $rights,
                'kanban:delete',
                null
            );

            $this->node_js_client->sendMessage($message);
        }
    }

    /**
     * @url OPTIONS {id}/columns
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @param string $id Id of the Kanban
     */
    public function optionsColumns($id) {
        Header::allowOptionsPostPut();
    }

    /**
     * Add a new column
     *
     * Create a new kanban column. Will add another open value to the field corresponding to
     * the 'Status' semantic. An error will be thrown if the semantic 'Status' is not bound to
     * static values.
     *
     * @url POST {id}/columns
     * @status 201
     *
     * @param string                         $id     Id of the Kanban
     * @param KanbanColumnPOSTRepresentation $column The created kanban column {@from body} {@type Tuleap\AgileDashboard\REST\v1\Kanban\KanbanColumnPOSTRepresentation}
     *
     * @throws 400
     * @throws 401
     * @throws 403
     * @throws 404
     *
     * @return Tuleap\AgileDashboard\REST\v1\Kanban\KanbanColumnRepresentation
     */
    protected function postColumns($id, KanbanColumnPOSTRepresentation $column) {
        $current_user = $this->getCurrentUser();
        $kanban_id    = $id;
        $kanban       = $this->getKanban($current_user, $kanban_id);
        $column_label = $column->label;

        try {
            $new_column_id = $this->kanban_column_manager->createColumn($current_user, $kanban, $column_label);
        } catch (AgileDashboard_UserNotAdminException $exception) {
            throw new RestException(401, $exception->getMessage());
        } catch(Kanban_SemanticStatusNotDefinedException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (Kanban_SemanticStatusNotBoundToStaticValuesException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Kanban_SemanticStatusBasedOnASharedFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $this->form_element_factory->clearCaches();

        $new_column = $this->kanban_column_factory->getColumnForAKanban($kanban, $new_column_id, $current_user);

        try {
            $this->kanban_actions_checker->checkUserCanAddInPlace($current_user, $kanban);
            $add_in_place = true;
        } catch (Exception $exception) {
            $add_in_place = false;
        }

        try {
            $this->kanban_actions_checker->checkUserCanDeleteColumn($current_user, $kanban, $new_column);
            $user_can_remove_column = true;
        } catch (Exception $exception) {
            $user_can_remove_column = false;
        }

        try {
            $this->kanban_actions_checker->checkUserCanEditColumnLabel($current_user, $kanban);
            $user_can_edit_label = true;
        } catch (Exception $exception) {
            $user_can_edit_label = false;
        }

        $column_representation = new KanbanColumnRepresentation();
        $column_representation->build($new_column, $add_in_place, $user_can_remove_column, $user_can_edit_label);

        if(isset($_SERVER[self::HTTP_CLIENT_UUID]) && $_SERVER[self::HTTP_CLIENT_UUID]) {
            $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
            $rights  = new KanbanRightsPresenter($tracker, $this->permissions_serializer);
            $message = new MessageDataPresenter(
                $current_user->getId(),
                $_SERVER[self::HTTP_CLIENT_UUID],
                $kanban->getId(),
                $rights,
                'kanban_column:create',
                $column_representation
            );

            $this->node_js_client->sendMessage($message);
        }

        return $column_representation;
    }

    /**
     * Reorder Kanban columns
     *
     * @url PUT {id}/columns
     *
     * @param string $id         Id of the Kanban
     * @param array  $column_ids The created kanban column {@from body} {@type int}
     *
     * @throws 400
     * @throws 401
     * @throws 404
     */
    protected function putColumns($id, array $column_ids) {
        $user      = $this->getCurrentUser();
        $kanban_id = $id;
        $kanban    = $this->getKanban($user, $kanban_id);

        $this->checkColumnIdsExist($user, $kanban, $column_ids);

        try {
            $this->kanban_column_manager->reorderColumns($user, $kanban, $column_ids);
        } catch (AgileDashboard_UserNotAdminException $exception) {
            throw new RestException(401, $exception->getMessage());
        } catch(Kanban_SemanticStatusNotDefinedException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (Kanban_SemanticStatusNotBoundToStaticValuesException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Kanban_SemanticStatusBasedOnASharedFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Kanban_SemanticStatusAllColumnIdsNotProvidedException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Kanban_SemanticStatusColumnIdsNotInOpenSemanticException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        if(isset($_SERVER[self::HTTP_CLIENT_UUID]) && $_SERVER[self::HTTP_CLIENT_UUID]) {
            $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
            $rights  = new KanbanRightsPresenter($tracker, $this->permissions_serializer);
            $message = new MessageDataPresenter(
                $user->getId(),
                $_SERVER[self::HTTP_CLIENT_UUID],
                $kanban->getId(),
                $rights,
                'kanban_column:move',
                $column_ids
            );

            $this->node_js_client->sendMessage($message);
        }
    }

    /**
     * Get cumulative flow
     *
     * For each column, get the total number of kanban items that were in this column for the requested period.
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/cumulative_flow
     * @access hybrid
     *
     * @param int    $id                     Id of the kanban
     * @param string $start_date             Start date of the cumulative flow in ISO format (YYYY-MM-DD) {@from path}{@type date}
     * @param string $end_date               End date of the cumulative flow in ISO format (YYYY-MM-DD) {@from path}{@type date}
     * @param int    $interval_between_point Number of days between 2 points of the cumulative flow {@from path}{@type int}{@min 1}
     *
     * @return Tuleap\AgileDashboard\REST\v1\Kanban\CumulativeFlowDiagram\DiagramRepresentation
     *
     * @throws 400
     * @throws 403
     * @throws 404
     */
    public function getCumulativeFlow($id, $start_date, $end_date, $interval_between_point)
    {
        $this->checkAccess();
        $user             = $this->getCurrentUser();
        $kanban           = $this->getKanban($user, $id);
        $artifact_factory = Tracker_ArtifactFactory::instance();

        $representation_builder = new DiagramRepresentationBuilder(
            new KanbanCumulativeFlowDiagramDao(),
            $this->kanban_column_factory,
            $artifact_factory
        );

        Header::allowOptionsGet();

        $datetime_start = new DateTime($start_date);
        $datetime_end   = new DateTime($end_date);
        if ($datetime_start > $datetime_end) {
            throw new RestException(400, '`start_date` must be older than `end_date`');
        }

        try {
            $diagram_representation = $representation_builder->build(
                $kanban,
                $user,
                $datetime_start,
                $datetime_end,
                $interval_between_point
            );
        } catch (TooMuchPointsException $exception) {
            throw new RestException(
                400,
                'Number of points requested is too large, you can request for ' . DiagramRepresentationBuilder::MAX_POSSIBLE_POINTS . 'maximum'
            );
        }

        return $diagram_representation;
    }

    /**
     * @url OPTIONS {id}/cumulative_flow
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @param int $id Id of the Kanban
     */
    public function optionsCumulativeFlow($id)
    {
        Header::allowOptionsGet();
    }

    private function checkColumnIdsExist(PFUser $user, AgileDashboard_Kanban $kanban, array $column_ids) {
        foreach ($column_ids as $column_id) {
            if (! $this->columnIsInTracker($kanban, $user, $column_id)) {
                throw new RestException(404, "Column $column_id is not known");
            }
        }
    }

    /** @return AgileDashboard_Kanban */
    private function getKanban(PFUser $user, $id) {
        try {
            $kanban = $this->kanban_factory->getKanban($user, $id);
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
            throw new RestException(404);
        } catch (AgileDashboard_KanbanCannotAccessException $exception) {
            throw new RestException(403);
        }

        return $kanban;
    }

    /**
     * @return PFUser
     */
    private function getCurrentUser() {
        $user = UserManager::instance()->getCurrentUser();

        return $user;
    }

    private function getSemanticStatus(AgileDashboard_Kanban $kanban) {
        $tracker = $this->getTrackerForKanban($kanban);
        if (! $tracker) {
            return;
        }

        $semantic = Tracker_Semantic_Status::load($tracker);
        if (! $semantic->getFieldId()) {
            return;
        }

        return $semantic;
    }

    /**
     * @return Tracker
     */
    private function getTrackerForKanban(AgileDashboard_Kanban $kanban) {
        $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
        if (! $tracker) {
            throw new RestException(500, 'The tracker used by the kanban does not exist anymore');
        }

        return $tracker;
    }

    /**
     * @return int
     */
    private function getProjectIdForKanban(AgileDashboard_Kanban $kanban) {
        return $this->tracker_factory->getTrackerById($kanban->getTrackerId())->getGroupId();
    }

    private function sendMessageForDroppingItem(
        PFUser $current_user,
        AgileDashboard_Kanban $kanban,
        $order,
        $add,
        $in_column,
        $from_column
    ) {
        if(isset($_SERVER[self::HTTP_CLIENT_UUID]) && $_SERVER[self::HTTP_CLIENT_UUID]) {
            $data_to_send = array(
                'add'         => $add,
                'order'       => $order,
                'in_column'   => $in_column,
                'from_column' => $from_column
            );
            if($add) {
                $this->sendMessageForEachArtifact($current_user, $kanban, $add->ids, $data_to_send);
            } else {
                $this->sendMessageForEachArtifact($current_user, $kanban, $order->ids, $data_to_send);
            }

        }
    }

    private function sendMessageForEachArtifact(PFUser $current_user, AgileDashboard_Kanban $kanban, array $artifact_ids, array $data) {
        foreach($artifact_ids as $artifact_id) {
            $artifact = $this->artifact_factory->getArtifactById($artifact_id);
            $rights   = new KanbanArtifactRightsPresenter($artifact, $this->permissions_serializer);
            $message  = new MessageDataPresenter(
                $current_user->getId(),
                $_SERVER[self::HTTP_CLIENT_UUID],
                $kanban->getId(),
                $rights,
                'kanban_item:move',
                $data
            );

            $this->node_js_client->sendMessage($message);
        }
    }
}
