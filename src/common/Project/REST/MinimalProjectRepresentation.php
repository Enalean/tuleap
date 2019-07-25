<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Project\REST;

use Project;
use Tuleap\Project\ProjectStatusMapper;
use Tuleap\REST\JsonCast;

class MinimalProjectRepresentation
{
    public const ROUTE = 'projects';

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $uri;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $shortname;

    /**
     * @var string
     */
    public $status;

    /**
     * @var string {@choice unrestricted,public,private,private-wo-restr}
     */
    public $access;

    public function buildMinimal(Project $project)
    {
        $this->id        = JsonCast::toInt($project->getID());
        $this->uri       = self::ROUTE . '/' . $this->id;
        $this->label     = $project->getUnconvertedPublicName();
        $this->shortname = $project->getUnixName();
        $this->status    = ProjectStatusMapper::getProjectStatusLabelFromStatusFlag(
            $project->getStatus()
        );
        $this->access    = $project->getAccess();
    }
}
