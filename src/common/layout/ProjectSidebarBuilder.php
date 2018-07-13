<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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
use PermissionsOverrider_PermissionsOverriderManager;
use PFUser;
use Project;
use ProjectManager;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Sanitizer\URISanitizer;

class ProjectSidebarBuilder
{
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var PermissionsOverrider_PermissionsOverriderManager
     */
    private $permission_overrider;
    /**
     * @var Codendi_HTMLPurifier
     */
    private $purifier;
    /**
     * @var URISanitizer
     */
    private $uri_sanitizer;
    /**
     * @var MembershipDelegationDao
     */
    private $membership_delegation_dao;

    public function __construct(
        EventManager $event_manager,
        ProjectManager $project_manager,
        PermissionsOverrider_PermissionsOverriderManager $permission_overrider,
        Codendi_HTMLPurifier $purifier,
        URISanitizer $uri_sanitizer,
        MembershipDelegationDao $membership_delegation_dao
    ) {
        $this->event_manager             = $event_manager;
        $this->project_manager           = $project_manager;
        $this->permission_overrider      = $permission_overrider;
        $this->purifier                  = $purifier;
        $this->uri_sanitizer             = $uri_sanitizer;
        $this->membership_delegation_dao = $membership_delegation_dao;
    }

    /** @return array[] Array of sidebar entries */
    public function getSidebar(PFUser $user, $toptab, Project $project)
    {
        $sidebar          = array();
        $allowed_services = $this->getAllowedServicesForUser($user, $project);

        foreach ($project->getServicesData() as $short_name => $service_data) {
            if (! $this->canServiceBeAddedInSidebar($project, $user, $short_name, $service_data, $allowed_services)) {
                continue;
            }

            $sidebar[] = array(
                'link'        => $this->getLink($service_data, $project),
                'icon'        => $this->getIcon($short_name, $service_data),
                'name'        => $this->purifier->purify($service_data['label']),
                'label'       => $this->getLabel($service_data),
                'enabled'     => $this->isEnabled($toptab, $service_data, $short_name),
                'description' => $this->purifier->purify($service_data['description']),
                'id'          => $this->purifier->purify('sidebar-' . $short_name)
            );
        }

        return $sidebar;
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

    private function getIcon($service_name, $service_data)
    {
        if (isset($service_data['icon'])) {
            return $service_data['icon'];
        }

        return 'tuleap-services-angle-double-right tuleap-services-' . $service_name;
    }

    /** @return string[] */
    private function getAllowedServicesForUser(PFUser $user, Project $project)
    {
        $allowed_services = array('summary');
        if ($this->restrictedMemberIsNotProjectMember($user, $project)) {
            $this->event_manager->processEvent(
                Event::GET_SERVICES_ALLOWED_FOR_RESTRICTED,
                array(
                    'allowed_services' => &$allowed_services,
                )
            );
        }

        return $allowed_services;
    }

    private function canServiceBeAddedInSidebar(
        Project $project,
        PFUser $user,
        $short_name,
        array $service_data,
        array $allowed_services
    ) {
        if (! $service_data['is_used']) {
            return false;
        }
        if (! $service_data['is_active']) {
            return false;
        }

        if ((string)$short_name === "summary") {
            return false;
        }

        if ((string)$short_name === "admin") {
            if (! $this->userCanSeeAdminService($project, $user)) {
                return false;
            }
        }

        if (! $this->isProjectSuperPublic($project)
            && $this->restrictedMemberIsNotProjectMember($user, $project)
            && ! $this->permission_overrider->doesOverriderAllowUserToAccessProject($user, $project)
            && ! in_array($short_name, $allowed_services)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    private function getLink(array $service_data, Project $project)
    {
        $project_id = $project->getID();

        if ($service_data['is_in_iframe']) {
            $link = '/service/?group_id=' . $project_id . '&amp;id=' . $service_data['service_id'];
        } else {
            $service_url_collector = new ServiceUrlCollector($project, $service_data['short_name']);

            $this->event_manager->processEvent($service_url_collector);

            if ($service_url_collector->hasUrl()) {
                $link = $service_url_collector->getUrl();
            } else {
                $link = $this->purifier->purify($service_data['link']);
            }
        }
        if ($project_id == 100) {
            if (strpos($link, '$projectname') !== false) {
                // NOTE: if you change link variables here, change them also in
                // * src/common/project/RegisterProjectStep_Confirmation.class.php
                // * src/www/project/admin/servicebar.php
                // Don't check project name if not needed.
                // When it is done here, the service bar will not appear updated on the current page
                $link = str_replace(
                    '$projectname',
                    $this->project_manager->getProject($project_id)->getUnixName(),
                    $link
                );
            }
            $link = str_replace('$sys_default_domain', ForgeConfig::get('sys_default_domain'), $link);
            $sys_default_protocol = 'http';
            if (ForgeConfig::get('sys_https_host')) {
                $sys_default_protocol = 'https';
            }
            $link = str_replace('$sys_default_protocol', $sys_default_protocol, $link);
            $link = str_replace('$group_id', $project_id, $link);
        }

        return $this->uri_sanitizer->sanitizeForHTMLAttribute($link);
    }

    /**
     * @return bool
     */
    private function isEnabled($toptab, array $service_data, $short_name)
    {
        return (is_numeric($toptab) && $toptab == $service_data['service_id'])
            || ($short_name && ($toptab == $short_name));
    }

    /**
     * @return string
     */
    private function getLabel(array $service_data)
    {
        $label = '<span title="' . $this->purifier->purify($service_data['description']) . '">';
        $label .= $this->purifier->purify($service_data['label']) . '</span>';

        return $label;
    }

    private function userCanSeeAdminService(Project $project, PFUser $user)
    {
        if (! $user->isLoggedIn()) {
            return false;
        }

        return $user->isSuperUser()
            || $user->isMember($project->getID(), 'A')
            || $this->membership_delegation_dao->doesUserHasMembershipDelegation($user->getId(), $project->getID());
    }
}
