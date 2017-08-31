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

use ForgeConfig;
use TemplateRendererFactory;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\CrossTracker\CrossTrackerPresenter;
use Tuleap\Tracker\CrossTracker\CrossTrackerReportDao;
use Tuleap\Tracker\CrossTracker\CrossTrackerSaver;
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

    public function create(&$request)
    {
        $content_id = $this->getDao()->create();

        return $content_id;
    }

    public function destroy($content_id)
    {
        $this->getDao()->delete($content_id);
    }

    public function getJavascriptDependencies()
    {
        $cross_tracker_include_assets = new IncludeAssets(
            TRACKER_BASE_DIR . '/../www/assets',
            TRACKER_BASE_URL . '/assets'
        );

        return array(
            array('file' => $cross_tracker_include_assets->getFileURL('cross-tracker.js'))
        );
    }

    /**
     * @return CrossTrackerReportDao
     */
    private function getDao()
    {
        return new CrossTrackerReportDao();
    }
}
