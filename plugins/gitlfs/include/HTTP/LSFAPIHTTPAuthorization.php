<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\HTTP;

use GitRepository;
use HTTPRequest;
use PFUser;
use Tuleap\Authentication\SplitToken\IncorrectSizeVerificationStringException;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperationDownload;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperationUpload;
use Tuleap\GitLFS\Authorization\User\UserAuthorizationException;
use Tuleap\GitLFS\Authorization\User\UserTokenVerifier;

class LSFAPIHTTPAuthorization
{
    /**
     * @var UserTokenVerifier
     */
    private $token_verifier;
    /**
     * @var SplitTokenIdentifierTranslator
     */
    private $split_token_unserializer;

    public function __construct(UserTokenVerifier $token_verifier, SplitTokenIdentifierTranslator $split_token_unserializer)
    {
        $this->token_verifier           = $token_verifier;
        $this->split_token_unserializer = $split_token_unserializer;
    }

    public function getUserFromAuthorizationToken(HTTPRequest $request, GitRepository $repository, GitLfsHTTPOperation $lfs_request): ?PFUser
    {
        $authorization_header = $request->getFromServer('HTTP_AUTHORIZATION');
        if ($authorization_header === false) {
            return null;
        }

        try {
            $authorization_token = $this->split_token_unserializer->getSplitToken(
                new ConcealedString($authorization_header)
            );
        } catch (IncorrectSizeVerificationStringException $ex) {
            return null;
        } catch (InvalidIdentifierFormatException $ex) {
            return null;
        }

        $user_operations = [];
        if ($lfs_request->isRead()) {
            $user_operations[] = new UserOperationDownload();
        }
        if ($lfs_request->isWrite()) {
            $user_operations[] = new UserOperationUpload();
        }
        if (empty($user_operations)) {
            return null;
        }

        foreach ($user_operations as $user_operation) {
            $user = $this->findUserMatchingTokenAndOperation($repository, $authorization_token, $user_operation);
            if ($user !== null) {
                return $user;
            }
        }

        return null;
    }

    private function findUserMatchingTokenAndOperation(
        GitRepository $repository,
        SplitToken $authorization_token,
        UserOperation $user_operation
    ): ?PFUser {
        try {
            return $this->token_verifier->getUser(
                new \DateTimeImmutable(),
                $authorization_token,
                $repository,
                $user_operation
            );
        } catch (UserAuthorizationException $ex) {
            return null;
        }
    }
}
