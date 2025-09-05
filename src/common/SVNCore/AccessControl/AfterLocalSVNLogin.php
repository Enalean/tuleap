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

namespace Tuleap\SVNCore\AccessControl;

use Tuleap\User\AfterLocalLogin;

final class AfterLocalSVNLogin implements AfterLocalLogin
{
    public const NAME = 'afterLocalSVNLogin';

    private bool $is_login_allowed   = true;
    private string $feedback_message = '';

    public function __construct(
        /** @psalm-readonly */
        public \PFUser $user,
        /** @psalm-readonly */
        public \Project $project,
    ) {
    }

    public function refuseLogin(string $feedback): void
    {
        $this->feedback_message = $feedback;
        $this->is_login_allowed = false;
    }

    #[\Override]
    public function isIsLoginAllowed(): bool
    {
        return $this->is_login_allowed;
    }

    #[\Override]
    public function getFeedbackMessage(): string
    {
        return $this->feedback_message;
    }
}
