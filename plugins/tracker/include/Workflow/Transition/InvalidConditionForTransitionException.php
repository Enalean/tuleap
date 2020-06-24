<?php
/**
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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

class Tracker_Workflow_Transition_InvalidConditionForTransitionException extends Tracker_Exception
{
    public function __construct(Transition $transition)
    {
        $field_value_to = $transition->getFieldValueTo();
        if ($field_value_to !== null) {
            $message = sprintf(dgettext('tuleap-tracker', 'The transition to the value "%1$s" is not valid.'), $field_value_to->getLabel());
        } else {
            $message = dgettext('tuleap-tracker', 'The transition to the value "None" is not valid.');
        }

        parent::__construct($message);
    }
}
