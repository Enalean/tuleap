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
namespace Tuleap\Cardwall\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use Planning_Milestone;
use Cardwall_OnTop_ConfigFactory;
use Tracker_ArtifactFactory;
use Cardwall_RawBoardBuilder;
use UserManager;

class MilestonesCardwallResource
{
    /** @var Cardwall_OnTop_ConfigFactory */
    private $config_factory;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(Cardwall_OnTop_ConfigFactory $config_factory)
    {
        $this->config_factory   = $config_factory;
        $this->artifact_factory = Tracker_ArtifactFactory::instance();
    }

    public function options()
    {
        $this->sendAllowHeaderForCardwall();
    }

    /**
     * Get milestone
     *
     * Get the definition of a given the milestone
     *
     * @url GET {id}/cardwall
     *
     * @param int $id Id of the milestone
     *
     * @return \AgileDashboard_MilestonesCardwallRepresentation
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function get(Planning_Milestone $milestone)
    {
        $this->checkCardwallIsEnabled($milestone);

        $this->sendAllowHeaderForCardwall();
        $board = $this->getBoard($milestone);
        $board_representation = new \AgileDashboard_MilestonesCardwallRepresentation();
        $board_representation->build($board, $milestone->getPlanningId(), $this->getCurrentUser());

        return $board_representation;
    }

    private function getBoard(Planning_Milestone $milestone)
    {
        $raw_board_builder = new Cardwall_RawBoardBuilder();
        $config            = $this->config_factory->getOnTopConfigByPlanning($milestone->getPlanning());

        $board = $raw_board_builder->buildBoardUsingMappedFields(
            $this->getCurrentUser(),
            $this->artifact_factory,
            $milestone,
            $config,
            $config->getDashboardColumns()
        );

        return $board;
    }

    private function checkCardwallIsEnabled(Planning_Milestone $milestone)
    {
        $config = $this->config_factory->getOnTopConfig($milestone->getArtifact()->getTracker());

        if (! $config->isEnabled()) {
            throw new RestException(404);
        }
    }

    private function getCurrentUser()
    {
        return UserManager::instance()->getCurrentUser();
    }

    private function sendAllowHeaderForCardwall()
    {
        Header::allowOptionsGet();
    }
}
