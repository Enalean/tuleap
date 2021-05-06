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
use Tuleap\REST\I18NRestException;

final class SubtasksRetriever
{
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var ICacheTaskRepresentationBuilderForTracker
     */
    private $representation_builder_cache;
    /**
     * @var IDetectIfArtifactIsOutOfDate
     */
    private $out_of_date_detector;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        \UserManager $user_manager,
        ICacheTaskRepresentationBuilderForTracker $representation_builder_cache,
        IDetectIfArtifactIsOutOfDate $out_of_date_detector
    ) {
        $this->artifact_factory             = $artifact_factory;
        $this->user_manager                 = $user_manager;
        $this->representation_builder_cache = $representation_builder_cache;
        $this->out_of_date_detector         = $out_of_date_detector;
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

        $representations = [];
        foreach ($sliced_subtasks as $artifact) {
            $representation_builder = $this->representation_builder_cache
                ->getRepresentationBuilderForTracker($artifact->getTracker(), $user);

            if (! $representation_builder) {
                continue;
            }

            if ($this->out_of_date_detector->isArtifactOutOfDate($artifact, $now, $user)) {
                continue;
            }

            $representations[] = $representation_builder->buildRepresentation($artifact, $user);
        }

        return new PaginatedCollectionOfTaskRepresentations($representations, $total_size);
    }
}
