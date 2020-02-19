<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\REST\v1\Columns;

use Cardwall_OnTop_ColumnDao;
use Luracast\Restler\RestException;
use PFUser;
use Planning;
use Planning_ArtifactMilestone;
use Tuleap\Cardwall\Column\ColumnColorRetriever;
use Tuleap\REST\JsonCast;
use UserManager;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsAllowedChecker;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsNotAllowedException;

class ColumnsGetter
{
    /** @var UserManager */
    private $user_manager;
    /** @var \Planning_MilestoneFactory */
    private $milestone_factory;
    /** @var MilestoneIsAllowedChecker */
    private $milestone_checker;
    /** @var Cardwall_OnTop_ColumnDao */
    private $column_dao;

    public function __construct(
        UserManager $user_manager,
        \Planning_MilestoneFactory $milestone_factory,
        MilestoneIsAllowedChecker $milestone_checker,
        Cardwall_OnTop_ColumnDao $column_dao
    ) {
        $this->user_manager      = $user_manager;
        $this->milestone_factory = $milestone_factory;
        $this->milestone_checker = $milestone_checker;
        $this->column_dao        = $column_dao;
    }

    public static function build(): self
    {
        return new self(
            UserManager::instance(),
            \Planning_MilestoneFactory::build(),
            MilestoneIsAllowedChecker::build(),
            new Cardwall_OnTop_ColumnDao()
        );
    }

    /**
     * @return ColumnRepresentation[]
     * @throws RestException
     */
    public function getColumns(int $milestone_id): array
    {
        $current_user = $this->getCurrentUser();
        $milestone    = $this->getMilestone($current_user, $milestone_id);
        return $this->buildColumnCollection($milestone->getPlanning());
    }

    /**
     * @throws RestException
     */
    private function getCurrentUser(): PFUser
    {
        try {
            return $this->user_manager->getCurrentUser();
        } catch (\Rest_Exception_InvalidTokenException | \User_LoginException $e) {
            throw new RestException(401, $e->getMessage());
        }
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
     * @return ColumnRepresentation[]
     */
    private function buildColumnCollection(Planning $planning): array
    {
        $columns = [];
        foreach ($this->column_dao->searchColumnsByTrackerId($planning->getPlanningTrackerId()) as $row) {
            $column = new ColumnRepresentation();
            $header_color = ColumnColorRetriever::getHeaderColorNameOrHex($row);
            $column->build(JsonCast::toInt($row['id']), $row['label'], $header_color);
            $columns[] = $column;
        }

        return $columns;
    }
}
