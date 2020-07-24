<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\Semantic;

use Tracker_FormElement_Field_List;
use Tuleap\Event\Dispatchable;

class SemanticStatusGetDisabledValues implements Dispatchable
{
    public const NAME = 'semanticStatusGetDisabledValues';

    /**
     * @var Tracker_FormElement_Field_List
     */
    private $field;

    /**
     * @var array
     */
    private $disabled_values = [];

    public function __construct(Tracker_FormElement_Field_List $field)
    {
        $this->field = $field;
    }

    /**
     * @return Tracker_FormElement_Field_List
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return array
     */
    public function getDisabledValues()
    {
        return $this->disabled_values;
    }

    public function setDisabledValues(array $disabled_values)
    {
        $this->disabled_values = $disabled_values;
    }
}
