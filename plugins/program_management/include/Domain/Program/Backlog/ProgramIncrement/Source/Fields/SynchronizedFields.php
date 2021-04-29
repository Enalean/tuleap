<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields;

use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_Numeric;

/**
 * @psalm-immutable
 */
final class SynchronizedFields
{
    /**
     * @var Field
     */
    private $artifact_link_field;
    /**
     * @var Field
     */
    private $title_field;
    /**
     * @var Field
     */
    private $description_field;
    /**
     * @psalm-var Field<Tracker_FormElement_Field_List>
     */
    private $status_field;
    /**
     * @psalm-var Field<Tracker_FormElement_Field_Date>
     */
    private $start_date_field;
    /**
     * @psalm-var Field<Tracker_FormElement_Field_Date>|Field<Tracker_FormElement_Field_Numeric>
     */
    private $end_period_field;

    /**
     * @psalm-param Field<Tracker_FormElement_Field_List> $status_field
     * @psalm-param Field<Tracker_FormElement_Field_Date> $start_date_field
     * @psalm-param Field<Tracker_FormElement_Field_Date>|Field<Tracker_FormElement_Field_Numeric> $end_period_field
     */
    public function __construct(
        Field $artifact_link_field,
        Field $title_field,
        Field $description_field,
        Field $status_field,
        Field $start_date_field,
        Field $end_period_field
    ) {
        $this->artifact_link_field = $artifact_link_field;
        $this->title_field         = $title_field;
        $this->description_field   = $description_field;
        $this->status_field        = $status_field;
        $this->start_date_field    = $start_date_field;
        $this->end_period_field    = $end_period_field;
    }

    public function getArtifactLinkField(): Field
    {
        return $this->artifact_link_field;
    }

    public function getTitleField(): Field
    {
        return $this->title_field;
    }

    public function getDescriptionField(): Field
    {
        return $this->description_field;
    }

    /**
     * @psalm-return Field<Tracker_FormElement_Field_List>
     */
    public function getStatusField(): Field
    {
        return $this->status_field;
    }

    /**
     * @psalm-return Field<Tracker_FormElement_Field_Date>
     */
    public function getStartDateField(): Field
    {
        return $this->start_date_field;
    }

    /**
     * @psalm-return Field<Tracker_FormElement_Field_Date>|Field<Tracker_FormElement_Field_Numeric>
     */
    public function getEndPeriodField(): Field
    {
        return $this->end_period_field;
    }

    /**
     * @return array<int, true>
     * @psalm-readonly
     */
    public function getSynchronizedFieldIDsAsKeys(): array
    {
        return [
            $this->artifact_link_field->getId() => true,
            $this->title_field->getId()         => true,
            $this->description_field->getId()   => true,
            $this->status_field->getId()        => true,
            $this->start_date_field->getId()    => true,
            $this->end_period_field->getId()    => true,
        ];
    }

    /**
     * @return Field[]
     * @psalm-readonly
     */
    public function getAllFields(): array
    {
        return [
            $this->artifact_link_field,
            $this->title_field,
            $this->description_field,
            $this->status_field,
            $this->start_date_field,
            $this->end_period_field,
        ];
    }
}
