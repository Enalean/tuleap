<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/TreeNode/TreeNodeMapper.class.php';
require_once 'common/templating/TemplateRendererFactory.class.php';

/**
 * A pane to be displayed in AgileDashboard
 */
class Cardwall_Pane extends AgileDashboard_Pane {

    /**
     * @var Cardwall_PaneInfo
     */
    private $info;

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * @var bool
     */
    private $enable_qr_code;

    /**
     * @var Cardwall_OnTop_Config
     */
    private $config;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    public function __construct(
            Cardwall_PaneInfo $info,
            Planning_Milestone $milestone,
            $enable_qr_code,
            Cardwall_OnTop_Config $config,
            PFUser $user,
            Planning_MilestoneFactory $milestone_factory
            ) {
        $this->info           = $info;
        $this->milestone      = $milestone;
        $this->enable_qr_code = $enable_qr_code;
        $this->config         = $config;
        $this->user           = $user;
        $this->milestone_factory = $milestone_factory;
        $this->artifact_factory = Tracker_ArtifactFactory::instance();
    }

    public function getIdentifier() {
        return $this->info->getIdentifier();
    }

    public function getUriForMilestone(Planning_Milestone $milestone) {
        return $this->info->getUriForMilestone($milestone);
    }


    /**
     * @see AgileDashboard_Pane::getFullContent()
     */
    public function getFullContent() {
        return $this->getPaneContent('agiledashboard-fullpane');
    }

    /**
     * @see AgileDashboard_Pane::getMinimalContent()
     */
    public function getMinimalContent() {
        return $this->getPaneContent('agiledashboard-minimalpane');
    }

    private function getPaneContent($template) {
        $event_manager = EventManager::instance();
        $columns = $this->config->getDashboardColumns();
        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__).'/../templates');
        $html = $renderer->renderToString($template, $this->getPresenterUsingMappedFields($columns));
        // TODO what if no semantic status and no mapping????

        $event_manager->processEvent(CARDWALL_EVENT_DISPLAYED, array('html' => &$html));

        return $html;
    }

    /**
     * @return Cardwall_PaneContentPresenter
     */
    private function getPresenterUsingMappedFields(Cardwall_OnTop_Config_ColumnCollection $columns) {
        $board_factory       = new Cardwall_BoardFactory();
        $planning            = $this->milestone->getPlanning();

        $field_provider     = new Cardwall_OnTop_Config_MappedFieldProvider($this->config,
                                new Cardwall_FieldProviders_SemanticStatusFieldRetriever());

        $display_preferences = $this->getDisplayPreferences();
        $column_preferences  = new Cardwall_UserPreferences_Autostack_AutostackDashboard($this->user, $this->config->getTracker());
        $column_autostack    = new Cardwall_UserPreferences_UserPreferencesAutostackFactory();
        $column_autostack->setAutostack($columns, $column_preferences);

        $mapping_collection = $this->getMappingCollection($planning, $columns, $field_provider);
        $card_in_cell_presenter_factory = new Cardwall_CardInCellPresenterFactory($field_provider, $mapping_collection);
        $node_factory = new Cardwall_CardInCellPresenterNodeFactory(
            new Cardwall_CardInCellPresenterFactory($field_provider, $mapping_collection),
            new Cardwall_CardFields(UserManager::instance(), Tracker_FormElementFactory::instance()),
            $display_preferences,
            $this->user
        );

        $pane_builder = new Cardwall_PaneBuilder($node_factory, $this->artifact_factory, new AgileDashboard_BacklogItemDao());
        $planned_artifacts   = $pane_builder->getPlannedArtifacts($this->user, $this->milestone->getArtifact());

        $board               = $board_factory->getBoard($field_provider, $columns, $planned_artifacts, $this->config, $this->user, $display_preferences, $mapping_collection);
        $backlog_title       = $this->milestone->getPlanning()->getBacklogTracker()->getName();
        $redirect_parameter  = 'cardwall[agile]['. $planning->getId() .']='. $this->milestone->getArtifactId();



        return new Cardwall_PaneContentPresenter(
            $board,
            $this->getQrCode(),
            $redirect_parameter,
            $backlog_title,
            $this->canConfigure(),
            $this->getSwitchDisplayAvatarsURL(),
            $display_preferences->shouldDisplayAvatars(),
            $planning
        );
    }

    private function getMappingCollection(Planning $planning, Cardwall_OnTop_Config_ColumnCollection $columns, Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider) {
        $trackers_used_on_cardwall = $planning->getBacklogTracker()->getChildren();

        return $this->config->getCardwallMappings(
            $this->getIndexedStatusFieldsOf($trackers_used_on_cardwall, $field_provider),
            $columns
        );
    }

    private function getIndexedStatusFieldsOf(array $trackers, $field_provider) {
        $status_fields          = array_filter(array_map(array($field_provider, 'getField'), $trackers));
        $indexed_status_fields  = $this->indexById($status_fields);
        return $indexed_status_fields;
    }

    private function indexById(array $fields) {
        $indexed_array = array();
        foreach ($fields as $field) {
            $indexed_array[$field->getId()] = $field;
        }
        return $indexed_array;
    }


    private function getDisplayPreferences() {
        $pref_name = Cardwall_UserPreferences_UserPreferencesDisplayUser::ASSIGNED_TO_USERNAME_PREFERENCE_NAME . $this->milestone->getTrackerId();
        $display_avatars = $this->user->isAnonymous() || ! $this->user->getPreference($pref_name);

        return new Cardwall_UserPreferences_UserPreferencesDisplayUser($display_avatars);
    }

    private function canConfigure() {
        $project = $this->milestone->getProject();
        if ($project->userIsAdmin($this->user)){
            $configure_url      = TRACKER_BASE_URL .'/?tracker='. $this->milestone->getTrackerId() .'&func=admin-cardwall';
            return $configure_url;
        }
        return false;
    }

    /**
     * @return Cardwall_QrCode
     */
    private function getQrCode() {
        if ($this->enable_qr_code) {
            return new Cardwall_QrCode($_SERVER['REQUEST_URI'] .'&pv=2');
        }
        return false;
    }

    private function getSwitchDisplayAvatarsURL() {
        if ($this->user->isAnonymous()) {
            return false;
        }

        $group_id    = $this->milestone->getGroupId();
        $planning_id = $this->milestone->getPlanningId();
        $tracker_id  = $this->milestone->getTrackerId();
        $artifact_id = $this->milestone->getArtifactId();

        $action      = 'toggle_user_display_avatar';

        $switch_display_username_url =
            CARDWALL_BASE_URL
            . '/?group_id='   . $group_id
            . '&planning_id=' . $planning_id
            . '&tracker_id='  . $tracker_id
            . '&aid='         . $artifact_id
            . '&action='      . $action;

        return $switch_display_username_url;
    }
}
?>
