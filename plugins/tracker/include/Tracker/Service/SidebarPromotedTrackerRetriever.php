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
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Layout\SidebarPromotedItemPresenter;
use Tuleap\Tracker\RetrievePromotedTrackers;

#[ConfigKeyCategory('Tracker')]
final class SidebarPromotedTrackerRetriever
{
    #[FeatureFlagConfigKey(<<<'EOF'
    Should we display promoted trackers in project sidebar?
    Comma separated list of project ids like 123,234.
    0 => No projects have the feature (default)
    1 => Every projects have the feature
    123,234 => Only projects with id 123 or 234 have the feature
    EOF
    )]
    #[ConfigKeyString('0')]
    public const FEATURE_FLAG = 'display_promoted_trackers_in_sidebar';

    public function __construct(private readonly RetrievePromotedTrackers $retriever)
    {
    }

    /**
     * @return list<SidebarPromotedItemPresenter>
     */
    public function getPromotedItemPresenters(\PFUser $user, \Project $project, ?string $active_promoted_item_id): array
    {
        if (! $this->isProjectAllowedToPromoteTrackersInSidebar($project)) {
            return [];
        }

        return array_values(
            array_map(
                static fn(Tracker $tracker) => new SidebarPromotedItemPresenter(
                    $tracker->getUri(),
                    $tracker->getName(),
                    $tracker->getDescription(),
                    $tracker->getPromotedTrackerId() === $active_promoted_item_id,
                ),
                $this->retriever->getTrackers($user, $project),
            ),
        );
    }

    public function isProjectAllowedToPromoteTrackersInSidebar(\Project $project): bool
    {
        $list_of_project_ids_with_promoted_trackers_in_sidebar = \ForgeConfig::getFeatureFlagArrayOfInt(self::FEATURE_FLAG);

        if (! $list_of_project_ids_with_promoted_trackers_in_sidebar) {
            return false;
        }

        if ($list_of_project_ids_with_promoted_trackers_in_sidebar === [1]) {
            return true;
        }

        return in_array((int) $project->getID(), $list_of_project_ids_with_promoted_trackers_in_sidebar, true);
    }
}
