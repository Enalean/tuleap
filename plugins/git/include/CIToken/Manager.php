<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\CIToken;

use GitRepository;
use RandomNumberGenerator;

class Manager
{
    /** Dao */
    private $dao;

    /** RandomNumberGenerator */
    private $token_generator;

    public function __construct(Dao $dao)
    {
        $this->dao             = $dao;
        $this->token_generator = new RandomNumberGenerator(32);
    }

    public function generateNewTokenForRepository(GitRepository $git_repository)
    {
        $new_token = $this->token_generator->getNumber();

        $this->dao->updateTokenForRepositoryId($git_repository->getId(), $new_token);
    }

    public function getToken(GitRepository $git_repository)
    {
        $token = $this->dao->getTokenForRepositoryId($git_repository->getId());

        if ($token === false) {
            return null;
        }

        return $token;
    }
}
