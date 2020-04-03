<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Timetracking\ArtifactView;

use Codendi_Request;
use CSRFSynchronizerToken;
use PFUser;
use timetrackingPlugin;
use Tracker_Artifact;
use Tuleap\Timetracking\Admin\TimetrackingEnabler;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\Time\DateFormatter;
use Tuleap\Timetracking\Time\TimePresenterBuilder;
use Tuleap\Timetracking\Time\TimeRetriever;

class ArtifactViewBuilder
{
    /**
     * @var timetrackingPlugin
     */
    private $plugin;

    /**
     * @var TimetrackingEnabler
     */
    private $timetracking_enabler;

    /**
     * @var PermissionsRetriever
     */
    private $permissions_retriever;

    /**
     * @var TimeRetriever
     */
    private $time_retriever;

    /**
     * @var TimePresenterBuilder
     */
    private $time_presenter_builder;

    /**
     * @var DateFormatter
     */
    private $date_formatter;

    public function __construct(
        timetrackingPlugin $plugin,
        TimetrackingEnabler $timetracking_enabler,
        PermissionsRetriever $permissions_retriever,
        TimeRetriever $time_retriever,
        TimePresenterBuilder $time_presenter_builder,
        DateFormatter $date_formatter
    ) {
        $this->plugin                 = $plugin;
        $this->timetracking_enabler   = $timetracking_enabler;
        $this->permissions_retriever  = $permissions_retriever;
        $this->time_retriever         = $time_retriever;
        $this->time_presenter_builder = $time_presenter_builder;
        $this->date_formatter         = $date_formatter;
    }

    /**
     * @return ArtifactView | null
     */
    public function build(PFUser $user, Codendi_Request $request, Tracker_Artifact $artifact)
    {
        $tracker = $artifact->getTracker();
        $project = $tracker->getProject();

        if (! $this->plugin->isAllowed($project->getID())) {
            return null;
        }

        if (! $this->timetracking_enabler->isTimetrackingEnabledForTracker($tracker)) {
            return null;
        }

        $user_can_add_time = $this->permissions_retriever->userCanAddTimeInTracker($user, $tracker);

        if (
            ! $user_can_add_time &&
            ! $this->permissions_retriever->userCanSeeAggregatedTimesInTracker($user, $tracker)
        ) {
            return null;
        }

        $csrf                 = new CSRFSynchronizerToken($artifact->getUri());
        $times_for_user       = $this->time_retriever->getTimesForUser($user, $artifact);
        $time_presenters      = $this->getTimePresenters($user, $times_for_user);
        $formatted_total_time = $this->getFormattedTotalTime($times_for_user);

        $presenter = new ArtifactViewPresenter(
            $artifact,
            $csrf,
            $time_presenters,
            $formatted_total_time,
            $user_can_add_time
        );

        return new ArtifactView($artifact, $request, $user, $presenter);
    }

    /**
     * @return array
     */
    private function getTimePresenters(PFUser $user, array $times_for_user)
    {
        $presenters = array();

        foreach ($times_for_user as $time) {
            $presenters[] = $this->time_presenter_builder->buildPresenter($time, $user);
        }

        return $presenters;
    }

    /**
     * @return string
     */
    private function getFormattedTotalTime(array $times_for_user)
    {
        $total_minutes = 0;
        foreach ($times_for_user as $time) {
            $total_minutes += $time->getMinutes();
        }

        return $this->date_formatter->formatMinutes($total_minutes);
    }
}
