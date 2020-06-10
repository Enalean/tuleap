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

use DateInterval;
use DateTime;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use UserManager;

class Verifier
{
    public const TOKEN_VALIDITY_PERIOD = 'PT1H';

    /**
     * @var LostPasswordDAO
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

    public function __construct(LostPasswordDAO $dao, SplitTokenVerificationStringHasher $hasher, UserManager $user_manager)
    {
        $this->dao          = $dao;
        $this->hasher       = $hasher;
        $this->user_manager = $user_manager;
    }

    /**
     * @throws \Tuleap\User\Password\Reset\ExpiredTokenException
     * @throws \Tuleap\User\Password\Reset\InvalidTokenException
     */
    public function getUser(SplitToken $token): \PFUser
    {
        $row = $this->dao->getTokenInformationById($token->getID());

        if ($row === null) {
            throw new InvalidTokenException('Invalid ID');
        }

        $is_token_valid = $this->hasher->verifyHash($token->getVerificationString(), $row['verifier']);

        if ($is_token_valid === false) {
            throw new InvalidTokenException('Invalid identifier');
        }

        $maximum_expiration_date = new DateTime('@' . $row['creation_date']);
        $maximum_expiration_date->add(new DateInterval(self::TOKEN_VALIDITY_PERIOD));

        $current_date = new DateTime();

        if ($current_date > $maximum_expiration_date) {
            throw new ExpiredTokenException();
        }

        return $this->user_manager->getUserById($row['user_id']);
    }
}
