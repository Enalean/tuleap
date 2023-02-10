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

use Tuleap\Event\Dispatchable;

final class BeforeRegisterFormValidationEvent implements Dispatchable
{
    public const NAME = 'beforeRegisterFormValidationEvent';

    private ?RegisterFormValidationIssue $validation_error = null;

    public function __construct(private \HTTPRequest $request)
    {
    }

    public function getRequest(): \HTTPRequest
    {
        return $this->request;
    }

    public function addValidationError(RegisterFormValidationIssue $validation_error): void
    {
        $this->validation_error = $validation_error;
    }

    public function getValidationError(): ?RegisterFormValidationIssue
    {
        return $this->validation_error;
    }
}
