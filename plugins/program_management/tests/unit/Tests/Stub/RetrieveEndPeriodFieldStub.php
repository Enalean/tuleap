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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DurationFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\MissingTimeFrameFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveEndPeriodField;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class RetrieveEndPeriodFieldStub implements RetrieveEndPeriodField
{
    /**
     * @param array<DurationFieldReference|EndDateFieldReference> $end_periods
     */
    private function __construct(private bool $should_throw, private array $end_periods)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withEndDateFields(EndDateFieldReference $field, EndDateFieldReference ...$other_fields): self
    {
        return new self(false, [$field, ...$other_fields]);
    }

    /**
     * @no-named-arguments
     */
    public static function withDurationFields(DurationFieldReference $field, DurationFieldReference ...$other_fields): self
    {
        return new self(false, [$field, ...$other_fields]);
    }

    public static function withError(): self
    {
        return new self(true, []);
    }

    #[\Override]
    public function getEndPeriodField(
        TrackerIdentifier $tracker_identifier,
    ): EndDateFieldReference|DurationFieldReference {
        if ($this->should_throw) {
            throw new MissingTimeFrameFieldException($tracker_identifier->getId(), 'end date or duration');
        }
        if (count($this->end_periods) > 0) {
            return array_shift($this->end_periods);
        }
        throw new \LogicException('No end period field configured');
    }
}
