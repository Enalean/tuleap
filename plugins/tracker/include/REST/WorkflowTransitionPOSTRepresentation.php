<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use Transition;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
class WorkflowTransitionPOSTRepresentation
{
    public const ROUTE = 'tracker_workflow_transitions';

    /**
     * @var int ID of the transition {@type int} {@required true}
     */
    public $id;

    /**
     * @var string URI of the transition {@type string} {@required true}
     */
    public $uri;

    public function __construct(Transition $transition)
    {
        $this->id  = JsonCast::toInt($transition->getId());
        $this->uri = self::ROUTE . '/' . $transition->getId();
    }
}
