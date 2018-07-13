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

namespace Tuleap\Project\Service;

use Project;
use Service;

class ServicePresenter
{
    public $label;
    public $description;
    public $id;
    public $is_active;
    public $is_used;
    public $scope;
    public $rank;
    public $can_be_deleted;
    public $short_name;
    public $is_read_only;
    public $can_see_shortname;
    public $is_scope_project;
    public $can_update_is_active;
    public $link;
    public $is_summary;
    public $is_in_iframe;
    public $is_link_customizable;

    /**
     * ServicePresenter constructor.
     *
     * @param Service $service
     * @param         $is_read_only
     * @param         $can_see_shortname
     * @param         $is_scope_project
     * @param         $can_update_is_active
     * @param         $is_link_customizable
     * @param         $service_link
     */
    public function __construct(
        Service $service,
        $is_read_only,
        $can_see_shortname,
        $is_scope_project,
        $can_update_is_active,
        $service_link
    ) {
        $this->id                   = $service->getId();
        $this->label                = $service->getInternationalizedName();
        $this->description          = $service->getInternationalizedDescription();
        $this->is_active            = $service->isActive();
        $this->is_in_iframe         = $service->isIFrame();
        $this->is_used              = $service->isUsed();
        $this->scope                = $service->getScope();
        $this->rank                 = $service->getRank();
        $this->short_name           = $service->getShortName();
        $this->link                 = $service->getUrl($service_link);
        $this->can_be_deleted       = $this->canBeDeleted($service);
        $this->is_read_only         = $is_read_only;
        $this->can_see_shortname    = $can_see_shortname;
        $this->is_scope_project     = $is_scope_project;
        $this->can_update_is_active = $can_update_is_active;
        $this->is_summary           = $service->getShortName() === 'summary';
        $this->is_link_customizable = $service_link === null;
    }

    private function canBeDeleted(Service $service)
    {
        if ((int)$service->getGroupId() === Project::ADMIN_PROJECT_ID) {
            return true;
        }

        if ($service->getScope() === Service::SCOPE_SYSTEM) {
            return false;
        }

        return ! $this->isLegacyHomePageService($service);
    }

    private function isLegacyHomePageService(Service $service)
    {
        $home_page_label = $GLOBALS['Language']->getText('project_admin_servicebar', 'home_page');

        return $service->getInternationalizedName() === $home_page_label;
    }
}
