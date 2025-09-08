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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Events;

use Tuleap\ProgramManagement\Adapter\Workspace\ProjectProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Events\ServiceDisabledCollectorEvent;
use Tuleap\ProgramManagement\Domain\Workspace\ProjectIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Project\Service\ServiceDisabledCollector;

final class ServiceDisabledCollectorProxy implements ServiceDisabledCollectorEvent
{
    private function __construct(
        private ServiceDisabledCollector $event,
        private ProjectIdentifier $project_identifier,
        private UserIdentifier $user_identifier,
    ) {
    }

    public static function fromEvent(ServiceDisabledCollector $event): self
    {
        return new self($event, ProjectProxy::buildFromProject($event->getProject()), UserProxy::buildFromPFUser($event->getUser()));
    }

    #[\Override]
    public function isForServiceShortName(string $service): bool
    {
        return $this->event->isForService($service);
    }

    #[\Override]
    public function getProjectIdentifier(): ProjectIdentifier
    {
        return $this->project_identifier;
    }

    #[\Override]
    public function disableWithMessage(string $message): void
    {
        $this->event->setIsDisabled($message);
    }

    #[\Override]
    public function getUserIdentifier(): UserIdentifier
    {
        return $this->user_identifier;
    }
}
