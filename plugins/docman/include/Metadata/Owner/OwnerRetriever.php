<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Metadata\Owner;

use PFUser;
use UserManager;

class OwnerRetriever
{
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(UserManager $user_manager)
    {
        $this->user_manager = $user_manager;
    }

    /**
     * @deprecated should be used only in core context
     */
    public function getOwnerIdFromLoginName(string $candidate_owner): ?int
    {
        $candidate_owner = $this->user_manager->findUser($candidate_owner);

        $owner = $this->checkUser($candidate_owner);
        if ($owner === null) {
            return null;
        }

        return (int) $owner->getId();
    }

    public function getUserFromRepresentationId(int $owner_id): ?PFUser
    {
        $candidate_owner = $this->user_manager->getUserById($owner_id);

        return $this->checkUser($candidate_owner);
    }

    private function checkUser(?PFUser $candidate_owner): ?PFUser
    {
        if ($candidate_owner === null) {
            return null;
        }

        if (! $candidate_owner->isAlive()) {
            return null;
        }

        return $candidate_owner;
    }
}
