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

use Tuleap\ProgramManagement\Domain\Redirections\ProgramRedirectionParameters;

/**
 * @psalm-immutable
 */
final class ProgramRedirectionParametersStub implements ProgramRedirectionParameters
{
    private function __construct(private string $redirect_value)
    {
    }

    public static function withCreate(): self
    {
        return new self(ProgramRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION);
    }

    public static function withUpdate(): self
    {
        return new self(ProgramRedirectionParameters::REDIRECT_AFTER_UPDATE_ACTION);
    }

    public static function withOtherValue(): self
    {
        return new self('other');
    }

    #[\Override]
    public function getValue(): string
    {
        return $this->redirect_value;
    }

    #[\Override]
    public function isRedirectionNeeded(): bool
    {
        return $this->needsRedirectionAfterCreate() || $this->needsRedirectionAfterUpdate();
    }

    #[\Override]
    public function needsRedirectionAfterCreate(): bool
    {
        return $this->redirect_value === self::REDIRECT_AFTER_CREATE_ACTION;
    }

    #[\Override]
    public function needsRedirectionAfterUpdate(): bool
    {
        return $this->redirect_value === self::REDIRECT_AFTER_UPDATE_ACTION;
    }
}
