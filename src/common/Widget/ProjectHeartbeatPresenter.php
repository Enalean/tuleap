<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Widget;

use PFUser;
use Project;

class ProjectHeartbeatPresenter
{
    public $project_id;
    public string $empty_state_no_perms;
    public string $empty_state_no_activity;
    public $error_message;
    public $locale;
    public $today;
    public $yesterday;
    public $recently;
    public $date_format;
    public $date_time_format;

    public function __construct(Project $project, PFUser $user)
    {
        $this->project_id = $project->getID();
        $this->locale     = $user->getShortLocale();

        $this->date_time_format = $GLOBALS['Language']->getText('system', 'datefmt');
        $this->date_format      = $GLOBALS['Language']->getText('system', 'datefmt_short');

        $this->error_message = _('Unable to fetch the latest activities of the project');
        $this->today         = _('Today');
        $this->yesterday     = _('Yesterday');
        $this->recently      = _('Recently');

        $this->empty_state_no_perms = _('There are no items you can see');

        $this->empty_state_no_activity = _("There isn't any activity yet");
    }
}
