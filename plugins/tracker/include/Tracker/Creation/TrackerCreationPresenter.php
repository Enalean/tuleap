<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Tracker\Creation;

use ForgeConfig;
use Project;

class TrackerCreationPresenter
{
    /**
     * @var string
     */
    public $project_templates;

    /**
     * @var string
     */
    public $project_unix_name;

    /**
     * @var string
     */
    public $csrf_token;
    /**
     * @var int
     */
    public $project_id;

    /**
     * @var string
     */
    public $existing_trackers;

    /**
     * @var string
     */
    public $trackers_from_other_projects;
    /**
     * @var string
     */
    public $company_name;

    /**
     * @var string
     */
    public $tracker_colors;
    /**
     * @var false|string
     */
    public $default_templates;

    public function __construct(
        array $default_templates,
        array $project_templates,
        array $existing_trackers,
        array $trackers_from_other_projects,
        array $tracker_colors,
        Project $current_project,
        \CSRFSynchronizerToken $csrf
    ) {
        $this->default_templates            = json_encode($default_templates, JSON_THROW_ON_ERROR);
        $this->project_templates            = json_encode($project_templates, JSON_THROW_ON_ERROR);
        $this->existing_trackers            = json_encode($existing_trackers, JSON_THROW_ON_ERROR);
        $this->trackers_from_other_projects = json_encode($trackers_from_other_projects, JSON_THROW_ON_ERROR);
        $this->tracker_colors               = json_encode($tracker_colors, JSON_THROW_ON_ERROR);
        $this->project_unix_name            = $current_project->getUnixNameLowerCase();
        $this->project_id                   = $current_project->getID();
        $this->csrf_token                   = json_encode(
            [
                'name'  => $csrf->getTokenName(),
                'value' => $csrf->getToken()
            ]
        );
        $this->company_name                 = (string) ForgeConfig::get('sys_org_name');
    }
}
