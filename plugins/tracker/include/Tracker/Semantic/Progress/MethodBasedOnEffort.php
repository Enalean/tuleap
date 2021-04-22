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


namespace Tuleap\Tracker\Semantic\Progress;

use Tracker_FormElement_Field_Numeric;

class MethodBasedOnEffort implements IComputeProgression
{
    private const METHOD_NAME = 'effort-based';

    /**
     * @var Tracker_FormElement_Field_Numeric
     */
    private $total_effort_field;
    /**
     * @var Tracker_FormElement_Field_Numeric
     */
    private $remaining_effort_field;

    public function __construct(
        Tracker_FormElement_Field_Numeric $total_effort_field,
        Tracker_FormElement_Field_Numeric $remaining_effort_field
    ) {
        $this->total_effort_field     = $total_effort_field;
        $this->remaining_effort_field = $remaining_effort_field;
    }

    public static function getMethodName(): string
    {
        return self::METHOD_NAME;
    }

    public static function getMethodLabel(): string
    {
        return dgettext('tuleap-tracker', 'Effort based');
    }

    public function getTotalEffortFieldId(): int
    {
        return $this->total_effort_field->getId();
    }

    public function getRemainingEffortFieldId(): int
    {
        return $this->remaining_effort_field->getId();
    }

    public function getCurrentConfigurationDescription(): string
    {
        return sprintf(
            dgettext(
                'tuleap-tracker',
                'The progress of artifacts is based on effort and will be computed by dividing the current value of the "%s" field by the current value of the "%s" field.'
            ),
            $this->remaining_effort_field->getLabel(),
            $this->total_effort_field->getLabel()
        );
    }

    public function isFieldUsedInComputation(\Tracker_FormElement_Field $field): bool
    {
        $field_id = $field->getId();

        return $field_id === $this->total_effort_field->getId()
            || $field_id === $this->remaining_effort_field->getId();
    }
}
