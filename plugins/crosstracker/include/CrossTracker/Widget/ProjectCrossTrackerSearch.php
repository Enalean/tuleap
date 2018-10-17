<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

use Codendi_Request;
use HTTPRequest;
use Project;
use TemplateRendererFactory;
use Tuleap\Layout\IncludeAssets;
use Tuleap\CrossTracker\CrossTrackerReportDao;
use Widget;

class ProjectCrossTrackerSearch extends Widget
{
    const NAME = 'crosstrackersearch';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function loadContent($id)
    {
        $this->content_id = $id;
    }

    public function getContent()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(
            CROSSTRACKER_BASE_DIR . '/templates/widgets'
        );

        $request = HTTPRequest::instance();

        return $renderer->renderToString(
            'project-cross-tracker-search',
            new ProjectCrossTrackerSearchPresenter(
                $this->content_id,
                $request->getCurrentUser()
            )
        );
    }

    public function getDescription()
    {
        return dgettext('tuleap-crosstracker', 'Search into multiple trackers and multiple projects.');
    }

    public function getIcon()
    {
        return "fa-list-ul";
    }

    public function getTitle()
    {
        return dgettext('tuleap-crosstracker', 'Cross trackers search');
    }

    public function getCategory()
    {
        return 'trackers';
    }

    public function isUnique()
    {
        return false;
    }

    public function create(Codendi_Request $request)
    {
        $content_id = $this->getDao()->create();

        return $content_id;
    }

    public function destroy($content_id)
    {
        $this->getDao()->delete($content_id);
    }

    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $new_project_id,
        $owner_type
    ) {
        $content_id      = $this->getDao()->create();
        $tracker_factory = $this->getTrackerFactory();

        $trackers_existing_widget = $this->getTrackers($id);
        $trackers_new_widget      = array();

        foreach ($trackers_existing_widget as $tracker) {
            if ($this->owner_id == $tracker->getGroupId()) {
                $trackers_new_widget[] = $tracker_factory->getTrackerByShortnameAndProjectId(
                    $tracker->getItemName(),
                    $new_project_id
                );
            } else {
                $trackers_new_widget[] = $tracker;
            }
        }

        $this->getDao()->addTrackersToReport($trackers_new_widget, $content_id);

        return $content_id;
    }

    /**
     * @return \Tracker[]
     */
    private function getTrackers($report_id)
    {
        $tracker_factory = $this->getTrackerFactory();
        $tracker_rows    = $this->getDao()->searchReportTrackersById($report_id);
        $trackers        = array();
        foreach ($tracker_rows as $row) {
            $tracker = $tracker_factory->getTrackerById($row['tracker_id']);
            if ($tracker !== null) {
                $trackers[] = $tracker;
            }
        }
        return $trackers;
    }

    /**
     * @return \TrackerFactory
     */
    private function getTrackerFactory()
    {
        return \TrackerFactory::instance();
    }

    public function getJavascriptDependencies()
    {
        $cross_tracker_include_assets = new IncludeAssets(
            __DIR__ . '/../../../../../src/www/assets/crosstracker/scripts',
            '/assets/crosstracker/scripts'
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
