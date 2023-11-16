<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

class TimeframeChangesetFieldsValueRetriever
{
    /**
     * @throws TimeframeFieldNotFoundException
     * @throws TimeframeFieldNoValueException
     */
    public static function getTimestamp(\Tracker_FormElement_Field_Date $date_field, \PFUser $user, \Tracker_Artifact_Changeset $changeset): int
    {
        if (! $date_field->userCanRead($user)) {
            throw new TimeframeFieldNotFoundException();
        }

        $value = $changeset->getValue($date_field);
        if ($value === null) {
            throw new TimeframeFieldNoValueException();
        }

        assert($value instanceof \Tracker_Artifact_ChangesetValue_Date);

        $timestamp = $value->getTimestamp();

        if ($timestamp === null) {
            throw new TimeframeFieldNoValueException();
        }

        return $timestamp;
    }

    /**
     * @throws TimeframeFieldNotFoundException
     * @throws TimeframeFieldNoValueException
     */
    public static function getDurationFieldValue(\Tracker_FormElement_Field_Numeric $duration_field, \PFUser $user, \Tracker_Artifact_Changeset $changeset): ?float
    {
        if (! $duration_field->userCanRead($user)) {
            throw new TimeframeFieldNotFoundException();
        }

        $last_changeset_value = $changeset->getValue($duration_field);
        if ($last_changeset_value === null) {
            throw new TimeframeFieldNoValueException();
        }

        assert($last_changeset_value instanceof \Tracker_Artifact_ChangesetValue_Numeric);

        return $last_changeset_value->getNumeric();
    }
}
