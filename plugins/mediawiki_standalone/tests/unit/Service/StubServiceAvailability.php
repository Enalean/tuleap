<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Service;

final class StubServiceAvailability implements ServiceAvailability
{
    /**
     * @psalm-readonly
     * @psalm-allow-private-mutation
     */
    public bool $cannot_be_activated = false;

    public function __construct(private string $service_shortname, private \Project $project)
    {
    }

    #[\Override]
    public function isForService(string $service_shortname): bool
    {
        return $this->service_shortname === $service_shortname;
    }

    #[\Override]
    public function getProject(): \Project
    {
        return $this->project;
    }

    #[\Override]
    public function cannotBeActivated(string $reason): void
    {
        $this->cannot_be_activated = true;
    }
}
