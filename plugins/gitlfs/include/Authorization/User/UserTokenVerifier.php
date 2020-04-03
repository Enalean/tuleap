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

use GitRepository;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;
use UserManager;

class UserTokenVerifier
{
    /**
     * @var UserAuthorizationDAO
     */
    private $dao;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        UserAuthorizationDAO $dao,
        SplitTokenVerificationStringHasher $hasher,
        UserManager $user_manager
    ) {
        $this->dao          = $dao;
        $this->hasher       = $hasher;
        $this->user_manager = $user_manager;
    }

    /**
     * @return \PFUser
     */
    public function getUser(
        \DateTimeImmutable $current_time,
        SplitToken $authorization_token,
        GitRepository $git_repository,
        UserOperation $user_operation
    ) {
        $row = $this->dao->searchAuthorizationByIDAndExpiration($authorization_token->getID(), $git_repository->getId(), $current_time->getTimestamp());
        if ($row === null) {
            throw new UserAuthorizationNotFoundException($authorization_token->getID());
        }

        $is_valid_access_key = $this->hasher->verifyHash($authorization_token->getVerificationString(), $row['verifier']);
        if (
            ! $is_valid_access_key ||
            ! \hash_equals($user_operation->getName(), $row['operation_name'])
        ) {
            throw new InvalidUserUserAuthorizationException();
        }

        $user = $this->user_manager->getUserById($row['user_id']);
        if ($user !== null && $user->isAlive()) {
            return $user;
        }

        throw new UserNotFoundExceptionUser();
    }
}
