<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\AgileDashboard\Milestone\Pane\Details;

use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Tracker\Tracker;
use function Psl\Json\encode as json_encode;

final readonly class DetailsPresenter
{
    public string $backlog_item_type;
    public string $submilestones_ids;
    public string $csrf_token_data;

    /**
     * @param list<Tracker> $trackers
     * @param list<int> $submilestone_ids_array
     */
    public function __construct(
        public int $milestone_id,
        public DetailsChartPresenter $chart_presenter,
        public string $solve_inconsistencies_url,
        array $trackers,
        array $submilestone_ids_array,
        CSRFSynchronizerTokenInterface $csrf_token,
    ) {
        $this->backlog_item_type = $this->getTrackerNames($trackers);
        $this->csrf_token_data   = json_encode(CSRFSynchronizerTokenPresenter::fromToken($csrf_token));
        $this->submilestones_ids = implode(',', $submilestone_ids_array);
    }

    /**
     * @param Tracker[] $trackers
     */
    private function getTrackerNames(array $trackers): string
    {
        $tracker_names = [];

        foreach ($trackers as $tracker) {
            $tracker_names[] = $tracker->getName();
        }

        return implode(', ', $tracker_names);
    }

    public function getTemplateName(): string
    {
        return 'pane-details';
    }
}
