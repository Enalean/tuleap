<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

/**
 * Manage values in changeset for numeric fields
 */
abstract class Tracker_Artifact_ChangesetValue_Numeric extends Tracker_Artifact_ChangesetValue
{

    /**
     * @var mixed (int or float)
     */
    protected $numeric;

    public function __construct($id, Tracker_Artifact_Changeset $changeset, $field, $has_changed, $numeric)
    {
        parent::__construct($id, $changeset, $field, $has_changed);
        $this->numeric = $numeric;
    }

    /**
     * Get the numeric value
     *
     * @return mixed (int or float) the Numeric
     */
    public function getNumeric()
    {
        return $this->numeric;
    }

    /**
     * Get the string value
     *
     * @return string|null
     */
    public function getValue()
    {
        return $this->numeric;
    }

    /**
     * Get the diff between this numeric value and the one passed in param
     *
     * @return string|false The difference between another $changeset_value, false if no differences
     */
    public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
    {
        $previous_numeric = $changeset_value->getValue();
        $next_numeric     = $this->getValue();
        if ($previous_numeric !== $next_numeric) {
            if ($previous_numeric === null) {
                return sprintf(dgettext('tuleap-tracker', 'set to %s'), $this->format($next_numeric, $format));
            } elseif ($next_numeric === null) {
                return dgettext('tuleap-tracker', 'cleared');
            } else {
                return sprintf(
                    dgettext('tuleap-tracker', 'changed from %s to %s'),
                    $this->format($previous_numeric, $format),
                    $this->format($next_numeric, $format)
                );
            }
        }
        return false;
    }

     /**
     * Returns the "set to" for field added later
     *
     * @return string The sentence to add in changeset
     */
    public function nodiff($format = 'html')
    {
        if ($this->getNumeric() != 0) {
            return $GLOBALS['Language']->getText('plugin_tracker_artifact', 'set_to') . ' ' . $this->format($this->getValue(), $format);
        }
        return '';
    }

    private function format($value, $format)
    {
        if ($format === 'text') {
            return $value;
        }
        return Codendi_HTMLPurifier::instance()->purify($value);
    }
}
