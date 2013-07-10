<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class Cardwall_CardControllerBuilder {

    /** @var Cardwall_OnTop_ConfigFactory */
    private $config_factory;

    public function __construct(Cardwall_OnTop_ConfigFactory $config_factory) {
        $this->config_factory = $config_factory;
    }

    /**
     * Return a new card controller
     *
     * @param Codendi_Request $request
     *
     * @return Cardwall_CardController
     *
     * @throws Exception
     */
    public function getCardController(Codendi_Request $request) {
        $card_artifact   = $this->getArtifact($request);
        $config          = $this->getConfig($request);
        $field_provider = $this->getFieldRetriever($config);
        $columns         = $config->getDashboardColumns();
        $user_manager    = UserManager::instance();
        $factory         = Tracker_FormElementFactory::instance();

        return new Cardwall_CardController(
            $request,
            $card_artifact,
            new Cardwall_CardFields($user_manager, $factory),
            new Cardwall_UserPreferences_UserPreferencesDisplayUser(Cardwall_UserPreferences_UserPreferencesDisplayUser::DISPLAY_AVATARS),
            $config,
            $field_provider,
            $this->getCardInCellPresenterFactory($config, $card_artifact, $field_provider, $columns),
            $columns
        );
    }

    private function getArtifact(Codendi_Request $request) {
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($request->getValidated('id', 'uint', 0));
        if ($artifact) {
            return $artifact;
        }
        throw new CardControllerBuilderRequestIdException();
    }

    private function getConfig(Codendi_Request $request) {
        $config = $this->config_factory->getOnTopConfigByPlanning($this->getPlanning($request));
        if ($config) {
            return $config;
        }
        throw new CardControllerBuilderRequestDataException();
    }

    private function getPlanning(Codendi_Request $request) {
        $planning = PlanningFactory::build()->getPlanning($request->get('planning_id'));
        if ($planning) {
            return $planning;
        }
        throw new CardControllerBuilderRequestPlanningIdException();
    }

    private function getFieldRetriever(Cardwall_OnTop_Config $config) {
        return new Cardwall_OnTop_Config_MappedFieldProvider(
            $config,
            new Cardwall_FieldProviders_SemanticStatusFieldRetriever()
        );
    }

    private function getCardInCellPresenterFactory(Cardwall_OnTop_Config $config, Tracker_Artifact $artifact, Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider, Cardwall_OnTop_Config_ColumnCollection $columns) {
        $field = $field_provider->getField($artifact->getTracker());
        $status_fields[$field->getId()] = $field;
        return new Cardwall_CardInCellPresenterFactory(
            $field_provider,
            $config->getCardwallMappings($status_fields, $columns)
        );
    }
}

?>
