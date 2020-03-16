<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015-2017. All Rights Reserved.
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

/**
 * Manage values in changeset for date fields
 */
class Tracker_Artifact_ChangesetValue_Date extends Tracker_Artifact_ChangesetValue
{

    /**
     * @var int
     */
    protected $timestamp;

    public function __construct($id, Tracker_Artifact_Changeset $changeset, $field, $has_changed, $timestamp)
    {
        parent::__construct($id, $changeset, $field, $has_changed);
        $this->timestamp = $timestamp;
    }

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitDate($this);
    }

    /**
     * Get timestamp of this changeset value date
     *
     * @return int the timestamp, or null if date is null (none)
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get human-readable representation of the date
     *
     * @return string the human-readable representation of the date, or '' if date is null(none)
     */
    public function getDate()
    {
        return $this->formatDate($this->timestamp);
    }

    /**
     * Format the timestamp in human readable date
     *
     * @param int $timestamp The date
     *
     * @return string the date in the format Y-m-d with maybe hours and minutes or '' if date is null (none)
     */
    protected function formatDate($timestamp)
    {
        if ($timestamp === null) {
            return '';
        } else {
            return $this->field->formatDateForDisplay($timestamp);
        }
    }

    public function getRESTValue(PFUser $user)
    {
        return $this->getFullRESTValue($user);
    }


    public function getFullRESTValue(PFUser $user)
    {
        $date = null;
        if ($this->getTimestamp()) {
            $date = date('c', $this->getTimestamp());
        }
        return $this->getFullRESTRepresentation($date);
    }

    /**
     * Returns the value of this changeset value (human readable)
     *
     * @return string The value of this artifact changeset value for the web interface, or '' if date is null (none)
     */
    public function getValue()
    {
        return $this->getDate();
    }

    /**
     * Returns diff between current date and date in param
     *
     * @return string|false The difference between another $changeset_value, false if no differneces
     */
    public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
    {
        $next_date = $this->getDate();
        if ($changeset_value->getTimestamp() != 0) {
            $previous_date = $changeset_value->getDate();
            if ($previous_date !== $next_date) {
                if ($next_date === '') {
                    return dgettext('tuleap-tracker', 'cleared');
                } else {
                    return sprintf(
                        dgettext('tuleap-tracker', 'changed from %s to %s'),
                        $previous_date,
                        $next_date
                    );
                }
            }
        } elseif ($next_date !== '') {
            return sprintf(
                dgettext('tuleap-tracker', 'set to %s'),
                $next_date
            );
        }

        return false;
    }

    /**
     * Returns the "set to" date for field added later
     *
     * @return string The sentence to add in changeset
     */
    public function nodiff($format = 'html')
    {
        if ($this->getTimestamp() != 0) {
            $next_date = $this->getDate();
            return $GLOBALS['Language']->getText('plugin_tracker_artifact', 'set_to') . ' ' . $next_date;
        }
    }
}
