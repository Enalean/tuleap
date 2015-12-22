<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

use Luracast\Restler\RestException;
use Tuleap\AgileDashboard\KanbanArtifactRightsPresenter;
use Tuleap\RealTime\MessageDataPresenter;
use Tuleap\REST\Header;
use Tuleap\REST\AuthenticatedResource;
use AgileDashboard_PermissionsManager;
use AgileDashboard_KanbanDao;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanNotFoundException;
use AgileDashboard_KanbanCannotAccessException;
use AgileDashboard_Kanban;
use AgileDashboard_KanbanColumnFactory;
use AgileDashboard_KanbanColumnDao;
use AgileDashboard_KanbanColumnManager;
use AgileDashboard_KanbanItemDao;
use AgileDashboardStatisticsAggregator;
use TrackerFactory;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use UserManager;
use Tracker;
use PFUser;
use Tracker_FormElement_Field_List;
use Tracker_Semantic_Status;
use Tracker_REST_Artifact_ArtifactCreator                 as ArtifactCreator;
use Tracker_REST_Artifact_ArtifactValidator               as ArtifactValidator;
use Tuleap\Tracker\REST\TrackerReference                  as TrackerReference;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation   as ArtifactValuesRepresentation;
use AgileDashboard_KanbanUserPreferences;
use AgileDashboard_KanbanItemManager;
use Tuleap\RealTime\NodeJSClient;
use AgileDashboard_KanbanActionsChecker;
use Tracker_FormElement_Field_List_Bind_Static_ValueDao;
use Tracker_Permission_PermissionsSerializer;
use Tracker_Permission_PermissionRetrieveAssignee;

class KanbanItemsResource extends AuthenticatedResource {

    /** @var AgileDashboard_KanbanFactory */
    private $kanban_factory;

    /** @var AgileDashboard_KankanColumnFactory */
    private $kanban_column_factory;

    /** @var AgileDashboard_KanbanColumnManager */
    private $kanban_column_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var TimeInfoFactory */
    private $time_info_factory;

    /** @var AgileDashboard_KanbanItemManager */
    private $kanban_item_manager;

    /** @var AgileDashboardStatisticsAggregator */
    private $statistics_aggregator;

    /** @var NodeJSClient */
    private $node_js_client;

    /** @var Tracker_Permission_PermissionsSerializer */
    private $permissions_serializer;

    const HTTP_CLIENT_UUID = 'HTTP_X_CLIENT_UUID';

    public function __construct() {
        $this->tracker_factory      = TrackerFactory::instance();
        $this->artifact_factory     = Tracker_ArtifactFactory::instance();
        $this->form_element_factory = Tracker_FormElementFactory::instance();

        $this->kanban_factory = new AgileDashboard_KanbanFactory(
            $this->tracker_factory,
            new AgileDashboard_KanbanDao()
        );

        $kanban_column_dao           = new AgileDashboard_KanbanColumnDao();
        $permissions_manager         = new AgileDashboard_PermissionsManager();
        $this->kanban_column_factory = new AgileDashboard_KanbanColumnFactory(
            $kanban_column_dao,
            new AgileDashboard_KanbanUserPreferences()
        );
        $this->kanban_column_manager = new AgileDashboard_KanbanColumnManager(
            $kanban_column_dao,
            new Tracker_FormElement_Field_List_Bind_Static_ValueDao(),
            new AgileDashboard_KanbanActionsChecker(
                $this->tracker_factory,
                $permissions_manager,
                $this->form_element_factory
            )
        );

        $kanban_item_dao             = new AgileDashboard_KanbanItemDao();
        $this->time_info_factory     = new TimeInfoFactory($kanban_item_dao);
        $this->kanban_item_manager   = new AgileDashboard_KanbanItemManager($kanban_item_dao);

        $this->statistics_aggregator = new AgileDashboardStatisticsAggregator();

        $this->node_js_client         = new NodeJSClient();
        $this->permissions_serializer = new Tracker_Permission_PermissionsSerializer(
            new Tracker_Permission_PermissionRetrieveAssignee(UserManager::instance())
        );
    }

    /**
     * @url OPTIONS
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     */
    public function options() {
        Header::allowOptionsGetPost();
    }

    /**
     * Add new Kanban Item
     *
     * Create a kanban item in the given column or backlog
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     * <pre>
     * /!\ Only works for trackers that allow artifact creation with only a "title" /!\
     * </pre>
     *
     * @access protected
     *
     * @url POST
     *
     * @param KanbanItemPOSTRepresentation $item The created kanban item {@from body} {@type Tuleap\AgileDashboard\REST\v1\Kanban\KanbanItemPOSTRepresentation}
     *
     * @status 201
     */
    protected function post(KanbanItemPOSTRepresentation $item) {
        $current_user = $this->getCurrentUser();
        $kanban       = $this->getKanban($current_user, $item->kanban_id);
        $tracker      = $this->tracker_factory->getTrackerById($kanban->getTrackerId());

        $updater = new ArtifactCreator(
            new ArtifactValidator(
                $this->form_element_factory
            ),
            $this->artifact_factory,
            $this->tracker_factory
        );

        $tracker_reference = new TrackerReference();
        $tracker_reference->build($tracker);

        $artifact_fields = $this->buildFieldsData($tracker, $item);

        $art_ref = $updater->create($current_user, $tracker_reference, $artifact_fields);

        $artifact = $art_ref->getArtifact();
        if (! $artifact) {
            throw new RestException(500, implode('. ', $GLOBALS['Response']->getFeedbackErrors()));
        }

        $this->statistics_aggregator->addKanbanAddInPlaceHit(
            $tracker->getGroupId()
        );

        $item_representation = $this->buildItemRepresentation($artifact);

        if(isset($_SERVER[self::HTTP_CLIENT_UUID]) && $_SERVER[self::HTTP_CLIENT_UUID]) {
            $kanban_id = $this->kanban_factory->getKanbanIdByTrackerId($artifact->getTrackerId());
            $item      = $item_representation;
            array_push($item->card_fields, $artifact->getTracker()->getTitleField());
            array_push($item->card_fields, $artifact->getTracker()->getStatusField());
            $data = array(
                'artifact' => $item
            );
            $rights   = new KanbanArtifactRightsPresenter($artifact, $this->permissions_serializer);
            $message  = new MessageDataPresenter(
                $current_user->getId(),
                $_SERVER[self::HTTP_CLIENT_UUID],
                $kanban_id,
                $rights,
                'kanban_item:create',
                $data
            );

            $this->node_js_client->sendMessage($message);
        }

        return $item_representation;
    }

