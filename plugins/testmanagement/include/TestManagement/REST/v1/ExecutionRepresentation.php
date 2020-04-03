<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Tuleap\REST\JsonCast;
use Tuleap\User\REST\UserRepresentation;

class ExecutionRepresentation
{
    public const ROUTE = 'testmanagement_executions';

    public const FIELD_RESULTS        = 'results';
    public const FIELD_ASSIGNED_TO    = 'assigned_to';
    public const FIELD_STATUS         = 'status';
    public const FIELD_ARTIFACT_LINKS = "artifact_links";
    public const FIELD_TIME           = 'time';
    public const FIELD_STEPS_RESULTS  = 'steps_results';

    /**
     * @var int ID of the artifact
     */
    public $id;

    /**
     * @var String
     */
    public $uri;

    /**
     * @var String Result of an execution
     */
    public $results;

    /**
     * @var String
     */
    public $status;

    /**
     * @var String
     */
    public $last_update_date;

    /**
     * @var UserRepresentation | null
     */
    public $assigned_to;

    /**
     * @var PreviousResultRepresentation | null
     */
    public $previous_result;

    /**
     * @var DefinitionRepresentation
     */
    public $definition;

    /**
     * @var array {@type BugRepresentation}
     */
    public $linked_bugs;

    /**
     * @var int
     */
    public $time;

    /**
     * @var array {@type StepResultRepresentation}
     */
    public $steps_results;

    public function build(
        int $artifact_id,
        string $status,
        string $results,
        int $last_update_date,
        ?UserRepresentation $assigned_to,
        ?PreviousResultRepresentation $previous_result,
        DefinitionRepresentation $definition,
        array $linked_bug,
        int $time,
        array $steps_results
    ): void {
        $this->id               = JsonCast::toInt($artifact_id);
        $this->uri              = self::ROUTE . '/' . $this->id;
        $this->results          = $results;
        $this->status           = $status;
        $this->last_update_date = JsonCast::toDate($last_update_date);
        $this->definition       = $definition;
        $this->previous_result  = $previous_result;
        $this->assigned_to      = $assigned_to;
        $this->time             = $time;
        $this->linked_bugs      = $linked_bug;
        $this->steps_results    = (array) JsonCast::toObject($steps_results);
    }
}
