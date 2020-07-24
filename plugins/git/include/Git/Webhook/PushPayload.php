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

namespace Tuleap\Git\Webhook;

use GitRepository;
use PFUser;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\Webhook\Payload;

class PushPayload implements Payload
{
    /**
     * @var array
     */
    private $payload;

    public function __construct(GitRepository $repository, PFUser $user, $oldrev, $newrev, $refname)
    {
        $this->payload = $this->buildPayload($repository, $user, $oldrev, $newrev, $refname);
    }

    /**
     * @return array
     */
    private function buildPayload(GitRepository $repository, PFUser $user, $oldrev, $newrev, $refname)
    {
        $repository_representation = [
            'id'        => $repository->getId(),
            'name'      => $repository->getName(),
            'full_name' => $repository->getFullName(),
        ];

        $pusher_representation = [
            'name'  => $user->getUserName(),
            'email' => $user->getEmail(),
        ];

        $sender_representation = MinimalUserRepresentation::build($user);

        return [
            'ref'        => $refname,
            'after'      => $newrev,
            'before'     => $oldrev,
            'repository' => $repository_representation,
            'pusher'     => $pusher_representation,
            'sender'     => $sender_representation,
        ];
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
