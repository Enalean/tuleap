<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Timeframe;

use PFUser;
use TimePeriodWithoutWeekEnd;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_FormElement_Field_Date;
use Tracker_FormElementFactory;

class TimeframeBuilder
{
    private const START_DATE_FIELD_NAME = 'start_date';
    private const DURATION_FIELD_NAME   = 'duration';

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    public function __construct(Tracker_FormElementFactory $formelement_factory)
    {
        $this->formelement_factory = $formelement_factory;
    }

    public function buildTimePeriodWithoutWeekendForArtifact(Tracker_Artifact $artifact, PFUser $user) : TimePeriodWithoutWeekEnd
    {
        $start_date  = $this->getTimestamp($user, $artifact);
        $duration    = $this->getComputedFieldValue($user, $artifact);

        return new TimePeriodWithoutWeekEnd($start_date, $duration);
    }

    private function getTimestamp(PFUser $user, Tracker_Artifact $artifact) : int
    {
        $field = $this->formelement_factory->getDateFieldByNameForUser(
            $artifact->getTracker(),
            $user,
            self::START_DATE_FIELD_NAME
        );

        if ($field === null) {
            return 0;
        }

        assert($field instanceof Tracker_FormElement_Field_Date);

        $value = $field->getLastChangesetValue($artifact);
        if (! $value) {
            return 0;
        }

        assert($value instanceof Tracker_Artifact_ChangesetValue_Date);

        return (int) $value->getTimestamp();
    }

    private function getComputedFieldValue(PFUser $user, Tracker_Artifact $milestone_artifact)
    {
        $field = $this->formelement_factory->getComputableFieldByNameForUser(
            $milestone_artifact->getTracker()->getId(),
            self::DURATION_FIELD_NAME,
            $user
        );

        if ($field) {
            return $field->getComputedValue($user, $milestone_artifact);
        }

        return 0;
    }
}
