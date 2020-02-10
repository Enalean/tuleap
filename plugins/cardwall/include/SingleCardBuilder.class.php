<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\Cardwall\AccentColor\AccentColor;
use Tuleap\Cardwall\AccentColor\AccentColorBuilder;
use Tuleap\Cardwall\BackgroundColor\BackgroundColor;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\Cardwall\Semantic\BackgroundColorFieldRetriever;

class Cardwall_SingleCardBuilder
{
    /** @var Cardwall_OnTop_ConfigFactory */
    private $config_factory;

    /** @var UserManager */
    private $card_fields;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var BackgroundColorFieldRetriever */
    private $background_color_builder;

    /** @var AccentColorBuilder */
    private $accent_color_builder;

    public function __construct(
        Cardwall_OnTop_ConfigFactory $config_factory,
        Cardwall_CardFields $card_fields,
        Tracker_ArtifactFactory $artifact_factory,
        PlanningFactory $planning_factory,
        BackgroundColorBuilder $background_color_builder,
        AccentColorBuilder $accent_color_builder
    ) {
        $this->config_factory           = $config_factory;
        $this->card_fields              = $card_fields;
        $this->artifact_factory         = $artifact_factory;
        $this->planning_factory         = $planning_factory;
        $this->background_color_builder = $background_color_builder;
        $this->accent_color_builder     = $accent_color_builder;
    }

    /**
     * Return a new card controller
     *
     * @return Cardwall_SingleCard
     *
     * @throws Exception
     */
    public function getSingleCard(PFUser $user, $artifact_id, $planning_id)
    {
        $card_artifact       = $this->getArtifact($artifact_id);
        $config              = $this->getConfig($planning_id);
        $field_provider      = $this->getFieldRetriever($config);
        $columns             = $config->getDashboardColumns();
        $display_preferences = new Cardwall_UserPreferences_UserPreferencesDisplayUser(
            Cardwall_UserPreferences_UserPreferencesDisplayUser::DISPLAY_AVATARS
        );

        $semantic_card_fields = Cardwall_Semantic_CardFields::load($card_artifact->getTracker());
        $background_color     = $this->background_color_builder->build(
            $semantic_card_fields,
            $card_artifact,
            $user
        );
        $accent_color = $this->accent_color_builder->build($card_artifact, $user);

        $presenter_factory = $this->getCardInCellPresenterFactory($config, $card_artifact, $field_provider, $columns);

        $card_in_cell_presenter = $this->getCardInCellPresenter(
            $presenter_factory,
            $user,
            $card_artifact,
            $this->card_fields,
            $display_preferences,
            $background_color,
            $accent_color
        );

        return new Cardwall_SingleCard(
            $card_in_cell_presenter,
            $this->card_fields,
            $display_preferences,
            $this->getColumnId($card_artifact, $columns, $config, $field_provider),
            $config->getMappingFor($card_artifact->getTracker())
        );
    }

    /**
     * @return Cardwall_CardInCellPresenter
     */
    protected function getCardInCellPresenter(
        Cardwall_CardInCellPresenterFactory $presenter_factory,
        PFUser $user,
        Tracker_Artifact $artifact,
        Cardwall_CardFields $card_fields,
        Cardwall_UserPreferences_UserPreferencesDisplayUser $display_preferences,
        BackgroundColor $background_color,
        AccentColor $accent_color
    ) {
        return $presenter_factory->getCardInCellPresenter(
            $this->getCardPresenter(
                $user,
                $artifact,
                $card_fields,
                $display_preferences,
                $background_color,
                $accent_color
            )
        );
    }

    /**
     * @return Cardwall_CardPresenter
     */
    private function getCardPresenter(
        PFUser $user,
        Tracker_Artifact $artifact,
        Cardwall_CardFields $card_fields,
        Cardwall_UserPreferences_UserPreferencesDisplayUser $display_preferences,
        BackgroundColor $background_color,
        AccentColor $accent_color
    ) {
        $parent_artifact = $artifact->getParent($user);

        return new Cardwall_CardPresenter(
            $user,
            $artifact,
            $card_fields,
            $accent_color,
            $display_preferences,
            null,
            $artifact->getAllowedChildrenTypesForUser($user),
            $background_color,
            $parent_artifact
        );
    }

    private function getColumnId(Tracker_Artifact $artifact, Cardwall_OnTop_Config_ColumnCollection $columns, Cardwall_OnTop_Config $config, Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider)
    {
        foreach ($columns as $column) {
            if ($config->isInColumn($artifact, $field_provider, $column)) {
                return $column->getId();
            }
        }
        return -1;
    }

    private function getArtifact($artifact_id)
    {
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if ($artifact) {
            return $artifact;
        }
        throw new CardControllerBuilderRequestIdException();
    }

    private function getConfig($planning_id)
    {
        $config = $this->config_factory->getOnTopConfigByPlanning($this->getPlanning($planning_id));
        if ($config && $config->isEnabled()) {
            return $config;
        }
        throw new CardControllerBuilderRequestDataException();
    }

    private function getPlanning($planning_id)
    {
        $planning = $this->planning_factory->getPlanning($planning_id);
        if ($planning) {
            return $planning;
        }
        throw new CardControllerBuilderRequestPlanningIdException();
    }

    private function getFieldRetriever(Cardwall_OnTop_Config $config)
    {
        return new Cardwall_OnTop_Config_MappedFieldProvider(
            $config,
            new Cardwall_FieldProviders_SemanticStatusFieldRetriever()
        );
    }

    /**
     *
     * @return Cardwall_CardInCellPresenterFactory
     */
    private function getCardInCellPresenterFactory(
        Cardwall_OnTop_Config $config,
        Tracker_Artifact $artifact,
        Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider,
        Cardwall_OnTop_Config_ColumnCollection $columns
    ) {
        $field = $field_provider->getField($artifact->getTracker());
        $status_fields = [];
        if ($field !== null) {
            $status_fields[$field->getId()] = $field;
        }
        return new Cardwall_CardInCellPresenterFactory(
            $field_provider,
            $config->getCardwallMappings($status_fields, $columns)
        );
    }
}
