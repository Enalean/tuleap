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

use Tuleap\Project\Event\ProjectServiceBeforeActivation;

final class ServiceAvailabilityProjectServiceBeforeAvailabilityEvent implements ServiceAvailability
{
    public function __construct(private ProjectServiceBeforeActivation $event)
    {
    }

    #[\Override]
    public function isForService(string $service_shortname): bool
    {
        return $this->event->isForService($service_shortname);
    }

    #[\Override]
    public function getProject(): \Project
    {
        return $this->event->getProject();
    }

    #[\Override]
    public function cannotBeActivated(string $reason): void
    {
        $this->event->pluginSetAValue();
        $this->event->setWarningMessage($reason);
    }
}
