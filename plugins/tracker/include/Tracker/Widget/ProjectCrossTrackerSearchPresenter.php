<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Widget;

use Tuleap\Tracker\CrossTracker\CrossTrackerPresenter;

class ProjectCrossTrackerSearchPresenter
{
    /**
     * @var CrossTrackerPresenter
     */
    public $cross_tracker_presenter;
    public $too_many_trackers_selected_error;
    public $could_not_fetch_list_of_trackers_error;
    public $could_not_fetch_list_of_projects_error;
    public $project_label;
    public $tracker_label;
    public $add_button_label;
    public $please_choose_label;

    public function __construct(CrossTrackerPresenter $cross_tracker_presenter)
    {
        $this->cross_tracker_presenter = $cross_tracker_presenter;

        $this->too_many_trackers_selected_error       = dgettext(
            'tuleap-tracker',
            'Tracker selection is limited to 10 trackers'
        );
        $this->could_not_fetch_list_of_trackers_error = dgettext(
            'tuleap-tracker',
            'Error while fetching the list of trackers of this project'
        );
        $this->could_not_fetch_list_of_projects_error = dgettext(
            'tuleap-tracker',
            'Error while fetching the list of projects you are member of'
        );
        $this->project_label                          = dgettext('tuleap-tracker', 'Project');
        $this->tracker_label                          = dgettext('tuleap-tracker', 'Tracker');
        $this->add_button_label                       = dgettext('tuleap-tracker', 'Add');
        $this->please_choose_label                    = dgettext('tuleap-tracker', 'Please choose...');
    }
}
