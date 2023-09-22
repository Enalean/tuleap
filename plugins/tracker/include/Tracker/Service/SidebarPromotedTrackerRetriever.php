<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Service;

use Tracker;
use Tuleap\Layout\PromotedItemQuickLinkPresenter;
use Tuleap\Layout\SidebarPromotedItemPresenter;
use Tuleap\Tracker\RetrievePromotedTrackers;

final class SidebarPromotedTrackerRetriever
{
    public function __construct(
        private readonly RetrievePromotedTrackers $retriever,
        private readonly PromotedTrackerConfigurationChecker $configuration_checker,
    ) {
    }

    /**
     * @return list<SidebarPromotedItemPresenter>
     */
    public function getPromotedItemPresenters(\PFUser $user, \Project $project, ?string $active_promoted_item_id): array
    {
        if (! $this->configuration_checker->isProjectAllowedToPromoteTrackersInSidebar($project)) {
            return [];
        }

        return array_values(
            array_map(
                static fn(Tracker $tracker) => new SidebarPromotedItemPresenter(
                    $tracker->getUri(),
                    $tracker->getName(),
                    $tracker->getDescription(),
                    $tracker->getPromotedTrackerId() === $active_promoted_item_id,
                    new PromotedItemQuickLinkPresenter(
                        $tracker->getSubmitUrl(),
                        $tracker->getSubmitLabel(),
                    ),
                ),
                $this->retriever->getTrackers($user, $project),
            ),
        );
    }
}
