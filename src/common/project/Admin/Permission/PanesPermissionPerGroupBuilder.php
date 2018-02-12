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

namespace Tuleap\Project\Admin\Permission;

use EventManager;
use Project;
use Tuleap\FRS\PerGroup\PermissionPerGroupPaneBuilder;
use Tuleap\PHPWiki\PerGroup\PHPWikiPermissionPerGroupPaneBuilder;

class PanesPermissionPerGroupBuilder
{
    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var PermissionPerGroupPaneBuilder
     */
    private $frs_pane_builder;

    /**
     * @var PHPWikiPermissionPerGroupPaneBuilder
     */
    private $phpwiki_pane_builder;

    public function __construct(
        EventManager $event_manager,
        PermissionPerGroupPaneBuilder $frs_pane_builder,
        PHPWikiPermissionPerGroupPaneBuilder $phpwiki_pane_builder
    ) {
        $this->event_manager        = $event_manager;
        $this->frs_pane_builder     = $frs_pane_builder;
        $this->phpwiki_pane_builder = $phpwiki_pane_builder;
    }

    /**
     * @return string[]
     */
    public function getPanes(Project $project, $selected_ugroup)
    {
        $event = new PermissionPerGroupPaneCollector($project, $selected_ugroup);
        $this->event_manager->processEvent($event);

        $panes = $event->getPanes();
        $this->addCorePanes($project, $panes, $selected_ugroup);

        return $panes;
    }

    private function addCorePanes(Project $project, array &$panes, $selected_ugroup)
    {
        $panes[] = $this->frs_pane_builder->getPaneContent($project, $selected_ugroup);
        $panes[] = $this->phpwiki_pane_builder->getPaneContent($project, $selected_ugroup);
    }
}
