<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin;

use Tracker;
use Tuleap\Tracker\Workflow\WorkflowMenuPresenter;

class HeaderPresenter
{
    public $tracker_id;
    public $tracker_name;
    public $additional_items;

    /**
     * @var bool
     */
    public $is_fields_tab_active = false;

    /**
     * @var bool
     */
    public $is_semantics_tab_active = false;

    /**
     * @var bool
     */
    public $is_permissions_tab_active = false;

    /**
     * @var bool
     */
    public $is_workflow_tab_active = false;

    /**
     * @var bool
     */
    public $is_notification_tab_active = false;

    /**
     * @var bool
     */
    public $is_other_tab_active = false;

    public function __construct(
        Tracker $tracker,
        string $current_item,
        array $additional_items,
        public readonly WorkflowMenuPresenter $workflow_menu,
    ) {
        $this->tracker_id       = $tracker->getId();
        $this->tracker_name     = $tracker->getName();
        $this->additional_items = $additional_items;

        $this->defineActiveTabBasedOnItem($current_item);
    }

    private function defineActiveTabBasedOnItem(string $current_item)
    {
        if ($current_item === 'editformElements' || $current_item === 'dependencies') {
            $this->is_fields_tab_active = true;
        } elseif ($current_item === 'editsemantic') {
            $this->is_semantics_tab_active = true;
        } elseif ($current_item === 'editperms') {
            $this->is_permissions_tab_active = true;
        } elseif ($current_item === 'editworkflow') {
            $this->is_workflow_tab_active = true;
        } elseif ($current_item === 'editnotifications') {
            $this->is_notification_tab_active = true;
        } else {
            $this->is_other_tab_active = true;
        }
    }
}
