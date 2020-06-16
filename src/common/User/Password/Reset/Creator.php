<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\User\Password\Reset;

use PFUser;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

class Creator
{
    /**
     * @var LostPasswordDAO
     */
    private $dao;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;

    public function __construct(
        LostPasswordDAO $dao,
        SplitTokenVerificationStringHasher $hasher
    ) {
        $this->dao    = $dao;
        $this->hasher = $hasher;
    }

    public function create(PFUser $user): ?SplitToken
    {
        $verification_string = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $current_date        = new \DateTime();

        $token_id = $this->dao->create(
            $user->getId(),
            $this->hasher->computeHash($verification_string),
            $current_date->getTimestamp()
        );

        if ($token_id === null) {
            return null;
        }

        return new SplitToken($token_id, $verification_string);
    }
}
