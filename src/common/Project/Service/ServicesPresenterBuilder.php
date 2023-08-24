<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use PFUser;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Service;
use ServiceManager;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

class ServicesPresenterBuilder
{
    private const NONE_SERVICE_ID = 100;

    public function __construct(
        private readonly ServiceManager $service_manager,
        private readonly EventDispatcherInterface $event_manager,
    ) {
    }

    public function build(Project $project, CSRFSynchronizerTokenInterface $csrf, PFUser $user): ServicesPresenter
    {
        $service_presenters = [];
        $allowed_services   = $this->service_manager->getListOfAllowedServicesForProject($project);
        $add_missing        = $this->event_manager->dispatch(
            new AddMissingService($project, $allowed_services)
        );

        foreach ($add_missing->getAllowedServices() as $service) {
            if (! $this->isServiceReadable($service, $user) || $this->isBannedService($service)) {
                continue;
            }

            $service_presenters[] = new ServicePresenter(
                $service,
                $this->buildJSONPresenter($service, $project, $user)
            );
        }

        return new ServicesPresenter($project, $csrf, $service_presenters);
    }

    private function buildJSONPresenter(Service $service, Project $project, PFUser $user): ServiceJSONPresenter
    {
        $service_disabled_collector = $this->isServiceDisabledByPlugin($service, $project, $user);
        return new ServiceJSONPresenter(
            $service->getId(),
            $service->getShortName(),
            $service->getInternationalizedName(),
            $service->getIconName(),
            $service->getUrl(),
            $service->getInternationalizedDescription(),
            $service->isActive(),
            $service->isUsed(),
            $service->isIFrame(),
            $service->isOpenedInNewTab(),
            $service->getRank(),
            $service->getScope() !== Service::SCOPE_SYSTEM,
            $service->urlCanChange(),
            $service_disabled_collector->getReason()
        );
    }

    private function isServiceReadable(Service $service, PFUser $user): bool
    {
        if ((int) $service->getId() === self::NONE_SERVICE_ID) {
            return false;
        }

        if ($user->isSuperUser()) {
            return true;
        }

        $is_service_shown = $this->event_manager->dispatch(new HideServiceInUserInterfaceEvent($service))->isShown();
        if (! $is_service_shown) {
            return false;
        }

        if ($service->isActive()) {
            return true;
        }

        return false;
    }

    private function isBannedService(Service $service): bool
    {
        if ($service->getShortName() === \Service::ADMIN) {
            return true;
        }

        if ($service->getShortName() === \Service::SUMMARY) {
            return true;
        }

        return false;
    }

    private function isServiceDisabledByPlugin(Service $service, Project $project, PFUser $user): ServiceDisabledCollector
    {
        $service_collector = new ServiceDisabledCollector($project, $service->getShortName(), $user);
        $this->event_manager->dispatch($service_collector);

        return $service_collector;
    }
}
