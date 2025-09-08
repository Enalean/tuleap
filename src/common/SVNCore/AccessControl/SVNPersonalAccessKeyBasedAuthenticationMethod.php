<?php
/**
 * Copyright (c) Enalean 2023-Present. All rights reserved
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
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\IncorrectSizeVerificationStringException;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\Server\IPAddressExtractor;
use Tuleap\User\AccessKey\AccessKeyException;
use Tuleap\User\AccessKey\AccessKeyVerifier;

final class SVNPersonalAccessKeyBasedAuthenticationMethod implements SVNAuthenticationMethod
{
    public function __construct(
        private SplitTokenIdentifierTranslator $access_key_identifier_unserializer,
        private AccessKeyVerifier $access_key_verifier,
        private AuthenticationScope $authentication_scope,
        private SVNLoginNameUserProvider $user_provider,
        private \Psr\Log\LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function isAuthenticated(
        string $login_name,
        ConcealedString $user_secret,
        \Project $project,
        ServerRequestInterface $request,
    ): ?\PFUser {
        try {
            $access_key = $this->access_key_identifier_unserializer->getSplitToken($user_secret);
        } catch (InvalidIdentifierFormatException | IncorrectSizeVerificationStringException $ex) {
            $this->logger->debug(
                'SVN personal access key authentication rejected: secret does not look like a valid personal access key',
                ['exception' => $ex]
            );
            return null;
        }

        try {
            $access_key_user = $this->access_key_verifier->getUser(
                $access_key,
                $this->authentication_scope,
                IPAddressExtractor::getIPAddressFromServerParams($request->getServerParams()),
            );
        } catch (AccessKeyException $ex) {
            $this->logger->debug(
                'SVN personal access key authentication rejected: access key is not valid',
                ['exception' => $ex]
            );
            return null;
        }

        $svn_login_name_user = $this->user_provider->getUserFromSVNLoginName($login_name, $project);
        if ($svn_login_name_user === null) {
            $this->logger->debug(sprintf('SVN personal access key authentication rejected: no user found with the login name %s', $login_name));
            return null;
        }

        $svn_login_name_user_id = (int) $svn_login_name_user->getId();
        $access_key_user_id     = (int) $access_key_user->getId();
        if ($svn_login_name_user_id !== $access_key_user_id) {
            $this->logger->debug(
                sprintf(
                    'SVN personal access key authentication rejected: user associated with the SVN login name %s (#%d) is not the same than the one associated with the access key (#%d)',
                    $login_name,
                    $svn_login_name_user->getId(),
                    $access_key_user->getId()
                )
            );
            return null;
        }

        return $access_key_user;
    }
}
