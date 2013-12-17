<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
namespace Tuleap\Cardwall\REST\v1;

use \Tuleap\REST\Header;
use \Luracast\Restler\RestException;
use \Cardwall_SingleCardBuilder;
use \Cardwall_OnTop_ConfigFactory;
use \Cardwall_CardFields;
use \TrackerFactory;
use \Tracker_FormElementFactory;
use \Tracker_ArtifactFactory;
use \PlanningFactory;
use \UserManager;
use \CardControllerBuilderRequestIdException;
use \CardControllerBuilderRequestDataException;
use \CardControllerBuilderRequestPlanningIdException;

class CardsResource {

    /** @var Cardwall_SingleCardBuilder */
    private $single_card_builder;

    public function __construct() {
        $this->single_card_builder = new Cardwall_SingleCardBuilder(
            new Cardwall_OnTop_ConfigFactory(
                TrackerFactory::instance(),
                Tracker_FormElementFactory::instance()
            ),
            new Cardwall_CardFields(
                UserManager::instance(),
                Tracker_FormElementFactory::instance()
            ),
            Tracker_ArtifactFactory::instance(),
            PlanningFactory::build()
        );
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptions();
    }

    /**
     * Return info card if exists
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the milestone
     *
     * @throws 403
     * @throws 404
     */
    protected function optionsId($id) {
        $this->getSingleCard($id);
        Header::allowOptionsPut();
    }

    private function getSingleCard($id) {
        try {
            list($planning_id, $artifact_id) = explode('_', $id);
            $user        = UserManager::instance()->getCurrentUser();
            $single_card = $this->single_card_builder->getSingleCard($user, $artifact_id, $planning_id)->getCardInCellPresenter();
            if ($single_card->getArtifact()->userCanView($user)) {
                return $single_card;
            }
            throw new RestException(403);
        } catch (CardControllerBuilderRequestIdException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (CardControllerBuilderRequestDataException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (CardControllerBuilderRequestPlanningIdException $exception) {
            throw new RestException(404, $exception->getMessage());
        }
        throw new RestException(404);
    }
}
