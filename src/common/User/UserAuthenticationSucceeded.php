<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\User;

use PFUser;
use Tuleap\Event\Dispatchable;

final class UserAuthenticationSucceeded implements Dispatchable
{
    public const NAME = 'userAuthenticationSucceeded';

    /**
     * @var PFUser
     * @psalm-readonly
     */
    public $user;
    /**
     * @var bool
     */
    private $is_login_allowed = true;
    /**
     * @var string
     */
    private $feedback_message = '';

    public function __construct(PFUser $user)
    {
        $this->user = $user;
    }

    public function refuseLogin(string $feedback = ''): void
    {
        $this->is_login_allowed = false;
        $this->feedback_message = $feedback;
    }

    public function isLoginAllowed(): bool
    {
        return $this->is_login_allowed;
    }

    public function getFeedbackMessage(): string
    {
        return $this->feedback_message;
    }
}
