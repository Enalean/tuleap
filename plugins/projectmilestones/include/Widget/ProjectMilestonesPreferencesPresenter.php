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

declare(strict_types=1);

namespace Tuleap\ProjectMilestones\Widget;

use Project;
use CSRFSynchronizerToken;

class ProjectMilestonesPreferencesPresenter
{
    /**
     * @var int
     */
    public $widget_id;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var string
     */
    public $data;
    /**
     * @var string
     */
    public $placeholder;

    public function __construct(int $widget_id, ?Project $project, CSRFSynchronizerToken $csrf_token)
    {
        $this->widget_id   = $widget_id;
        $this->csrf_token  = $csrf_token;
        $this->placeholder = dgettext('tuleap-projectmilestones', 'Project name');
        $data = [];
        if ($project) {
            $data = array(["id" => (int) $project->getID(), "text" => $project->getPublicName(), "selected" => true, "disabled" => false]);
        }
        $this->data = (string) json_encode($data, JSON_THROW_ON_ERROR);
    }
}
