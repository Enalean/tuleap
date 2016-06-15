<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Artifact;

use Tracker_Artifact_ChangesetValue_Float;
use PFUser;

class ChangesetValueComputed extends Tracker_Artifact_ChangesetValue_Float
{

    /**
     * Returns the value of this changeset value (integer)
     *
     * @return int The value of this artifact changeset value
     */
    public function getValue()
    {
         return $this->getNumeric();
    }

    public function getText()
    {
        return $this->getValue();
    }

    public function diff($changeset_value, $format = 'html', PFUser $user = null)
    {
        $previous_numeric = $changeset_value->getValue();
        $next_numeric     = $this->getValue();

        if ($previous_numeric !== $next_numeric) {
            if ($previous_numeric === null) {
                return $GLOBALS['Language']->getText('plugin_tracker_artifact', 'changed_from')." ".
                $GLOBALS['Language']->getText('plugin_tracker', 'autocompute_field')." ".
                $GLOBALS['Language']->getText('plugin_tracker_artifact', 'to') ." ".
                $next_numeric;
            } elseif (is_null($next_numeric)) {
                return $GLOBALS['Language']->getText('plugin_tracker_artifact', 'changed_from') ." ".
                $previous_numeric. " ".
                $GLOBALS['Language']->getText('plugin_tracker_artifact', 'to') ." ".
                $GLOBALS['Language']->getText('plugin_tracker', 'autocompute_field');
            } else {
                return $GLOBALS['Language']->getText('plugin_tracker_artifact', 'changed_from').
                    ' ' . $previous_numeric . ' ' .
                    $GLOBALS['Language']->getText('plugin_tracker_artifact', 'to') . ' ' . $next_numeric;
            }
        }

        return false;
    }
}
