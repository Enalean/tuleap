<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\Event;

use Transition;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Workflow\Update\PostAction;

class GetWorkflowExternalPostActionsValuesForUpdate implements Dispatchable
{
    public const NAME = 'getWorkflowExternalPostActionsValuesForUpdate';

    /**
     * @var array
     */
    private $external_values = [];

    /**
     * @var Transition
     */
    private $transition;

    public function __construct(Transition $transition)
    {
        $this->transition = $transition;
    }

    /**
     * @return array
     */
    public function getExternalValues(): array
    {
        return $this->external_values;
    }

    public function addExternalValue(PostAction $external_value): void
    {
        $this->external_values[] = $external_value;
    }

    public function getTransition(): Transition
    {
        return $this->transition;
    }
}
