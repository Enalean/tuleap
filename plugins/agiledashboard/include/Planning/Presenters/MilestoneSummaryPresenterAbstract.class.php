<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;

abstract class Planning_Presenter_MilestoneSummaryPresenterAbstract
{
    /** @var Planning_Milestone */
    public $milestone;

    /** @var string */
    private $plugin_path;

    /** @var string */
    public $has_cardwall;

    public function __construct(Planning_Milestone $milestone, $plugin_path, $has_cardwall)
    {
        $this->milestone    = $milestone;
        $this->plugin_path  = $plugin_path;
        $this->has_cardwall = $has_cardwall;
    }

    public function content()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'details_pane_title');
    }

    public function cardwall()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cardwall');
    }

    public function breadcrumbs()
    {
        $breadcrumbs = [];
        foreach (array_reverse($this->milestone->getAncestors()) as $milestone) {
            $breadcrumbs[] = $this->getMilestoneBreadcrumb($milestone);
        }

        return $breadcrumbs;
    }

    private function getMilestoneBreadcrumb(Planning_Milestone $milestone)
    {
        $hp             = Codendi_HTMLPurifier::instance();
        $tracker        = $milestone->getArtifact()->getTracker();
        $url_parameters = [
            'planning_id' => $milestone->getPlanningId(),
            'pane'        => DetailsPaneInfo::IDENTIFIER,
            'action'      => 'show',
            'group_id'    => $milestone->getGroupId(),
            'aid'         => $milestone->getArtifactId()
        ];

        return [
            'url'          => $this->plugin_path . '/?' . http_build_query($url_parameters),
            'title'        => $hp->purify($milestone->getArtifactTitle()),
            'default_name' => $hp->purify($tracker->getName() . ' #' . $milestone->getArtifactId()),
        ];
    }

    public function milestone_title()
    {
        return $this->milestone->getArtifactTitle();
    }

    abstract public function has_burndown();

    public function planning_id()
    {
        return $this->milestone->getPlanningId();
    }

    public function artifact_id()
    {
        return $this->milestone->getArtifactId();
    }

    public function edit_base_link()
    {
        return '/plugins/tracker/?aid=';
    }
}
