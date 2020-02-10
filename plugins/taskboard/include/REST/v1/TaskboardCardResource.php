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

use AgileDashboardPlugin;
use Cardwall_OnTop_Dao;
use Luracast\Restler\RestException;
use PFUser;
use Planning_ArtifactMilestone;
use PluginManager;
use RuntimeException;
use Tracker_Artifact_PriorityManager;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsAllowedChecker;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsNotAllowedException;
use Tuleap\Taskboard\AgileDashboard\TaskboardUsage;
use Tuleap\Taskboard\AgileDashboard\TaskboardUsageDao;
use Tuleap\Taskboard\REST\v1\Card\CardPatcher;
use Tuleap\Taskboard\REST\v1\Card\CardPatchRepresentation;
use Tuleap\Tracker\Artifact\SlicedArtifactsBuilder;
use UserManager;

class TaskboardCardResource extends AuthenticatedResource
{
    private const MAX_LIMIT = 100;

    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var SlicedArtifactsBuilder
     */
    private $sliced_artifacts_builder;
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
        $this->user_manager     = UserManager::instance();
        $this->artifact_factory = Tracker_ArtifactFactory::instance();

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
            new TaskboardUsage(new TaskboardUsageDao()),
            $plugin_manager,
            $taskboard_plugin
        );

        $this->sliced_artifacts_builder = new SlicedArtifactsBuilder(
            new Tracker_ArtifactDao(),
            $this->artifact_factory
        );
    }

    /**
     * @url OPTIONS {id}/children
     */
    public function optionsChildren(int $id): void
    {
        $this->sendChildrenAllowHeaders();
    }

    /**
     * Get card children
     *
     * Get children of a card.
     *
     * @url    GET {id}/children
     * @access hybrid
     *
     * @param int $id           Id of the card
     * @param int $milestone_id Id of the milestone {@from path}
     * @param int $limit        Number of elements per page {@from path}{@min 1}{@max 100}
     * @param int $offset       Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type CardRepresentation}
     *
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getChildren(int $id, int $milestone_id, int $limit = 100, int $offset = 0): array
    {
        $this->sendChildrenAllowHeaders();
        $this->checkAccess();

        $user      = $this->user_manager->getCurrentUser();
        $milestone = $this->getMilestone($user, $milestone_id);
        $artifact  = $this->getArtifact($user, $id);

        $card_representation_builder = CardRepresentationBuilder::buildSelf();

        $collection      = [];
        $sliced_children = $this->sliced_artifacts_builder->getSlicedChildrenArtifactsForUser(
            $artifact,
            $user,
            $limit,
            $offset
        );
        foreach ($sliced_children->getArtifacts() as $child) {
            $collection[] = $card_representation_builder->build(
                $milestone,
                $child->getArtifact(),
                $user,
                $child->getRank()
            );
        }

        Header::sendPaginationHeaders($limit, $offset, $sliced_children->getTotalSize(), self::MAX_LIMIT);

        return $collection;
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId(int $id): void
    {
        $this->sendIdAllowHeaders();
    }

    /**
     * Get card
     *
     * Get a single card.
     *
     * @url    GET {id}
     * @access hybrid
     *
     * @param int $id           Id of the card
     * @param int $milestone_id Id of the milestone {@from path}
     *
     *
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getId(int $id, int $milestone_id): CardRepresentation
    {
        $this->sendIdAllowHeaders();
        $this->checkAccess();

        $user      = $this->user_manager->getCurrentUser();
        $milestone = $this->getMilestone($user, $milestone_id);
        $artifact  = $this->getArtifact($user, $id);

        $card_representation_builder = CardRepresentationBuilder::buildSelf();
        $priority_manager = Tracker_Artifact_PriorityManager::build();

        $rank = (int) $priority_manager->getGlobalRank($id);
        return $card_representation_builder->build($milestone, $artifact, $user, $rank);
    }

    /**
     * Patch card
     *
     * Update the content of a card
     *
     * <pre>
     * /!\ This REST route is under construction and subject to changes /!\
     * </pre>
     *
     * <br>
     * Example:
     * <pre>
     * { "remaining_effort": 13 }
     * </pre>
     *
     * @url PATCH {id}
     * @access protected
     *
     * @param int                     $id      Id of the card
     * @param CardPatchRepresentation $payload {@from body}
     *
     * @throws RestException 401
     * @throws RestException 404
     */
    public function patchId(int $id, CardPatchRepresentation $payload): void
    {
        $this->sendIdAllowHeaders();
        $this->checkAccess();

        $user    = $this->user_manager->getCurrentUser();
        $patcher = CardPatcher::build();
        $patcher->patchCard($this->getArtifact($user, $id), $user, $payload);
    }

    private function sendIdAllowHeaders(): void
    {
        Header::allowOptionsGetPatch();
    }

    private function sendChildrenAllowHeaders(): void
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

    /**
     * @throws RestException
     */
    private function getArtifact(PFUser $user, int $id): \Tracker_Artifact
    {
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $id);
        if (! $artifact) {
            throw new RestException(404);
        }

        return $artifact;
    }
}
