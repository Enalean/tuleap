<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use MVC2_PluginController;
use Codendi_Request;
use Planning_Milestone;
use EventManager;
use Project;
use Tuleap\TestManagement\Breadcrumbs\Breadcrumbs;
use Tuleap\TestManagement\Breadcrumbs\NoCrumb;

abstract class TestManagementController extends MVC2_PluginController
{

    public const NAME = 'testmanagement';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Project
     */
    protected $project;

    /**
     * @var Planning_Milestone
     */
    protected $current_milestone;

    /**
     * @var EventManager
     */
    protected $event_manager;

    public function __construct(
        Codendi_Request $request,
        Config $config,
        EventManager $event_manager
    ) {
        parent::__construct(self::NAME, $request);

        $this->project         = $request->getProject();
        $this->config          = $config;
        $this->event_manager   = $event_manager;

        $event = new \Tuleap\TestManagement\Event\GetMilestone(
            $request->getCurrentUser(),
            (int) $request->getValidated('milestone_id', 'int', 0)
        );
        $this->event_manager->processEvent($event);
        $milestone = $event->getMilestone();
        if (isset($milestone)) {
            $this->current_milestone = $milestone;
        }
    }

    public function getBreadcrumbs(): Breadcrumbs
    {
        return new NoCrumb();
    }

    /**
     * @return string
     */
    protected function getTemplatesDir()
    {
        return TESTMANAGEMENT_BASE_DIR . '/templates';
    }
}
