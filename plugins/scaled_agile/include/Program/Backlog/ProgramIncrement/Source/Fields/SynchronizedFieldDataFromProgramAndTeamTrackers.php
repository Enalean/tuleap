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

final class SynchronizedFieldDataFromProgramAndTeamTrackers
{
    /**
     * @var array<int, true>
     * @psalm-readonly
     */
    private $synchronized_field_data_ids;
    /**
     * @var SynchronizedFields
     * @psalm-readonly
     */
    private $synchronized_fields_data;

    public function __construct(SynchronizedFields $synchronized_fields)
    {
        $this->synchronized_fields_data    = $synchronized_fields;
        $this->synchronized_field_data_ids = $synchronized_fields->getSynchronizedFieldIDsAsKeys();
    }

    public function getSynchronizedFieldsData(): SynchronizedFields
    {
        return $this->synchronized_fields_data;
    }

    /**
     * @return array<int, true>
     */
    public function getSynchronizedFieldDataIds(): array
    {
        return $this->synchronized_field_data_ids;
    }
}
