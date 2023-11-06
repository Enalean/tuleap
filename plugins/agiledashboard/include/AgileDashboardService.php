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

namespace Tuleap\AgileDashboard;

use BackendLogger;
use PFUser;
use Planning_MilestoneFactory;
use PlanningFactory;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\Milestone\Sidebar\MilestoneDao;
use Tuleap\AgileDashboard\Milestone\Sidebar\AgileDashboardPromotedMilestonesRetriever;
use Tuleap\AgileDashboard\Milestone\Sidebar\MilestonesInSidebarDao;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\Layout\SidebarPromotedItemPresenter;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

class AgileDashboardService extends \Service
{
    public function getIconName(): string
    {
        return 'fa-solid fa-tlp-backlog';
    }

    public function getInternationalizedName(): string
    {
        return dgettext('tuleap-agiledashboard', 'Backlog');
    }

    public function getProjectAdministrationName(): string
    {
        return dgettext('tuleap-agiledashboard', 'Backlog');
    }

    public function getInternationalizedDescription(): string
    {
        return dgettext('tuleap-agiledashboard', 'Backlog');
    }

    public function urlCanChange(): bool
    {
        return false;
    }

    public function getUrl(?string $url = null): string
    {
        return AgileDashboardServiceHomepageUrlBuilder::getTopBacklogUrl($this->project);
    }

    /**
     * @return list<SidebarPromotedItemPresenter>
     */
    public function getPromotedItemPresenters(PFUser $user, ?string $active_promoted_item_id): array
    {
        $planning_factory = PlanningFactory::build();

        return (new AgileDashboardPromotedMilestonesRetriever(
            Planning_MilestoneFactory::build(),
            new MilestoneDao(),
            $this->project,
            new MilestonesInSidebarDao(),
            Tracker_ArtifactFactory::instance(),
            $planning_factory,
            new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), $planning_factory),
            SemanticTimeframeBuilder::build(),
            BackendLogger::getDefaultLogger()
        ))->getSidebarPromotedMilestones($user);
    }

    public function getSidebarInfoTooltip(): string
    {
        if ((new MilestonesInSidebarDao())->shouldSidebarDisplayLastMilestones((int) $this->project->getID())) {
            return \dgettext(
                'tuleap-agiledashboard',
                'Sidebar shows only the last 5 current milestones',
            );
        }

        return parent::getSidebarInfoTooltip();
    }
}
