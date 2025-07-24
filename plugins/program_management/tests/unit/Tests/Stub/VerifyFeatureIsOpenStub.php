<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsOpen;

final class VerifyFeatureIsOpenStub implements VerifyFeatureIsOpen
{
    private function __construct(private bool $always_open, private array $return_values)
    {
    }

    public static function withOpen(): self
    {
        return new self(true, []);
    }

    public static function withClosed(): self
    {
        return new self(false, []);
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveValues(bool $first_open, bool ...$other_open): self
    {
        return new self(false, [$first_open, ...$other_open]);
    }

    #[\Override]
    public function isFeatureOpen(FeatureIdentifier $feature): bool
    {
        if ($this->always_open) {
            return true;
        }
        if (count($this->return_values) > 0) {
            return array_shift($this->return_values);
        }
        return false;
    }
}
