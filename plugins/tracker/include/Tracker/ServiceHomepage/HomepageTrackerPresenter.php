<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\ServiceHomepage;

use Tuleap\Option\Option;
use Tuleap\Tracker\Tooltip\TooltipStatsPresenter;
use Tuleap\Tracker\Tooltip\TrackerStats;
use Tuleap\Tracker\Tracker;

final readonly class HomepageTrackerPresenter
{
    public int $tracker_id;
    public string $color;
    public string $uri;
    public string $label;
    public string $description;
    public bool $has_statistics;
    public string $nb_artifacts;
    public ?TooltipStatsPresenter $tooltip;

    /**
     * @param Option<TrackerStats>          $stats
     * @param Option<TooltipStatsPresenter> $tooltip_presenter
     */
    public function __construct(Tracker $tracker, Option $stats, Option $tooltip_presenter)
    {
        $this->tracker_id     = $tracker->getId();
        $this->color          = $tracker->getColor()->value;
        $this->label          = $tracker->getName();
        $this->description    = $tracker->getDescription();
        $this->uri            = $tracker->getUri();
        $this->has_statistics = $stats->isValue();
        $this->nb_artifacts   = $this->getNbArtifactsString($stats);
        $this->tooltip        = $tooltip_presenter->unwrapOr(null);
    }

    /**
     * @param Option<TrackerStats> $tracker_stats
     */
    private function getNbArtifactsString(Option $tracker_stats): string
    {
        return $tracker_stats->mapOr(function (TrackerStats $stats) {
            $open_artifacts = $stats->getNbOpenArtifacts();
            if ($open_artifacts > 0) {
                return sprintf(
                    dngettext('tuleap-tracker', '%1$d open / %2$d total', '%1$d open / %2$d total', $open_artifacts),
                    $open_artifacts,
                    $stats->getNbTotalArtifacts()
                );
            }
            return sprintf(dgettext('tuleap-tracker', '%d total'), $stats->getNbTotalArtifacts());
        }, '');
    }
}
