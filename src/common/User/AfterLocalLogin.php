<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\User;

use Tuleap\Event\Dispatchable;

/**
 * This event is thrown after authentication of a user against the local User database.
 *
 * /!\ It's not thrown in any other logins
 */
final class AfterLocalLogin implements Dispatchable
{
    public const NAME = 'afterLocalLogin';
    /**
     * @var \PFUser
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

    public function __construct(\PFUser $user)
    {
        $this->user = $user;
    }

    public function refuseLogin(string $feedback = ''): void
    {
        $this->feedback_message = $feedback;
        $this->is_login_allowed = false;
    }

    public function isIsLoginAllowed(): bool
    {
        return $this->is_login_allowed;
    }

    public function getFeedbackMessage(): string
    {
        return $this->feedback_message;
    }
}
