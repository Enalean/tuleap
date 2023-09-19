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

use ForgeConfig;
use PFUser;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Project\Service\HideServiceInUserInterfaceEvent;
use Tuleap\Project\Service\CollectServicesAllowedForRestrictedEvent;
use Tuleap\Project\Service\ProjectDefinedService;
use Tuleap\Project\Service\UserCanAccessToServiceEvent;
use Tuleap\Sanitizer\URISanitizer;
use Tuleap\ServerHostname;

class ProjectSidebarToolsBuilder
{
    public function __construct(
        private EventDispatcherInterface $event_manager,
        private \ProjectManager $project_manager,
        private URISanitizer $uri_sanitizer,
    ) {
    }

    /**
     * @return \Generator<int, SidebarServicePresenter>
     */
    public function getSidebarTools(PFUser $user, $toptab, Project $project): \Generator
    {
        $allowed_services = $this->getAllowedServicesForUser($user, $project);

        foreach ($project->getServices() as $service) {
            if (! $this->canServiceBeAddedInSidebar($project, $user, $service, $allowed_services)) {
                continue;
            }

            $href = $this->getLink($service, $project);
            if ($service instanceof ProjectDefinedService) {
                yield SidebarServicePresenter::fromProjectDefinedService($service, $href, $user);
            } else {
                yield SidebarServicePresenter::fromService($service, $href, $this->isEnabled($toptab, $service), $user);
            }
        }
    }

    private function restrictedMemberIsNotProjectMember(PFUser $user, Project $project): bool
    {
        return $user->isRestricted() && ! $user->isMember($project->getID());
    }

    private function isProjectSuperPublic(Project $project): bool
    {
        $projects = ForgeConfig::getSuperPublicProjectsFromRestrictedFile();

        return in_array($project->getID(), $projects);
    }

    private function getAllowedServicesForUser(PFUser $user, Project $project): CollectServicesAllowedForRestrictedEvent
    {
        $event = new CollectServicesAllowedForRestrictedEvent();
        if ($this->restrictedMemberIsNotProjectMember($user, $project)) {
            $this->event_manager->dispatch($event);
        }
        return $event;
    }

    private function canServiceBeAddedInSidebar(
        Project $project,
        PFUser $user,
        \Service $service,
        CollectServicesAllowedForRestrictedEvent $allowed_services,
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
            && ! $allowed_services->isServiceShortnameAllowed($short_name)
        ) {
            return false;
        }

        $is_permission_allowed_for_user = $this->event_manager
            ->dispatch(new UserCanAccessToServiceEvent($service, $user))
            ->isAllowed();
        if (! $is_permission_allowed_for_user) {
            return false;
        }
        return $this->event_manager->dispatch(new HideServiceInUserInterfaceEvent($service))->isShown();
    }

    private function getLink(\Service $service, Project $project): string
    {
        $project_id = $project->getID();

        if ($service->isIFrame()) {
            $link = '/service/?group_id=' . urlencode((string) $project_id) . '&id=' . urlencode((string) $service->getId());
        } else {
            $link = $service->getUrl();
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

        $href = $this->uri_sanitizer->sanitizeForHTMLAttribute($link);
        if (preg_match('#^[a-zA-Z]*://#', $href)) {
            return $href;
        }
        return ServerHostname::HTTPSUrl() . $href;
    }

    private function isEnabled($toptab, \Service $service): bool
    {
        return (is_numeric($toptab) && $toptab == $service->getId())
            || ($service->getShortName() && ($toptab == $service->getShortName()));
    }
}
