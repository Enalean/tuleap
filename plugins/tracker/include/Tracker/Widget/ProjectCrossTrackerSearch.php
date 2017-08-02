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


namespace Tuleap\Tracker\Widget;

use TemplateRendererFactory;
use Tuleap\Tracker\CrossTracker\CrossTrackerPresenter;
use Widget;

class ProjectCrossTrackerSearch extends Widget
{
    const NAME = 'crosstrackersearch';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function getContent()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(
            TRACKER_TEMPLATE_DIR . '/widgets'
        );

        $cross_tracker_presenter = new CrossTrackerPresenter($this->getCurrentUser());

        return $renderer->renderToString(
            'project-cross-tracker-search',
            new ProjectCrossTrackerSearchPresenter(
                $cross_tracker_presenter
            )
        );
    }

    public function getDescription()
    {
        return dgettext('tuleap-tracker', 'Search into multiple trackers and multiple projects.');
    }

    public function getIcon()
    {
        return "fa-list";
    }

    public function getTitle()
    {
        return dgettext('tuleap-tracker', 'Cross tracker search');
    }

    public function getCategory()
    {
        return 'trackers';
    }

    public function isUnique()
    {
        return false;
    }
}
