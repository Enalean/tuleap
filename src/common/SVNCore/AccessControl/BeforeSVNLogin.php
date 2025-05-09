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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Option\Option;
use Tuleap\User\BeforeLogin;
use function Psl\Type\string;

final class BeforeSVNLogin implements BeforeLogin
{
    public const NAME = 'beforeSVNLogin';

    private ?\PFUser $user = null;
    /**
     * @var Option<string>
     */
    private Option $login_refusal;

    public function __construct(
        private readonly string $login_name,
        private readonly ConcealedString $password,
        public readonly \Project $project,
    ) {
        $this->login_refusal = Option::nothing(string());
    }

    public function getLoginName(): string
    {
        return $this->login_name;
    }

    public function getPassword(): ConcealedString
    {
        return $this->password;
    }

    public function setUser(?\PFUser $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?\PFUser
    {
        return $this->user;
    }

    public function refuseLogin(string $feedback): void
    {
        $this->login_refusal = Option::fromValue($feedback);
    }

    public function isLoginRefused(): Option
    {
        return $this->login_refusal;
    }
}
