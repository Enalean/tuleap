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

use CSRFSynchronizerToken;
use PFUser;
use Project;
use Service;
use ServiceManager;
use Tuleap\Layout\ServiceUrlCollector;

class ServicesPresenterBuilder
{
    private const NONE_SERVICE_ID = 100;

    public function __construct(private ServiceManager $service_manager, private \EventManager $event_manager)
    {
    }

    public function build(Project $project, CSRFSynchronizerToken $csrf, PFUser $user): ServicesPresenter
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
        $service_link         = $this->getServiceLink($service, $project);
        $is_link_customizable = $service_link === null;

        $service_disabled_collector = $this->isServiceDisabledByPlugin($service, $project, $user);
        return new ServiceJSONPresenter(
            $service->getId(),
            $service->getShortName(),
            $service->getInternationalizedName(),
            $service->getIconName(),
            $service->getUrl($service_link),
            $service->getInternationalizedDescription(),
            $service->isActive(),
            $service->isUsed(),
            $service->isIFrame(),
            $service->isOpenedInNewTab(),
            $service->getRank(),
            $service->getScope() !== Service::SCOPE_SYSTEM,
            $is_link_customizable,
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

        return $service->isActive();
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

    private function getServiceLink(Service $service, Project $project): ?string
    {
        $service_url_collector = new ServiceUrlCollector($project, $service->getShortName());

        $this->event_manager->processEvent($service_url_collector);

        return $service_url_collector->getUrl();
    }

    private function isServiceDisabledByPlugin(Service $service, Project $project, PFUser $user): ServiceDisabledCollector
    {
        $service_collector = new ServiceDisabledCollector($project, $service->getShortName(), $user);
        $this->event_manager->processEvent($service_collector);

        return $service_collector;
    }
}
