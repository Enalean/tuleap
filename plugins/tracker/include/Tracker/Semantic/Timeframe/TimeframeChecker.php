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

use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;

class TimeframeChecker
{
    private const START_DATE_FIELD_NAME  = 'start_date';
    private const DURATION_FIELD_NAME    = 'duration';

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    public function isATimePeriodBuildableInTracker(Tracker $tracker): bool
    {
        $start_date_field = $this->getStartDateField($tracker);
        $duration_field   = $this->getDurationField($tracker);

        return $start_date_field
            && $start_date_field->isUsed()
            && $duration_field
            && $duration_field->isUsed();
    }

    private function getStartDateField(Tracker $tracker): ?Tracker_FormElement_Field
    {
        return $this->form_element_factory->getFormElementByName(
            $tracker->getId(),
            self::START_DATE_FIELD_NAME
        );
    }

    private function getDurationField(Tracker $tracker): ?Tracker_FormElement_Field
    {
        return $this->form_element_factory->getFormElementByName(
            $tracker->getId(),
            self::DURATION_FIELD_NAME
        );
    }
}
