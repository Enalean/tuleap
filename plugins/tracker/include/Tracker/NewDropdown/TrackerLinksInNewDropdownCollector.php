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


namespace Tuleap\Tracker\NewDropdown;

use Tuleap\Layout\NewDropdown\NewDropdownLinkPresenter;
use Tuleap\Layout\NewDropdown\NewDropdownProjectLinksCollector;
use Tuleap\Tracker\RetrievePromotedTrackers;

final class TrackerLinksInNewDropdownCollector
{
    public function __construct(
        private readonly RetrievePromotedTrackers $retriever,
        private readonly TrackerNewDropdownLinkPresenterBuilder $link_presenter_builder,
    ) {
    }

    public function collect(NewDropdownProjectLinksCollector $collector): void
    {
        $trackers_in_dropdown = $this->retriever->getTrackers(
            $collector->getCurrentUser(),
            $collector->getProject()
        );

        $current_context_section = $collector->getCurrentContextSection();
        $current_context_links   = $current_context_section ? $current_context_section->links : [];

        foreach ($trackers_in_dropdown as $tracker) {
            if ($this->isTrackerInCurrentContextSection($tracker, $current_context_links)) {
                continue;
            }

            $collector->addCurrentProjectLink($this->link_presenter_builder->build($tracker));
        }
    }

    /**
     * @param NewDropdownLinkPresenter[] $current_context_links
     */
    private function isTrackerInCurrentContextSection(\Tracker $tracker, array $current_context_links): bool
    {
        if (empty($current_context_links)) {
            return false;
        }

        $id = $tracker->getId();
        foreach ($current_context_links as $link) {
            $data_attributes = array_column($link->data_attributes, 'value', 'name');
            $link_tracker_id = (int) ($data_attributes['tracker-id'] ?? 0);
            if ($link_tracker_id === $id) {
                return true;
            }
        }

        return false;
    }
}
