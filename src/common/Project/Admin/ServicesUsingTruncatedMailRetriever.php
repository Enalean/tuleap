<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin;

use Event;
use EventManager;
use Project;
use Service;

class ServicesUsingTruncatedMailRetriever
{
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    public function getServicesImpactedByTruncatedEmails(Project $project)
    {
        $truncated_mails_impacted_services = [];
        $file_service                      = $project->getService(Service::FILE);
        if ($file_service) {
            $truncated_mails_impacted_services[] = $file_service->getInternationalizedName();
        }

        $svn_service = $project->getService(Service::SVN);
        if ($svn_service) {
            $truncated_mails_impacted_services[] = $svn_service->getInternationalizedName();
        }

        $wiki_service = $project->getService(Service::WIKI);
        if ($wiki_service) {
            $truncated_mails_impacted_services[] = $wiki_service->getInternationalizedName();
        }

        $this->event_manager->processEvent(
            Event::SERVICES_TRUNCATED_EMAILS,
            [
                'project'  => $project,
                'services' => &$truncated_mails_impacted_services
            ]
        );

        return $truncated_mails_impacted_services;
    }
}
