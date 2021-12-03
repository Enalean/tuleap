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

namespace Tuleap\ProgramManagement\Domain\Redirections;

/**
 * @psalm-immutable
 */
interface IterationRedirectionParameters
{
    public const FLAG                         = 'redirect-to-planned-iterations';
    public const PARAM_INCREMENT_ID           = 'increment-id';
    public const REDIRECT_AFTER_CREATE_ACTION = 'create';
    public const REDIRECT_AFTER_UPDATE_ACTION = 'update';

    public function getValue(): string;

    public function getIncrementId(): string;

    public function isRedirectionNeeded(): bool;

    public function needsRedirectionAfterCreate(): bool;

    public function needsRedirectionAfterUpdate(): bool;
}
