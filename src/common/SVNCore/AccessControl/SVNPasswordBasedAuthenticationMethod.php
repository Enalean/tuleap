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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVNCore\AccessControl;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\ConcealedString;

final class SVNPasswordBasedAuthenticationMethod implements SVNAuthenticationMethod
{
    public function __construct(private \User_LoginManager $login_manager, private LoggerInterface $logger)
    {
    }

    #[\Override]
    public function isAuthenticated(string $login_name, ConcealedString $user_secret, \Project $project, ServerRequestInterface $request): ?\PFUser
    {
        try {
            $user = $this->login_manager->authenticate(
                $login_name,
                $user_secret,
                fn (string $login_name, ConcealedString $password): BeforeSVNLogin => new BeforeSVNLogin($login_name, $user_secret, $project),
                fn (\PFUser $user): AfterLocalSVNLogin => new AfterLocalSVNLogin($user, $project)
            );
            $this->login_manager->validateAndSetCurrentUser($user);
        } catch (\User_LoginException $e) {
            $this->logger->debug(sprintf('SVN password based authentication rejected (%s)', $e->getMessage()), ['exception' => $e]);
            return null;
        }

        $this->logger->debug(sprintf('SVN password based authentication success for user #%d (%s)', $user->getId(), $user->getUserName()));
        return $user;
    }
}
