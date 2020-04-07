<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

use Codendi_HTMLPurifier;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Float;
use Tracker_Artifact_ChangesetValueVisitor;

class ChangesetValueComputed extends Tracker_Artifact_ChangesetValue_Float
{
    /**
     * @var bool
     */
    private $is_manual_value;

    public function __construct(
        $id,
        Tracker_Artifact_Changeset $changeset,
        $field,
        $has_changed,
        $numeric,
        $is_manual_value
    ) {
        parent::__construct($id, $changeset, $field, $has_changed, $numeric);

        $this->is_manual_value = $is_manual_value;
    }

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitComputed($this);
    }

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

    /**
     * @param \Tracker_Artifact_ChangesetValue $changeset_value
     * @param string                           $format
     * @param bool                             $ignore_perms
     *
     * @return string|false
     */
    public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
    {
        $previous_numeric = $changeset_value->getValue();
        $next_numeric     = $this->getValue();

        $purifier = Codendi_HTMLPurifier::instance();
        if (
            $changeset_value->isManualValue() === $this->isManualValue()
            && $previous_numeric === $next_numeric
        ) {
            return false;
        }

        if ($this->isManualValue() && $changeset_value->isManualValue()) {
            return sprintf(
                dgettext('tuleap-tracker', 'changed from %s to %s'),
                $purifier->purify($previous_numeric),
                $purifier->purify($next_numeric)
            );
        }

        if ($this->isManualValue()) {
            return sprintf(
                dgettext('tuleap-tracker', 'changed from autocomputed to %s'),
                $purifier->purify($next_numeric)
            );
        }

        return sprintf(
            dgettext('tuleap-tracker', 'changed from %s to autocomputed'),
            $purifier->purify($previous_numeric)
        );
    }

    /**
     * @return bool
     */
    public function isManualValue()
    {
        return $this->is_manual_value;
    }
}
