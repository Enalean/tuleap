<?php
/**
 * Copyright (c) Enalean 2022-Present. All rights reserved
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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\Server\IPAddressExtractor;

final class SVNTokenBasedAuthenticationMethod implements SVNAuthenticationMethod
{
    public function __construct(private \SVN_TokenHandler $token_handler, private LoggerInterface $logger)
    {
    }

    public function isAuthenticated(\PFUser $user, ConcealedString $user_secret, ServerRequestInterface $request): bool
    {
        if ($this->token_handler->isTokenValid($user, $user_secret, IPAddressExtractor::getIPAddressFromServerParams($request->getServerParams()))) {
            $this->logger->debug(sprintf('SVN token based authentication rejected: no matching token found for user #%d (%s)', $user->getId(), $user->getUserName()));
            return true;
        }

        $this->logger->debug(sprintf('SVN token based authentication success for user #%d (%s)', $user->getId(), $user->getUserName()));
        return false;
    }
}
