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
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRenderer;
use Tracker;
use Tuleap\AgileDashboard\ExplicitBacklog\VerifyProjectUsesExplicitBacklog;
use Tuleap\AgileDashboard\Planning\RetrieveRootPlanning;
use Tuleap\Kanban\SplitKanbanConfigurationChecker;

class AdditionalMasschangeActionBuilder
{
    public function __construct(
        private readonly VerifyProjectUsesExplicitBacklog $explicit_backlog_dao,
        private readonly RetrieveRootPlanning $planning_factory,
        private readonly TemplateRenderer $template_renderer,
        private readonly EventDispatcherInterface $event_dispatcher,
        private readonly SplitKanbanConfigurationChecker $split_kanban_configuration_checker,
    ) {
    }

    public function buildMasschangeAction(Tracker $tracker, PFUser $user): ?string
    {
        if (! $tracker->userIsAdmin($user)) {
            return null;
        }

        $project = $tracker->getProject();

        $block_scrum_access = new \Tuleap\AgileDashboard\BlockScrumAccess($project);
        $this->event_dispatcher->dispatch($block_scrum_access);
        if (! $block_scrum_access->isScrumAccessEnabled()) {
            return null;
        }

        $project_id = (int) $project->getID();
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

        $is_split_feature_flag_enabled = $this->split_kanban_configuration_checker->isProjectAllowedToUseSplitKanban($project);

        $add_to_top_backlog_text    = $is_split_feature_flag_enabled ? dgettext('tuleap-agiledashboard', 'Add to backlog') : dgettext('tuleap-agiledashboard', 'Add to top backlog');
        $remove_to_top_backlog_text = $is_split_feature_flag_enabled ? dgettext('tuleap-agiledashboard', 'Remove from backlog') : dgettext('tuleap-agiledashboard', 'Remove from top backlog');

        return $this->template_renderer->renderToString(
            'explicit-backlog-actions',
            ['add_to_top_backlog' => $add_to_top_backlog_text, 'remove_to_top_backlog' => $remove_to_top_backlog_text]
        );
    }
}
