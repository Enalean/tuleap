<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Redirections;

/**
 * @psalm-immutable
 */
final class IterationsRedirectParameters
{
    public const FLAG                         = 'redirect-to-planned-iterations';
    public const REDIRECT_AFTER_CREATE_ACTION = 'create';
    public const PARAM_INCREMENT_ID           = 'increment-id';

    private function __construct(
        private string $redirect_value,
        private string $increment_id
    ) {
    }

    public static function buildFromCodendiRequest(\Codendi_Request $request): self
    {
        $redirect_program_increment_value = $request->get(self::FLAG) ?: "";
        $increment_id                     = $request->get(self::PARAM_INCREMENT_ID) ?: "";

        return new self($redirect_program_increment_value, $increment_id);
    }

    public function isRedirectionNeeded(): bool
    {
        return $this->needsRedirectionAfterCreate();
    }

    public function needsRedirectionAfterCreate(): bool
    {
        return $this->increment_id !== "" &&
            $this->redirect_value === self::REDIRECT_AFTER_CREATE_ACTION;
    }

    public function getRedirectValue(): string
    {
        return $this->redirect_value;
    }

    public function getIncrementId(): string
    {
        return $this->increment_id;
    }
}
