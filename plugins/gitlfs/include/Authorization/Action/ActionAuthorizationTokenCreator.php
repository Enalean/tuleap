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
 */

namespace Tuleap\GitLFS\Authorization\Action;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

class ActionAuthorizationTokenCreator
{
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var ActionAuthorizationDAO
     */
    private $dao;

    public function __construct(SplitTokenVerificationStringHasher $hasher, ActionAuthorizationDAO $dao)
    {
        $this->hasher = $hasher;
        $this->dao    = $dao;
    }

    /**
     * @return SplitToken
     */
    public function createActionAuthorizationToken(ActionAuthorizationRequest $action_authorization)
    {
        $verification_string        = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $hashed_verification_string = $this->hasher->computeHash($verification_string);

        $token_id = $this->dao->create(
            $action_authorization->getGitRepository()->getId(),
            $hashed_verification_string,
            $action_authorization->getExpiration()->getTimestamp(),
            $action_authorization->getActionType()->getName(),
            $action_authorization->getObject()->getOID()->getValue(),
            $action_authorization->getObject()->getSize()
        );

        return new SplitToken($token_id, $verification_string);
    }
}
