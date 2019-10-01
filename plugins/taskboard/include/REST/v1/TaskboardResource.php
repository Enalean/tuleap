<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\REST\v1;

use AgileDashboard_BacklogItemDao;
use AgileDashboardPlugin;
use Cardwall_OnTop_Dao;
use Luracast\Restler\RestException;
use PFUser;
use Planning_ArtifactMilestone;
use PluginManager;
use RuntimeException;
use Tracker_ArtifactFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsAllowedChecker;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsNotAllowedException;
use UserManager;

class TaskboardResource extends AuthenticatedResource
{
    private const MAX_LIMIT = 100;

    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var AgileDashboard_BacklogItemDao
     */
    private $backlog_item_dao;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var MilestoneIsAllowedChecker
     */
    private $milestone_checker;

    public function __construct()
    {
        $this->user_manager        = UserManager::instance();
        $this->backlog_item_dao    = new AgileDashboard_BacklogItemDao();
        $this->artifact_factory    = Tracker_ArtifactFactory::instance();

        $plugin_manager        = PluginManager::instance();
        $agiledashboard_plugin = $plugin_manager->getPluginByName('agiledashboard');
        if (! $agiledashboard_plugin instanceof AgileDashboardPlugin) {
            throw new RuntimeException('Cannot instantiate Agiledashboard plugin');
        }
        $this->milestone_factory = $agiledashboard_plugin->getMilestoneFactory();

        $taskboard_plugin = $plugin_manager->getPluginByName('taskboard');
        if (! $taskboard_plugin instanceof \taskboardPlugin) {
            throw new RuntimeException('Cannot instantiate taskboard plugin');
        }
        $this->milestone_checker = new MilestoneIsAllowedChecker(
            new Cardwall_OnTop_Dao(),
            $plugin_manager,
            $taskboard_plugin
        );
    }

    /**
     * @url OPTIONS {id}/cards
     */
    public function optionsCards(int $id): void
    {
        $this->sendCardsAllowHeaders();
    }

    /**
     * Get top-level cards
     *
     * Get cards that are at top level of the taskboard. Those cards are either solo items or have children.
     *
     * @url    GET {id}/cards
     * @access hybrid
     *
     * @param int $id     Id of the taskboard
     * @param int $limit  Number of elements displayed per page {@from path}{@min 1}{@max 100}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type CardRepresentation}
     *
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getCards(int $id, int $limit = 100, int $offset = 0): array
    {
        $this->sendCardsAllowHeaders();
        $this->checkAccess();

        $card_representation_builder = CardRepresentationBuilder::buildSelf();

        $user        = $this->user_manager->getCurrentUser();
        $collection  = [];
        $milestone   = $this->getMilestone($user, $id);
        $backlog     = $this->backlog_item_dao->getBacklogArtifactsWithLimitAndOffset(
            $milestone->getArtifactId(),
            $limit,
            $offset
        );
        $total_count = $this->backlog_item_dao->foundRows();
        foreach ($backlog as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);
            if (! $artifact->userCanView($user)) {
                continue;
            }
            $collection[] = $card_representation_builder->build($milestone, $artifact, $user, (int) $row['rank']);
        }

        Header::sendPaginationHeaders($limit, $offset, $total_count, self::MAX_LIMIT);

        return $collection;
    }

    private function sendCardsAllowHeaders(): void
    {
        Header::allowOptionsGet();
    }

    /**
     * @throws RestException
     */
    private function getMilestone(PFUser $user, int $id): Planning_ArtifactMilestone
    {
        $milestone = $this->milestone_factory->getBareMilestoneByArtifactId($user, $id);
        if (! $milestone instanceof Planning_ArtifactMilestone) {
            throw new RestException(404);
        }

        try {
            $this->milestone_checker->checkMilestoneIsAllowed($milestone);
            return $milestone;
        } catch (MilestoneIsNotAllowedException $exception) {
            throw new RestException(404);
        }
    }
}
