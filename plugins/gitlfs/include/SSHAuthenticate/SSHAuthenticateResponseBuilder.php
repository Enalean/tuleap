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

namespace Tuleap\GitLFS\SSHAuthenticate;

use Tuleap\GitLFS\Authorization\LFSAuthorizationTokenHeaderSerializer;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;
use Tuleap\GitLFS\Authorization\User\UserTokenCreator;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionContent;

class SSHAuthenticateResponseBuilder
{
    const EXPIRES_IN_SECONDS = 600;

    /**
     * @var UserTokenCreator
     */
    private $token_creator;

    public function __construct(UserTokenCreator $token_creator)
    {
        $this->token_creator = $token_creator;
    }

    public function getResponse(
        \GitRepository $repository,
        \PFUser $user,
        UserOperation $operation,
        \DateTimeImmutable $current_time
    ) {
        return new BatchResponseActionContent(
            new LFSEndPointDiscovery($repository),
            $this->token_creator->createUserAuthorizationToken(
                $repository,
                $current_time->add(new \DateInterval('PT' . self::EXPIRES_IN_SECONDS . 'S')),
                $user,
                $operation
            ),
            new LFSAuthorizationTokenHeaderSerializer(),
            self::EXPIRES_IN_SECONDS
        );
    }
}
