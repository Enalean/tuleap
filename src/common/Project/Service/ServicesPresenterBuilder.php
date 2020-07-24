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

    /**
     * @var ServiceManager
     */
    private $service_manager;
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(ServiceManager $service_manager, \EventManager $event_manager)
    {
        $this->service_manager = $service_manager;
        $this->event_manager   = $event_manager;
    }

    public function build(Project $project, CSRFSynchronizerToken $csrf, PFUser $user): ServicesPresenter
    {
        $service_presenters = [];
        $allowed_services = $this->service_manager->getListOfAllowedServicesForProject($project);
        foreach ($allowed_services as $service) {
            if (! $this->isServiceReadable($service, $user)) {
                continue;
            }

            $service_presenters[] = new ServicePresenter(
                $service,
                $this->buildJSONPresenter($service, $project)
            );
        }

        return new ServicesPresenter($project, $csrf, $service_presenters);
    }

    private function buildJSONPresenter(Service $service, Project $project): ServiceJSONPresenter
    {
        $service_link = $this->getServiceLink($service, $project);
        $is_link_customizable = $service_link === null;
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
            $is_link_customizable
        );
    }

    private function isServiceReadable(Service $service, PFUser $user)
    {
        if ((int) $service->getId() === self::NONE_SERVICE_ID) {
            return false;
        }

        if ($user->isSuperUser()) {
            return true;
        }

        return $service->isActive();
    }

    private function getServiceLink(Service $service, Project $project)
    {
        $service_url_collector = new ServiceUrlCollector($project, $service->getShortName());

        $this->event_manager->processEvent($service_url_collector);

        return $service_url_collector->getUrl();
    }
}
