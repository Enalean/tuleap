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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveStartDateField;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class RetrieveStartDateFieldStub implements RetrieveStartDateField
{
    /**
     * @var StartDateFieldReference[]
     */
    private array $start_dates;

    private function __construct(StartDateFieldReference ...$start_dates)
    {
        $this->start_dates = $start_dates;
    }

    public static function withFields(StartDateFieldReference ...$start_dates): self
    {
        return new self(...$start_dates);
    }

    #[\Override]
    public function getStartDateField(TrackerIdentifier $tracker_identifier): StartDateFieldReference
    {
        if (count($this->start_dates) > 0) {
            return array_shift($this->start_dates);
        }
        throw new \LogicException('No start date field configured');
    }
}
