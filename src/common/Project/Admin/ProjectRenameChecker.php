<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Admin;

use EventManager;
use Project;
use Tuleap\Project\Event\ProjectUnixNameIsEditable;

class ProjectRenameChecker
{
    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var string
     */
    private $message = '';

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    public function isProjectUnixNameEditable(Project $project): bool
    {
        $project_unix_name_is_editable = new ProjectUnixNameIsEditable($project);
        $this->event_manager->processEvent($project_unix_name_is_editable);

        $this->message = $project_unix_name_is_editable->getMessage();

        return $project_unix_name_is_editable->isEditable();
    }

    public function getNotEditableNameReasons(): string
    {
        return $this->message;
    }
}
