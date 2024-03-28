<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Project\Service;

use Project;
use Tuleap\Event\Dispatchable;

final class ServiceDisabledCollector implements Dispatchable
{
    public const NAME = 'serviceDisabledCollector';

    private string $disabled_reason = '';
    /**
     * @psalm-readonly
     */
    private Project $project;
    /**
     * @psalm-readonly
     */
    private string $service_shortname;
    /**
     * @psalm-readonly
     */
    private \PFUser $user;

    public function __construct(Project $project, string $shortname, \PFUser $user)
    {
        $this->project           = $project;
        $this->service_shortname = $shortname;
        $this->user              = $user;
    }

    public function getReason(): string
    {
        return $this->disabled_reason;
    }

    public function isForService(string $service): bool
    {
        return $this->service_shortname === $service;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setIsDisabled(string $reason): void
    {
        $this->disabled_reason = $reason;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }
}
