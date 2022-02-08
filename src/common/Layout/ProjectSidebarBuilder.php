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

namespace Tuleap\Layout;

use Codendi_HTMLPurifier;
use Event;
use EventManager;
use ForgeConfig;
use PFUser;
use Project;
use ProjectManager;
use Tuleap\Project\Service\ProjectDefinedService;
use Tuleap\Sanitizer\URISanitizer;
use Tuleap\ServerHostname;

class ProjectSidebarBuilder
{
    private EventManager $event_manager;
    private ProjectManager $project_manager;
    private Codendi_HTMLPurifier $purifier;
    private URISanitizer $uri_sanitizer;

    public function __construct(
        EventManager $event_manager,
        ProjectManager $project_manager,
        Codendi_HTMLPurifier $purifier,
        URISanitizer $uri_sanitizer,
    ) {
        $this->event_manager   = $event_manager;
        $this->project_manager = $project_manager;
        $this->purifier        = $purifier;
        $this->uri_sanitizer   = $uri_sanitizer;
    }

    public function getSidebar(PFUser $user, $toptab, Project $project): \Generator
    {
        $allowed_services = $this->getAllowedServicesForUser($user, $project);

        foreach ($project->getServices() as $service) {
            if (! $this->canServiceBeAddedInSidebar($project, $user, $service, $allowed_services)) {
                continue;
            }

            if ($service instanceof ProjectDefinedService) {
                yield new SidebarProjectDefinedServicePresenter($service, $this->getLink($service, $project));
            } else {
                yield new SidebarServicePresenter(
                    $this->purifier->purify('sidebar-' . $service->getShortName()),
                    $this->purifier->purify($service->getInternationalizedName()),
                    $this->getLink($service, $project),
                    $service->getIcon(),
                    $this->getLabel($service),
                    $this->purifier->purify($service->getInternationalizedDescription()),
                    $this->isEnabled($toptab, $service)
                );
            }
        }
    }

    private function restrictedMemberIsNotProjectMember(PFUser $user, Project $project)
    {
        return $user->isRestricted() && ! $user->isMember($project->getID());
    }

    private function isProjectSuperPublic(Project $project)
    {
        $projects = ForgeConfig::getSuperPublicProjectsFromRestrictedFile();

        return in_array($project->getID(), $projects);
    }

    /** @return string[] */
    private function getAllowedServicesForUser(PFUser $user, Project $project)
    {
        $allowed_services = ['summary'];
        if ($this->restrictedMemberIsNotProjectMember($user, $project)) {
            $this->event_manager->processEvent(
                Event::GET_SERVICES_ALLOWED_FOR_RESTRICTED,
                [
                    'allowed_services' => &$allowed_services,
                ]
            );
        }

        return $allowed_services;
    }

    private function canServiceBeAddedInSidebar(
        Project $project,
        PFUser $user,
        \Service $service,
        array $allowed_services,
    ): bool {
        $short_name = $service->getShortName();

        if (! $service->isUsed()) {
            return false;
        }
        if (! $service->isActive()) {
            return false;
        }

        if ($short_name === \Service::SUMMARY) {
            return false;
        }

        if ($short_name === \Service::ADMIN) {
            return false;
        }

        if (
            ! $this->isProjectSuperPublic($project)
            && $this->restrictedMemberIsNotProjectMember($user, $project)
            && ! in_array($short_name, $allowed_services)
        ) {
            return false;
        }

        return true;
    }

    private function getLink(\Service $service, Project $project): string
    {
        $project_id = $project->getID();

        if ($service->isIFrame()) {
            $link = '/service/?group_id=' . $project_id . '&amp;id=' . $service->getId();
        } else {
            $service_url_collector = new ServiceUrlCollector($project, $service->getShortName());

            $this->event_manager->processEvent($service_url_collector);

            if ($service_url_collector->hasUrl()) {
                $link = $service_url_collector->getUrl();
            } else {
                $link = $this->purifier->purify($service->getUrl());
            }
        }
        if ($project_id == 100) {
            if (strpos($link, '$projectname') !== false) {
                // NOTE: if you change link variables here, change them also in
                // * src/common/Project/RegisterProjectStep_Confirmation.class.php
                // * @see ServicePOSTDataBuilder::substituteVariablesInLink
                // Don't check project name if not needed.
                // When it is done here, the service bar will not appear updated on the current page
                $link = str_replace(
                    '$projectname',
                    $this->project_manager->getProject($project_id)->getUnixName(),
                    $link
                );
            }
            $link = str_replace('$sys_default_domain', ServerHostname::hostnameWithHTTPSPort(), $link);
            $link = str_replace('$sys_default_protocol', 'https', $link);
            $link = str_replace('$group_id', $project_id, $link);
        }

        return $this->uri_sanitizer->sanitizeForHTMLAttribute($link);
    }

    private function isEnabled($toptab, \Service $service): bool
    {
        return (is_numeric($toptab) && $toptab == $service->getId())
            || ($service->getShortName() && ($toptab == $service->getShortName()));
    }

    private function getLabel(\Service $service): string
    {
        $label  = '<span title="' . $this->purifier->purify($service->getInternationalizedDescription()) . '">';
        $label .= $this->purifier->purify($service->getInternationalizedName()) . '</span>';

        return $label;
    }
}
