<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\BuildSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldsBuilder;

final class BuildSynchronizedFieldsStub implements BuildSynchronizedFields
{
    private function __construct(
        private int $artifact_link_id,
        private int $title_id,
        private int $description_id,
        private int $status_id,
        private int $start_date_id,
        private int $end_date_id
    ) {
    }

    public function build(ProgramTracker $source_tracker): SynchronizedFields
    {
        return SynchronizedFieldsBuilder::buildWithIds(
            $this->artifact_link_id,
            $this->title_id,
            $this->description_id,
            $this->status_id,
            $this->start_date_id,
            $this->end_date_id
        );
    }

    public static function withDefault(): self
    {
        return new self(555, 235, 937, 785, 571, 699);
    }

    public static function withFieldIds(
        int $artifact_link_id,
        int $title_id,
        int $description_id,
        int $status_id,
        int $start_date_id,
        int $end_date_id
    ): self {
        return new self($artifact_link_id, $title_id, $description_id, $status_id, $start_date_id, $end_date_id);
    }
}
