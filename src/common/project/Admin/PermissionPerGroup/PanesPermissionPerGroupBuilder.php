<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Project\Admin\PermissionsPerGroup;

use EventManager;
use PFUser;
use Project;
use Service;
use Tuleap\FRS\PermissionsPerGroup\PaneCollector;
use Tuleap\News\Admin\PermissionsPerGroup\NewsPermissionPerGroupPaneBuilder;
use Tuleap\PHPWiki\PermissionsPerGroup\PHPWikiPermissionPerGroupPaneBuilder;

class PanesPermissionPerGroupBuilder
{
    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var PHPWikiPermissionPerGroupPaneBuilder
     */
    private $phpwiki_pane_builder;
    /**
     * @var PaneCollector
     */
    private $pane_collector;

    /**
     * @var NewsPermissionPerGroupPaneBuilder
     */
    private $news_pane_builder;

    public function __construct(
        EventManager $event_manager,
        PaneCollector $pane_collector,
        PHPWikiPermissionPerGroupPaneBuilder $phpwiki_pane_builder,
        NewsPermissionPerGroupPaneBuilder $news_pane_builder
    ) {
        $this->event_manager        = $event_manager;
        $this->phpwiki_pane_builder = $phpwiki_pane_builder;
        $this->pane_collector       = $pane_collector;
        $this->news_pane_builder    = $news_pane_builder;
    }

    /**
     * @return string[]
     */
    public function getSortedPanes(Project $project, PFUser $user, $selected_ugroup)
    {
        $event = new PermissionPerGroupPaneCollector($project, $selected_ugroup);
        $this->event_manager->processEvent($event);

        $panes = $event->getPanes();
        $this->addCorePanes($project, $panes, $user, $selected_ugroup);

        return $this->sortPanesByServiceRank($panes);
    }

    private function sortPanesByServiceRank(array $panes)
    {
        ksort($panes);

        return array_values($panes);
    }

    private function addCorePanes(Project $project, array &$panes, PFUser $user, $selected_ugroup)
    {
        $frs_pane = $this->pane_collector->collectPane($project, $user, $selected_ugroup);
        if ($frs_pane) {
            $rank_in_project           = $project->getService(Service::FILE)->getRank();
            $panes[ $rank_in_project ] = $frs_pane;
        }

        $phpwiki_pane =  $this->phpwiki_pane_builder->getPaneContent($project, $selected_ugroup);
        if ($phpwiki_pane) {
            $rank_in_project           = $project->getService(Service::WIKI)->getRank();
            $panes[ $rank_in_project ] = $phpwiki_pane;
        }

        $news_pane = $this->news_pane_builder->getPaneContent($project, $selected_ugroup);
        if ($news_pane) {
            $rank_in_project           = $project->getService(Service::NEWS)->getRank();
            $panes[ $rank_in_project ] = $news_pane;
        }
    }
}
