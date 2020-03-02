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
     * @var string
     */
    public $existing_trackers;

    public function __construct(
        array $project_templates,
        array $existing_trackers,
        Project $current_project,
        \CSRFSynchronizerToken $csrf
    ) {
        $this->project_templates = json_encode($project_templates, JSON_THROW_ON_ERROR);
        $this->existing_trackers = json_encode($existing_trackers, JSON_THROW_ON_ERROR);
        $this->project_unix_name = $current_project->getUnixNameLowerCase();
        $this->csrf_token        = json_encode([
            'name' => $csrf->getTokenName(),
            'value' => $csrf->getToken()
        ]);
    }
}
