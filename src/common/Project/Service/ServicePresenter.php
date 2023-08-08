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

use Project;
use Service;

class ServicePresenter
{
    public string $label;
    /** @var string */
    public $description;
    /** @var int */
    public $id;
    /** @var bool */
    public $is_active;
    /** @var bool */
    public $is_used;
    /** @var string */
    public $scope;
    /** @var int */
    public $rank;
    /** @var bool */
    public $can_be_deleted;
    /** @var string */
    public $short_name;
    /**
     * @var ?string JSON
     */
    public $service_json = null;
    /**
     * @var string
     */
    public $icon;

    public function __construct(
        Service $service,
        ?ServiceJSONPresenter $json_presenter,
    ) {
        $this->id             = $service->getId();
        $this->label          = $service->getProjectAdministrationName();
        $this->description    = $service->getInternationalizedDescription();
        $this->is_active      = $service->isActive();
        $this->is_used        = $service->isUsed();
        $this->scope          = $service->getScope();
        $this->rank           = $service->getRank();
        $this->short_name     = $service->getShortName();
        $this->can_be_deleted = $this->canBeDeleted($service);
        $this->service_json   = json_encode($json_presenter);
        $this->icon           = $service->getIcon();
    }

    private function canBeDeleted(Service $service)
    {
        if ((int) $service->getGroupId() === Project::DEFAULT_TEMPLATE_PROJECT_ID) {
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