    /**
     * Get a Kanban item
     *
     *
     * Get the Kanban representation of the given artifact.
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     * <br/>
     * Example:
     * <pre><code>
     * {<br/>
     *     "id": 195,<br/>
     *     "item_name": "task",<br/>
     *     "label": "My Kanban task",<br/>
     *     "color": "inca_silver",<br/>
     *     "card_fields": [<br/>
     *         {<br/>
     *             "field_id": 7132,<br/>
     *             "type": "string",<br/>
     *             "label": "Title",<br/>
     *             "value": "My Kanban Task"<br/>
     *         }<br/>
     *     ],<br/>
     *     "in_column": "backlog"<br/>
     * }<br/>
     * </code></pre>
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param  int  $id     Id of the artifact
     * @return Tuleap\AgileDashboard\REST\v1\Kanban\KanbanRepresentation
     */
    protected function get($id) {
        $this->checkAccess();

        $current_user = $this->getCurrentUser();
        $artifact     = $this->artifact_factory->getArtifactById($id);

        if (! $artifact) {
            throw new RestException(404, 'Kanban item not found.');
        }

        if (! $artifact->userCanView($current_user)) {
            throw new RestException(403, 'You cannot access this kanban item.');
        }

        $item_representation = $this->buildItemRepresentation($artifact);

        if(isset($_SERVER[self::HTTP_CLIENT_UUID]) && $_SERVER[self::HTTP_CLIENT_UUID]) {
            $index           = $this->kanban_item_manager->getIndexOfKanbanItem($artifact, $item_representation->in_column);
            $kanban_id       = $this->kanban_factory->getKanbanIdByTrackerId($artifact->getTrackerId());
            $current_user_id = $current_user->getId();
            $item            = $item_representation;
            array_push($item->card_fields, $artifact->getTracker()->getTitleField());
            array_push($item->card_fields, $artifact->getTracker()->getStatusField());
            $data = array(
                'artifact' => $item,
                'index'    => $index
            );
            $rights   = new KanbanArtifactRightsPresenter($artifact, $this->permissions_serializer);
            $message  = new MessageDataPresenter(
                $current_user_id,
                $_SERVER[self::HTTP_CLIENT_UUID],
                $kanban_id,
                $rights,
                'kanban_item:edit',
                $data
            );

            $this->node_js_client->sendMessage($message);
        }

        return $item_representation;
    }

    private function buildItemRepresentation(Tracker_Artifact $artifact) {
        $item_representation = new KanbanItemRepresentation();

        $item_in_backlog = $this->kanban_item_manager->isKanbanItemInBacklog($artifact);
        $in_column = ($item_in_backlog) ? 'backlog' : null;

        if (! $in_column) {
            $item_in_archive = $this->kanban_item_manager->isKanbanItemInArchive($artifact);
            $in_column = ($item_in_archive) ? 'archive' : null;
        }

        if (! $in_column) {
            $in_column = $this->kanban_item_manager->getColumnIdOfKanbanItem($artifact);
        }

        $item_representation->build(
            $artifact,
            $this->time_info_factory->getTimeInfo($artifact),
            $in_column
        );

        return $item_representation;
    }

    private function buildFieldsData(Tracker $tracker, KanbanItemPOSTRepresentation $item) {
        $fields_data = array();

        $this->addSummaryToFieldsData($tracker, $item, $fields_data);
        $this->addStatusToFieldsData($tracker, $item, $fields_data);

        return $fields_data;
    }

    private function addSummaryToFieldsData(Tracker $tracker, KanbanItemPOSTRepresentation $item, array &$fields_data) {
        $summary_field = $tracker->getTitleField();

        if (! $summary_field) {
            throw new RestException(403);
        }

        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = (int) $summary_field->getId();
        $representation->value    = $item->label;

        $fields_data[] = $representation;
    }

    private function addStatusToFieldsData(Tracker $tracker, KanbanItemPOSTRepresentation $item, array &$fields_data) {
        $status_field = $tracker->getStatusField();

        if (! $status_field) {
            throw new RestException(403);
        }

        $value = Tracker_FormElement_Field_List::NONE_VALUE;
        if (! empty($item->column_id)) {
            $semantic = Tracker_Semantic_Status::load($tracker);

            if (! $semantic->getFieldId()) {
                throw new RestException(403);
            }

            if (! in_array($item->column_id, $semantic->getOpenValues())) {
                throw new RestException(400, 'Unknown column');
            }

            $value = $item->column_id;
        }

        $representation                 = new ArtifactValuesRepresentation();
        $representation->field_id       = (int) $status_field->getId();
        $representation->bind_value_ids = array((int) $value);

        $fields_data[] = $representation;

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

    private function getCurrentUser() {
        $user = UserManager::instance()->getCurrentUser();

        return $user;
    }
}
