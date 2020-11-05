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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields;

/**
 * @psalm-immutable
 */
final class SynchronizedFields
{
    /**
     * @var FieldData
     */
    private $artifact_link_field;
    /**
     * @var FieldData
     */
    private $title_field;
    /**
     * @var FieldData
     */
    private $description_field;
    /**
     * @var FieldData
     */
    private $status_field;
    /**
     * @var FieldData
     */
    private $start_date_field;
    /**
     * @var FieldData
     */
    private $end_period_field;

    public function __construct(
        FieldData $artifact_link_field,
        FieldData $title_field,
        FieldData $description_field,
        FieldData $status_field,
        FieldData $start_date_field,
        FieldData $end_period_field
    ) {
        $this->artifact_link_field = $artifact_link_field;
        $this->title_field         = $title_field;
        $this->description_field   = $description_field;
        $this->status_field        = $status_field;
        $this->start_date_field    = $start_date_field;
        $this->end_period_field    = $end_period_field;
    }

    public function getArtifactLinkField(): FieldData
    {
        return $this->artifact_link_field;
    }

    public function getTitleField(): FieldData
    {
        return $this->title_field;
    }

    public function getDescriptionField(): FieldData
    {
        return $this->description_field;
    }

    public function getStatusField(): FieldData
    {
        return $this->status_field;
    }

    public function getStartDateField(): FieldData
    {
        return $this->start_date_field;
    }

    public function getEndPeriodField(): FieldData
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
     * @return FieldData[]
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
