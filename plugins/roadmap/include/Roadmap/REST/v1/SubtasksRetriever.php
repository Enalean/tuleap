<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use Luracast\Restler\RestException;
use Psr\Log\LoggerInterface;
use Tuleap\REST\I18NRestException;

final class SubtasksRetriever
{
    private \Tracker_ArtifactFactory $artifact_factory;
    private \UserManager $user_manager;
    private ICacheTaskRepresentationBuilderForTracker $representation_builder_cache;
    private IDetectIfArtifactIsOutOfDate $out_of_date_detector;
    private LoggerInterface $logger;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        \UserManager $user_manager,
        ICacheTaskRepresentationBuilderForTracker $representation_builder_cache,
        IDetectIfArtifactIsOutOfDate $out_of_date_detector,
        LoggerInterface $logger,
    ) {
        $this->artifact_factory             = $artifact_factory;
        $this->user_manager                 = $user_manager;
        $this->representation_builder_cache = $representation_builder_cache;
        $this->out_of_date_detector         = $out_of_date_detector;
        $this->logger                       = $logger;
    }

    /**
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getTasks(int $id, int $limit, int $offset): PaginatedCollectionOfTaskRepresentations
    {
        $user   = $this->user_manager->getCurrentUser();
        $parent = $this->artifact_factory->getArtifactByIdUserCanView($user, $id);
        if (! $parent) {
            throw new I18NRestException(404, \dgettext('tuleap-roadmap', 'The task cannot be found.'));
        }

        $subtasks        = $parent->getChildrenForUser($user);
        $total_size      = count($subtasks);
        $sliced_subtasks = array_slice($subtasks, $offset, $limit);

        $now = new \DateTimeImmutable();

        $trackers_with_unreadable_status_collection = new TrackersWithUnreadableStatusCollection($this->logger);

        $representations = [];
        foreach ($sliced_subtasks as $artifact) {
            $representation_builder = $this->representation_builder_cache
                ->getRepresentationBuilderForTracker($artifact->getTracker(), $user);

            if (! $representation_builder) {
                continue;
            }

            if (
                $this->out_of_date_detector->isArtifactOutOfDate(
                    $artifact,
                    $now,
                    $user,
                    $trackers_with_unreadable_status_collection
                )
            ) {
                continue;
            }

            $representations[] = $representation_builder->buildRepresentation($artifact, $user);
        }

        $trackers_with_unreadable_status_collection->informLoggerIfWeHaveTrackersWithUnreadableStatus();

        return new PaginatedCollectionOfTaskRepresentations($representations, $total_size);
    }
}
