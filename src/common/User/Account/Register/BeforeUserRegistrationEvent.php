<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\User\Account\Register;

final class BeforeUserRegistrationEvent implements \Tuleap\Event\Dispatchable
{
    public const NAME = 'beforeUserRegistrationEvent';

    private bool $is_password_needed = true;

    public function __construct(private \Codendi_Request $request)
    {
    }

    public function getRequest(): \Codendi_Request
    {
        return $this->request;
    }

    public function isPasswordNeeded(): bool
    {
        return $this->is_password_needed;
    }

    public function noNeedForPassword(): void
    {
        $this->is_password_needed = false;
    }
}
