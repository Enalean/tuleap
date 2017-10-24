<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\project\Event;

use Project;
use Tuleap\Event\Dispatchable;

class ProjectServiceBeforeActivation implements Dispatchable
{
    const NAME = 'project_service_before_activation';

    /**
     * @var Project
     */
    private $project;

    /**
     * @var string
     */
    private $service_short_name;

    /**
     * @var bool
     */
    private $plugin_set_a_value = false;

    /**
     * @var string
     */
    private $warning_message = '';

    /**
     * @var bool
     */
    private $service_can_be_activated = false;

    public function __construct(Project $project, $service_short_name)
    {
        $this->project            = $project;
        $this->service_short_name = $service_short_name;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function getServiceShortname()
    {
        return $this->service_short_name;
    }

    /**
     * @return bool
     */
    public function doesPluginSetAValue()
    {
        return $this->plugin_set_a_value;
    }

    /**
     * @return bool
     */
    public function canServiceBeActivated()
    {
        return $this->service_can_be_activated;
    }

    /**
     * @return string
     */
    public function getWarningMessage()
    {
        return $this->warning_message;
    }

    public function pluginSetAValue()
    {
        $this->plugin_set_a_value = true;
    }

    public function serviceCanBeActivated()
    {
        $this->service_can_be_activated = true;
    }

    public function setWarningMessage($message)
    {
        $this->warning_message = $message;
    }
}
