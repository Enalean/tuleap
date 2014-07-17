<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Testing\REST\v1;

use Tuleap\REST\JsonCast;


class ExecutionRepresentation {

    const ROUTE = 'executions';

    const FIELD_RESULTS      = 'results';
    const FIELD_ASSIGNED_TO  = 'assigned_to';
    const FIELD_STATUS       = 'status';

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
     * @var UserRepresentation
     */
    public $assigned_to;

    /**
     * @var Tuleap\Testing\REST\v1\TestDefinitionRepresentation
     */
    public $test_definition;

    public function build(
        $artifact_id,
        $status,
        $results,
        $last_update_date,
        $assigned_to,
        $test_definition
    ) {

        $this->id                 = JsonCast::toInt($artifact_id);
        $this->uri                = self::ROUTE . '/' . $this->id;
        $this->results            = $results;
        $this->status             = $status;
        $this->last_update_date   = JsonCast::toDate($last_update_date);
        $this->test_definition    = $test_definition;
        $this->assigned_to        = $assigned_to;
    }
}
