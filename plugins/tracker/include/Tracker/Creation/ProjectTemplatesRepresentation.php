<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation;

use Project;

class ProjectTemplatesRepresentation implements \JsonSerializable
{
    /**
     * @var string
     */
    public $project_name;
    /**
     * @var array
     */
    public $tracker_list;

    public function __construct(Project $project, array $tracker_list)
    {
        $this->project_name = $project->getPublicName();
        $this->tracker_list = $tracker_list;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'project_name' => $this->project_name,
            'tracker_list' => $this->tracker_list
        ];
    }
}
