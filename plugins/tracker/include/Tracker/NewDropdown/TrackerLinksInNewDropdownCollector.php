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

use Tuleap\layout\NewDropdown\NewDropdownLinkPresenter;
use Tuleap\layout\NewDropdown\NewDropdownProjectLinksCollector;

class TrackerLinksInNewDropdownCollector
{
    /**
     * @var TrackerInNewDropdownRetriever
     */
    private $retriever;

    public function __construct(TrackerInNewDropdownRetriever $retriever)
    {
        $this->retriever = $retriever;
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

            $collector->addCurrentProjectLink(
                new \Tuleap\layout\NewDropdown\NewDropdownLinkPresenter(
                    $tracker->getSubmitUrl(),
                    sprintf(
                        dgettext('tuleap-tracker', 'New %s'),
                        $tracker->getItemName()
                    ),
                    'fa-plus'
                )
            );
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

        $submit_url = $tracker->getSubmitUrl();
        foreach ($current_context_links as $link) {
            if ($link->url === $submit_url) {
                return true;
            }
        }

        return false;
    }
}
