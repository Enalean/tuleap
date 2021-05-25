<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestPlan\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use Tuleap\AgileDashboard\REST\v1\ContentForMiletoneProvider;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use URLVerification;
use UserManager;

final class MilestoneResource extends AuthenticatedResource
{
    private const MAX_LIMIT = 30;

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var ContentForMiletoneProvider
     */
    private $content_for_milestone_provider;

    public function __construct()
    {
        $this->milestone_factory              = Planning_MilestoneFactory::build();
        $this->content_for_milestone_provider = ContentForMiletoneProvider::build($this->milestone_factory);
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id ID of the backlog item
     */
    public function options(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get content
     *
     * Get the backlog items of a given milestone
     *
     * @url    GET {id}/testplan
     * @access hybrid
     *
     * @param int $id     Id of the milestone
     * @param int $limit  Number of elements displayed per page {@min 0}{@max 30}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return array {@type \Tuleap\TestPlan\REST\v1\BacklogItemRepresentation}
     *
     * @throws RestException 404
     */
    public function getTestPlan(int $id, int $limit = 10, int $offset = 0): array
    {
        $this->checkAccess();

        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        $backlog_items = $this->content_for_milestone_provider->getContent(
            $milestone,
            $user,
            $limit,
            $offset
        );

        $backlog_items_representations = [];
        foreach ($backlog_items as $backlog_item) {
            $representation                  = new BacklogItemRepresentation($backlog_item, $user);
            $backlog_items_representations[] = $representation;
        }

        Header::allowOptionsGet();
        Header::sendPaginationHeaders($limit, $offset, $backlog_items->getTotalAvaialableSize(), self::MAX_LIMIT);

        return $backlog_items_representations;
    }

    private function getMilestoneById(PFUser $user, int $id): \Planning_ArtifactMilestone
    {
        try {
            $milestone = $this->milestone_factory->getValidatedBareMilestoneByArtifactId($user, $id);
        } catch (\MilestonePermissionDeniedException $e) {
            throw new RestException(404);
        }

        if (! $milestone instanceof Planning_ArtifactMilestone) {
            throw new RestException(404);
        }

        ProjectAuthorization::userCanAccessProject($user, $milestone->getProject(), new URLVerification());

        return $milestone;
    }

    private function getCurrentUser(): PFUser
    {
        return UserManager::instance()->getCurrentUser();
    }
}
