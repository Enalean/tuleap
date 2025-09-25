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

namespace Tuleap\Tracker\Events;

use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\FormElement\TrackerFormElement;

class AllowedFieldTypeChangesRetriever implements Dispatchable
{
    public const NAME = 'semanticAllowedFieldTypeRetriever';

    /**
     * @var \Tuleap\Tracker\FormElement\TrackerFormElement
     */
    private $field;

    /**
     * @var array
     */
    private $allowed_types = [];


    /**
     * @return \Tuleap\Tracker\FormElement\TrackerFormElement
     */
    public function getField()
    {
        return $this->field;
    }

    public function setField(TrackerFormElement $field)
    {
        $this->field = $field;
    }

    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return $this->allowed_types;
    }

    /**
     * @param array $allowed_types
     */
    public function setAllowedTypes($allowed_types)
    {
        $this->allowed_types = $allowed_types;
    }
}
