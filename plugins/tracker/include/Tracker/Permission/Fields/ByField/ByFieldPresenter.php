<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Permission\Fields\ByField;

use Tuleap\Tracker\Permission\Fields\ByGroup\ByGroupController;
use Tuleap\Tracker\Permission\Fields\PermissionsOnFieldsUpdateController;

class ByFieldPresenter
{
    /**
     * @var string
     */
    public $self_url;
    /**
     * @var string
     */
    public $update_url;
    /**
     * @var int
     */
    public $selected_id;
    /**
     * @var string
     */
    public $by_group_url;
    /**
     * @var ByFieldOneUGroupPresenter[]
     */
    public $ugroup_list;
    /**
     * @var bool
     */
    public $might_not_have_access;
    /**
     * @var string
     */
    public $tracker_url;
    /**
     * @var int
     */
    public $project_id;
    /**
     * @var bool
     */
    public $has_permissions;

    public function __construct(\Tracker $tracker, int $selected_id, array $ugroup_list, bool $might_not_have_access)
    {
        $this->self_url              = ByFieldController::getUrl($tracker);
        $this->update_url            = PermissionsOnFieldsUpdateController::getUrl($tracker);
        $this->by_group_url          = ByGroupController::getUrl($tracker);
        $this->tracker_url           = TRACKER_BASE_URL . '?tracker=' . $tracker->getId();
        $this->selected_id           = $selected_id;
        $this->ugroup_list           = $ugroup_list;
        $this->might_not_have_access = $might_not_have_access;
        $this->project_id            = $tracker->getGroupId();
        $this->has_permissions       = count($ugroup_list) >= 1;
    }
}
