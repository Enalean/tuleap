<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Service;

use Tuleap\Event\Dispatchable;

final class UserCanAccessToServiceEvent implements Dispatchable
{
    public const string NAME = 'userCanAccessToService';

    private bool $is_allowed = true;

    public function __construct(private \Service $service, private \PFUser $user)
    {
    }

    public function getService(): \Service
    {
        return $this->service;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }

    public function forbidAccessToService(): void
    {
        $this->is_allowed = false;
    }

    public function isAllowed(): bool
    {
        return $this->is_allowed;
    }
}
