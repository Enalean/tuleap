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

namespace Tuleap\ProgramManagement\Adapter\Redirections;

use Tuleap\ProgramManagement\Domain\Redirections\IterationRedirectionParameters;

/**
 * @psalm-immutable
 */
final class IterationRedirectionParametersProxy implements IterationRedirectionParameters
{
    private function __construct(private string $redirect_value, private string $increment_id)
    {
    }

    public static function buildFromCodendiRequest(\Codendi_Request $request): self
    {
        $redirect_value = $request->get(IterationRedirectionParameters::FLAG) ?: '';
        $increment_id   = $request->get(IterationRedirectionParameters::PARAM_INCREMENT_ID) ?: '';

        return new self($redirect_value, $increment_id);
    }

    #[\Override]
    public function getValue(): string
    {
        return $this->redirect_value;
    }

    #[\Override]
    public function getIncrementId(): string
    {
        return $this->increment_id;
    }

    #[\Override]
    public function isRedirectionNeeded(): bool
    {
        return $this->needsRedirectionAfterCreate() || $this->needsRedirectionAfterUpdate();
    }

    #[\Override]
    public function needsRedirectionAfterCreate(): bool
    {
        return $this->increment_id !== '' &&
            $this->redirect_value === IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION;
    }

    #[\Override]
    public function needsRedirectionAfterUpdate(): bool
    {
        return $this->increment_id !== '' &&
        $this->redirect_value === IterationRedirectionParameters::REDIRECT_AFTER_UPDATE_ACTION;
    }
}
