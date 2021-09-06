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

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveDescriptionField;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;

final class RetrieveDescriptionFieldStub implements RetrieveDescriptionField
{
    private function __construct(private DescriptionFieldReference $description)
    {
    }

    public static function withField(int $field_id, string $field_label): self
    {
        return new self(
            DescriptionFieldReferenceProxy::fromTrackerField(
                new \Tracker_FormElement_Field_Text(
                    $field_id,
                    1,
                    null,
                    'irrelevant',
                    $field_label,
                    'Irrelevant',
                    true,
                    'P',
                    true,
                    '',
                    1
                )
            )
        );
    }

    public function getDescriptionField(ProgramIncrementTrackerIdentifier $program_increment): DescriptionFieldReference
    {
        return $this->description;
    }
}
