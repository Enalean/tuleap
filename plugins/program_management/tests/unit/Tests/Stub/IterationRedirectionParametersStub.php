<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Redirections\IterationRedirectionParameters;

/**
 * @psalm-immutable
 */
final class IterationRedirectionParametersStub implements IterationRedirectionParameters
{
    private function __construct(private string $redirect_value, private string $increment_id)
    {
    }

    public static function withCreate(): self
    {
        return new self(IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION, '100');
    }

    public static function withUpdate(): self
    {
        return new self(IterationRedirectionParameters::REDIRECT_AFTER_UPDATE_ACTION, '100');
    }

    public static function withValues(string $redirect, string $increment_id): self
    {
        return new self($redirect, $increment_id);
    }

    public function getValue(): string
    {
        return $this->redirect_value;
    }

    public function getIncrementId(): string
    {
        return $this->increment_id;
    }

    public function isRedirectionNeeded(): bool
    {
        return $this->needsRedirectionAfterCreate() || $this->needsRedirectionAfterUpdate();
    }

    public function needsRedirectionAfterCreate(): bool
    {
        return $this->increment_id !== '' &&
            $this->redirect_value === IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION;
    }

    public function needsRedirectionAfterUpdate(): bool
    {
        return $this->increment_id !== '' &&
            $this->redirect_value === IterationRedirectionParameters::REDIRECT_AFTER_UPDATE_ACTION;
    }
}
