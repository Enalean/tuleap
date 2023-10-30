<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget;

use Codendi_Request;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Timetracking\Exceptions\TimetrackingOverviewWidgetNoTitle;
use Tuleap\Timetracking\Time\TimetrackingReportDao;
use Widget;

class TimeTrackingOverview extends Widget
{
    public const NAME = 'timetracking-overview';

    /**
     * @var string
     */
    private $widget_title;

    /**
     * @var TemplateRenderer
     */
    private $renderer;

    /**
     * @var TimetrackingReportDao
     */
    private $report_dao;

    public function __construct(TimetrackingReportDao $report_dao, TemplateRenderer $renderer)
    {
        parent::__construct(self::NAME);
        $this->report_dao = $report_dao;
        $this->renderer   = $renderer;
    }

    public function getTitle()
    {
        if ($this->widget_title !== null) {
            return $this->widget_title;
        }
        return dgettext('tuleap-timetracking', 'Timetracking overview');
    }

    public function loadContent($id)
    {
        $this->content_id   = $this->report_dao->searchReportById($id);
        $this->widget_title = $this->report_dao->getReportTitleById($id);
    }

    public function getDescription()
    {
        return dgettext('tuleap-timetracking', 'Displays time spent on multiple trackers');
    }

    public function getCategory()
    {
        return dgettext('tuleap-timetracking', 'Time tracking');
    }

    public function isUnique()
    {
        return false;
    }

    public function getContent()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(TIMETRACKING_TEMPLATE_DIR);
        return $renderer->renderToString(
            'timetracking-overview',
            new TimetrackingOverviewPresenter($this->content_id, $this->getUserPreferences($this->content_id))
        );
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        return $this->renderer->renderToString(
            'timetracking-overview-preferences',
            new TimetrackingOverviewPreferencesPresenter($widget_id, $this->widget_title)
        );
    }

    public function getInstallPreferences()
    {
        return $this->renderer->renderToString(
            'timetracking-overview-preferences',
            new TimetrackingOverviewPreferencesPresenter(0, $this->getTitle())
        );
    }

    /**
     * @throws TimetrackingOverviewWidgetNoTitle
     */
    public function updatePreferences(Codendi_Request $request)
    {
        $content_id = $request->getValidated('content_id', 'uint', 0);

        $title = $request->params["timetracking-overview-title"];
        $this->checkTitleValidity($title);

        return $this->report_dao->setReportTitleById($title, $content_id);
    }

    public function getJavascriptDependencies(): array
    {
        return [
            ['file' => $this->getAssets()->getFileURL('timetracking-overview.js')],
        ];
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        return new CssAssetCollection([new CssAssetWithoutVariantDeclinaisons($this->getAssets(), 'style-bp-overview')]);
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../scripts/timetracking-overview-widget/frontend-assets',
            '/assets/timetracking/timetracking-overview-widget'
        );
    }

    public function getUserPreferences(int $widget_id)
    {
        return $this->getCurrentUser()->getPreference("timetracking_overview_display_trackers_without_time_" . $widget_id);
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    /**
     * @throws TimetrackingOverviewWidgetNoTitle
     */
    public function create(Codendi_Request $request)
    {
        $title = $request->params["timetracking-overview-title"];
        $this->checkTitleValidity($title);

        $content_id = $this->report_dao->create();
        $this->report_dao->setReportTitleById($title, $content_id);

        return $content_id;
    }

    public function destroy($content_id)
    {
        $this->report_dao->delete($content_id);
    }

    /**
     * @throws TimetrackingOverviewWidgetNoTitle
     */
    private function checkTitleValidity(string $title): void
    {
        if (! $title) {
            throw new TimetrackingOverviewWidgetNoTitle();
        }
    }
}
