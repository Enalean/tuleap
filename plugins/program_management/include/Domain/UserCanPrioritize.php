<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain;

use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

class UserCanPrioritize
{
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var int
     */
    private $id;

    private function __construct(\PFUser $user)
    {
        $this->user = $user;
        $this->id   = (int) $user->getId();
    }

    public function getFullUser(): \PFUser
    {
        return $this->user;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @throws NotAllowedToPrioritizeException
     */
    public static function fromUser(VerifyPrioritizeFeaturesPermission $permission, \PFUser $user, ProgramIdentifier $program): self
    {
        if (! $permission->canUserPrioritizeFeatures($program, $user)) {
            throw new NotAllowedToPrioritizeException((int) $user->getId(), $program->getId());
        }
        return new self($user);
    }
}
