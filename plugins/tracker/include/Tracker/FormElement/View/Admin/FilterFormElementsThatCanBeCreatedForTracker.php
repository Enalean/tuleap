<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\View\Admin;

use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Tracker;

class FilterFormElementsThatCanBeCreatedForTracker implements Dispatchable
{
    public const NAME = 'filterFormElementsThatCanBeCreatedForTracker';

    /**
     * @var array
     */
    private $klasses;
    /**
     * @var Tracker
     */
    private $tracker;

    public function __construct(array $klasses, Tracker $tracker)
    {
        $this->klasses = $klasses;
        $this->tracker = $tracker;
    }

    /**
     * @return array
     */
    public function getKlasses()
    {
        return $this->klasses;
    }

    /**
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    public function removeByType($type)
    {
        if (isset($this->klasses[$type])) {
            unset($this->klasses[$type]);
        }
    }
}
