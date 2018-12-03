<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\Authorization\User;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;

class UserTokenCreator
{
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var UserAuthorizationDAO
     */
    private $dao;

    public function __construct(SplitTokenVerificationStringHasher $hasher, UserAuthorizationDAO $dao)
    {
        $this->hasher = $hasher;
        $this->dao    = $dao;
    }

    /**
     * @return SplitToken
     */
    public function createUserAuthorizationToken(
        \GitRepository $repository,
        \DateTimeImmutable $expiration,
        \PFUser $user,
        UserOperation $user_operation
    ) {
        $verification_string        = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $hashed_verification_string = $this->hasher->computeHash($verification_string);

        $token_id = $this->dao->create(
            $repository->getId(),
            $hashed_verification_string,
            $expiration->getTimestamp(),
            $user_operation->getName(),
            $user->getId()
        );

        return new SplitToken($token_id, $verification_string);
    }
}
