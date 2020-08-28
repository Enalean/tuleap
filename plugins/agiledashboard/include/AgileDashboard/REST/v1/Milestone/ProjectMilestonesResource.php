<?php
/**
 * Copyright (c) Enalean, 2013 – Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use Luracast\Restler\RestException;
use PFUser;
use Tuleap\AgileDashboard\Milestone\Request\FilteringQueryParser;
use Tuleap\AgileDashboard\Milestone\Request\MalformedQueryParameterException;
use Tuleap\AgileDashboard\Milestone\Request\TopMilestoneRequest;
use Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation;
use Tuleap\REST\Header;

/**
 * Wrapper for milestone related REST methods
 */
class ProjectMilestonesResource
{
    public const MAX_LIMIT = 50;

    /**
     * @var FilteringQueryParser
     */
    private $query_parser;
    /**
     * @var \Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var MilestoneRepresentationBuilder
     */
    private $representation_builder;

    public function __construct(
        FilteringQueryParser $query_parser,
        \Planning_MilestoneFactory $milestone_factory,
        MilestoneRepresentationBuilder $representation_builder
    ) {
        $this->query_parser           = $query_parser;
        $this->milestone_factory      = $milestone_factory;
        $this->representation_builder = $representation_builder;
    }

    /**
     * Get the top milestones of a given project
     * @return MilestoneRepresentation[]
     * @throws RestException
     */
    public function get(
        PFUser $user,
        \Project $project,
        string $representation_type,
        string $query,
        int $limit,
        int $offset,
        string $order
    ): array {
        try {
            $filtering_query = $this->query_parser->parse($query);
        } catch (MalformedQueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
        $request = new TopMilestoneRequest($user, $project, $limit, $offset, $order, $filtering_query);

        try {
            $milestones = $this->milestone_factory->getPaginatedTopMilestones($request);
            $this->sendPaginationHeaders($limit, $offset, $milestones->getTotalSize());
        } catch (\Planning_NoPlanningsException $e) {
            $this->sendPaginationHeaders($limit, $offset, 0);
            return [];
        }

        $milestone_representations = $this->representation_builder->buildRepresentationsFromCollection(
            $milestones,
            $user,
            $representation_type
        );
        return $milestone_representations->getMilestonesRepresentations();
    }

    private function sendPaginationHeaders(int $limit, int $offset, int $size): void
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }
}
