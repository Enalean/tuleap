<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;

/**
 * I'm responsible for building Cardwall_Board regardless of it's future use
 */

class Cardwall_RawBoardBuilder
{
    /**
     * Build a Cardwall_Board taking account of Mapped Fieds
     *
     *
     * @return Cardwall_Board
     */
    public function buildBoardUsingMappedFields(
        PFUser $user,
        Tracker_ArtifactFactory $artifact_factory,
        Planning_Milestone $milestone,
        Cardwall_OnTop_Config $config,
        Cardwall_OnTop_Config_ColumnCollection $columns
    ) {
        $planning       = $milestone->getPlanning();
        $field_provider = new Cardwall_OnTop_Config_MappedFieldProvider(
            $config,
            new Cardwall_FieldProviders_SemanticStatusFieldRetriever()
        );

        $column_preferences = new Cardwall_UserPreferences_Autostack_AutostackDashboard($user, $config->getTracker());
        $column_autostack   = new Cardwall_UserPreferences_UserPreferencesAutostackFactory();
        $column_autostack->setAutostack($columns, $column_preferences);

        $mapping_collection       = $this->getMappingCollection($planning, $columns, $field_provider, $config);
        $form_element_factory     = Tracker_FormElementFactory::instance();
        $background_color_builder = new BackgroundColorBuilder(new BindDecoratorRetriever());
        $accent_color_builder     = new \Tuleap\Cardwall\AccentColor\AccentColorBuilder(
            $form_element_factory,
            new BindDecoratorRetriever()
        );
        $presenter_builder        = new Cardwall_CardInCellPresenterBuilder(
            new Cardwall_CardInCellPresenterFactory($field_provider, $mapping_collection),
            new Cardwall_CardFields($form_element_factory),
            $this->getDisplayPreferences($milestone, $user),
            $user,
            $background_color_builder,
            $accent_color_builder
        );

        $swimline_factory   = new Cardwall_SwimlineFactory($config, $field_provider);
        $pane_board_builder = new Cardwall_PaneBoardBuilder(
            $presenter_builder,
            $artifact_factory,
            new AgileDashboard_BacklogItemDao(),
            $swimline_factory
        );
        $board              = $pane_board_builder->getBoard($user, $milestone->getArtifact(), $columns, $mapping_collection);

        return $board;
    }

    /**
     * Get the display preferences of a user for a given milestone
     *
     *
     * @return Cardwall_UserPreferences_UserPreferencesDisplayUser
     */
    public function getDisplayPreferences(Planning_Milestone $milestone, PFUser $user)
    {
        $pref_name = Cardwall_UserPreferences_UserPreferencesDisplayUser::ASSIGNED_TO_USERNAME_PREFERENCE_NAME . $milestone->getTrackerId();
        $display_avatars = $user->isAnonymous() || ! $user->getPreference($pref_name);

        return new Cardwall_UserPreferences_UserPreferencesDisplayUser($display_avatars);
    }

    private function getMappingCollection(Planning $planning, Cardwall_OnTop_Config_ColumnCollection $columns, Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider, Cardwall_OnTop_Config $config)
    {
        $trackers_used_on_cardwall = array();

        foreach ($planning->getBacklogTrackers() as $backlog_tracker) {
            $trackers_used_on_cardwall[] = $backlog_tracker->getChildren();
        }

        return $config->getCardwallMappings(
            $this->getIndexedStatusFieldsOf($trackers_used_on_cardwall, $field_provider),
            $columns
        );
    }

    private function getIndexedStatusFieldsOf(array $trackers, $field_provider)
    {
        $status_fields = array();

        foreach ($trackers as $tracker) {
            $status_fields = array_merge($status_fields, array_filter(array_map(array($field_provider, 'getField'), $tracker)));
        }
        $indexed_status_fields  = $this->indexById($status_fields);
        return $indexed_status_fields;
    }

    private function indexById(array $fields)
    {
        $indexed_array = array();
        foreach ($fields as $field) {
            $indexed_array[$field->getId()] = $field;
        }
        return $indexed_array;
    }
}
