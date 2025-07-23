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

use HTTPRequest;
use Tuleap\Layout\BaseLayout;

class IProcessRegisterFormStub implements IProcessRegisterForm
{
    private bool $is_admin           = false;
    private bool $is_password_needed = false;
    private bool $has_been_processed = false;

    private function __construct()
    {
    }

    public static function buildSelf(): self
    {
        return new self();
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, RegisterFormContext $context): void
    {
        $this->has_been_processed = true;
        $this->is_admin           = $context->is_admin;
        $this->is_password_needed = $context->is_password_needed;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isPasswordNeeded(): bool
    {
        return $this->is_password_needed;
    }

    public function hasBeenProcessed(): bool
    {
        return $this->has_been_processed;
    }
}
