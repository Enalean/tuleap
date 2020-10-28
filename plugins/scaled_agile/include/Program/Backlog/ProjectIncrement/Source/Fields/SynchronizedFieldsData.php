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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields;

final class SynchronizedFieldsData
{
    /**
     * @var FieldData
     * @psalm-readonly
     */
    private $field_artifact_link_data;
    /**
     * @var FieldData
     * @psalm-readonly
     */
    private $field_title_data;
    /**
     * @var FieldData
     * @psalm-readonly
     */
    private $field_description_data;
    /**
     * @var FieldData
     * @psalm-readonly
     */
    private $field_status_data;
    /**
     * @var FieldData
     * @psalm-readonly
     */
    private $field_start_date_data;
    /**
     * @var FieldData
     * @psalm-readonly
     */
    private $field_end_period_data;

    public function __construct(
        FieldData $field_artifact_link_data,
        FieldData $field_title_data,
        FieldData $field_description_data,
        FieldData $field_status_data,
        FieldData $field_start_date_data,
        FieldData $field_end_period_data
    ) {
        $this->field_artifact_link_data = $field_artifact_link_data;
        $this->field_title_data         = $field_title_data;
        $this->field_description_data   = $field_description_data;
        $this->field_status_data        = $field_status_data;
        $this->field_start_date_data    = $field_start_date_data;
        $this->field_end_period_data    = $field_end_period_data;
    }

    public function getFieldArtifactLinkData(): FieldData
    {
        return $this->field_artifact_link_data;
    }

    public function getFieldTitleData(): FieldData
    {
        return $this->field_title_data;
    }

    public function getFieldDescriptionData(): FieldData
    {
        return $this->field_description_data;
    }

    public function getFieldStatuData(): FieldData
    {
        return $this->field_status_data;
    }

    public function getFieldStartDateData(): FieldData
    {
        return $this->field_start_date_data;
    }

    public function getFieldEndPriodData(): FieldData
    {
        return $this->field_end_period_data;
    }

    /**
     * @return array<int, true>
     * @psalm-readonly
     */
    public function getSynchronizedFieldIDsAsKeys(): array
    {
        return [
            $this->field_artifact_link_data->getId() => true,
            $this->field_title_data->getId()         => true,
            $this->field_description_data->getId()   => true,
            $this->field_status_data->getId()        => true,
            $this->field_start_date_data->getId()    => true,
            $this->field_end_period_data->getId()    => true,
        ];
    }

    /**
     * @return FieldData[]
     * @psalm-readonly
     */
    public function getAllFields(): array
    {
        return [
            $this->field_artifact_link_data,
            $this->field_title_data,
            $this->field_description_data,
            $this->field_status_data,
            $this->field_start_date_data,
            $this->field_end_period_data,
        ];
    }
}
