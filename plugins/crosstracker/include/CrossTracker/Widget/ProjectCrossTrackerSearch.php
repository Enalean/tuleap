<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
use Tuleap\CrossTracker\CrossTrackerReportDao;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssViteAsset;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Project\MappingRegistry;
use Widget;

class ProjectCrossTrackerSearch extends Widget
{
    public const NAME = 'crosstrackersearch';

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
        $user    = $request->getCurrentUser();

        $permission_checker = new WidgetPermissionChecker(new CrossTrackerReportDao(), \ProjectManager::instance());
        $is_admin           = $permission_checker->isUserWidgetAdmin($user, $this->content_id);

        return $renderer->renderToString(
            'project-cross-tracker-search',
            new ProjectCrossTrackerSearchPresenter(
                $this->content_id,
                $is_admin
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
        return dgettext('tuleap-tracker', 'Trackers');
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

    public function destroy($id)
    {
        $this->getDao()->delete($id);
    }

    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type,
        MappingRegistry $mapping_registry,
    ) {
        $content_id      = $this->getDao()->create();
        $tracker_factory = $this->getTrackerFactory();

        $trackers_existing_widget = $this->getTrackers($id);
        $trackers_new_widget      = [];

        foreach ($trackers_existing_widget as $tracker) {
            if ($this->owner_id == $tracker->getGroupId()) {
                $trackers_new_widget[] = $tracker_factory->getTrackerByShortnameAndProjectId(
                    $tracker->getItemName(),
                    $owner_id
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
        $trackers        = [];
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

    public function getJavascriptAssets(): array
    {
        return [
            new JavascriptViteAsset($this->getAssets(), "src/index.ts"),
        ];
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        return new CssAssetCollection([
            CssViteAsset::fromFileName(
                $this->getAssets(),
                'themes/cross-tracker.scss'
            ),
        ]);
    }

    private function getAssets(): IncludeViteAssets
    {
        return new IncludeViteAssets(
            __DIR__ . '/../../../scripts/cross-tracker/frontend-assets',
            '/assets/crosstracker/cross-tracker'
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
