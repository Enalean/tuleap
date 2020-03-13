<?php
/**
 * Copyright (c) Enalean 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Masschange;

use PFUser;
use PlanningFactory;
use TemplateRenderer;
use Tracker;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;

class AdditionalMasschangeActionBuilder
{
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var TemplateRenderer
     */
    private $template_renderer;

    public function __construct(
        ExplicitBacklogDao $explicit_backlog_dao,
        PlanningFactory $planning_factory,
        TemplateRenderer $template_renderer
    ) {
        $this->explicit_backlog_dao = $explicit_backlog_dao;
        $this->planning_factory     = $planning_factory;
        $this->template_renderer    = $template_renderer;
    }

    public function buildMasschangeAction(Tracker $tracker, PFUser $user): ?string
    {
        if (! $tracker->userIsAdmin($user)) {
            return null;
        }

        $project_id = (int) $tracker->getProject()->getID();
        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id) === false) {
            return null;
        }

        $root_planning = $this->planning_factory->getRootPlanning($user, $project_id);
        if (! $root_planning) {
            return null;
        }

        if (! in_array($tracker->getId(), $root_planning->getBacklogTrackersIds())) {
            return null;
        }

        return $this->template_renderer->renderToString(
            'explicit-backlog-actions',
            []
        );
    }
}
