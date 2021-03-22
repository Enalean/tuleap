<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\Git\CIBuilds;

use GitRepository;
use RandomNumberGenerator;

class CITokenManager
{
    /**
     * @var CITokenDao
     */
    private $dao;

    /**
     * @var RandomNumberGenerator
     */
    private $token_generator;

    public function __construct(CITokenDao $dao)
    {
        $this->dao             = $dao;
        $this->token_generator = new RandomNumberGenerator(32);
    }

    public function generateNewTokenForRepository(GitRepository $git_repository): void
    {
        $new_token = $this->token_generator->getNumber();

        $this->dao->updateTokenForRepositoryId((int) $git_repository->getId(), $new_token);
    }

    public function getToken(GitRepository $git_repository): ?string
    {
        return $this->dao->getTokenForRepositoryId((int) $git_repository->getId());
    }
}
