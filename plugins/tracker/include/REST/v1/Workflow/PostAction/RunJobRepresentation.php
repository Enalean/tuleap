<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction;

use Tuleap\REST\JsonCast;

class RunJobRepresentation extends PostActionRepresentation
{
    /**
     * @var string
     */
    public $job_url;

    private function __construct($id, $job_url)
    {
        $this->id      = $id;
        $this->type    = 'run_job';
        $this->job_url = $job_url;
    }

    /**
     * @param int $id Action identifier (unique among actions with same type)
     * @param string $job_url
     * @return RunJobRepresentation
     */
    public static function build($id, $job_url)
    {
        return new self(
            JsonCast::toInt($id),
            $job_url
        );
    }
}
