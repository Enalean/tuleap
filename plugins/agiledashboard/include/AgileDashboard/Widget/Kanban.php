<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Widget;

use AgileDashboard_Kanban;
use AgileDashboard_KanbanCannotAccessException;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanNotFoundException;
use AgileDashboard_PermissionsManager;
use Codendi_Request;
use KanbanPresenter;
use Project;
use TemplateRendererFactory;
use Tracker_Report;
use Tracker_ReportFactory;
use TrackerFactory;
use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportBuilder;
use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportDao;
use Tuleap\AgileDashboard\KanbanJavascriptDependenciesProvider;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\MappingRegistry;
use Widget;

abstract class Kanban extends Widget
{
    protected $kanban_id;
    protected $kanban_title;

    private $tracker_report_id;
    /**
     * @var WidgetKanbanCreator
     */
    private $widget_kanban_creator;
    /**
     * @var AgileDashboard_KanbanFactory
     */
    private $kanban_factory;
    /**
     * @var WidgetKanbanRetriever
     */
    private $widget_kanban_retriever;
    /**
     * @var AgileDashboard_PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var WidgetKanbanDeletor
     */
    private $widget_kanban_deletor;
    /**
     * @var WidgetKanbanConfigRetriever
     */
    private $widget_kanban_config_retriever;

    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var WidgetKanbanConfigUpdater
     */
    private $widget_kanban_config_updater;

    /**
     * @var Tracker_ReportFactory
     */
    private $tracker_report_factory;

    public function __construct(
        $id,
        $owner_id,
        $owner_type,
        WidgetKanbanCreator $widget_kanban_creator,
        WidgetKanbanRetriever $widget_kanban_retriever,
        WidgetKanbanDeletor $widget_kanban_deletor,
        AgileDashboard_KanbanFactory $kanban_factory,
        TrackerFactory $tracker_factory,
        AgileDashboard_PermissionsManager $permissions_manager,
        WidgetKanbanConfigRetriever $widget_kanban_config_retriever,
        WidgetKanbanConfigUpdater $widget_kanban_config_updater,
        Tracker_ReportFactory $tracker_report_factory,
    ) {
        parent::__construct($id);
        $this->owner_id                       = $owner_id;
        $this->owner_type                     = $owner_type;
        $this->widget_kanban_creator          = $widget_kanban_creator;
        $this->widget_kanban_retriever        = $widget_kanban_retriever;
        $this->widget_kanban_deletor          = $widget_kanban_deletor;
        $this->kanban_factory                 = $kanban_factory;
        $this->tracker_factory                = $tracker_factory;
        $this->permissions_manager            = $permissions_manager;
        $this->widget_kanban_config_retriever = $widget_kanban_config_retriever;
        $this->widget_kanban_config_updater   = $widget_kanban_config_updater;
        $this->tracker_report_factory         = $tracker_report_factory;

        $this->renderer = TemplateRendererFactory::build()->getRenderer(
            AGILEDASHBOARD_TEMPLATE_DIR . '/widgets'
        );
    }

    public function create(Codendi_Request $request)
    {
        return $this->widget_kanban_creator->create($request, $this->owner_id, $this->owner_type);
    }

    public function getTitle()
    {
        $kanban_name     = $this->kanban_title ? : 'Kanban';
        $selected_report = $this->getSelectedReport();

        if (
            $this->tracker_report_id
            && $selected_report
            && $this->isCurrentReportSelectable($selected_report)
        ) {
            return sprintf(
                '%s - %s',
                $kanban_name,
                $selected_report->getName()
            );
        }

        return $kanban_name;
    }

    public function getDescription()
    {
        return dgettext('tuleap-agiledashboard', 'Displays a board to see the tasks to do, in progress, done etc. Please go on a kanban to add it.');
    }

    public function getIcon()
    {
        return 'fa-columns';
    }

    public function loadContent($id)
    {
        $widget = $this->widget_kanban_retriever->searchWidgetById($id, $this->owner_id, $this->owner_type);
        if (! $widget) {
            return;
        }

        try {
            $this->content_id        = $id;
            $this->kanban_id         = $widget[0]['kanban_id'];
            $this->tracker_report_id = $this->widget_kanban_config_retriever->getWidgetReportId($id);
            $kanban                  = $this->kanban_factory->getKanban($this->getCurrentUser(), $this->kanban_id);
            $this->kanban_title      = $kanban->getName();
        } catch (AgileDashboard_KanbanCannotAccessException $e) {
        }
    }

    public function getContent()
    {
        $is_empty = true;
        $renderer = TemplateRendererFactory::build()->getRenderer(
            AGILEDASHBOARD_TEMPLATE_DIR . '/widgets'
        );
        try {
            $kanban  = $this->kanban_factory->getKanban($this->getCurrentUser(), $this->kanban_id);
            $tracker = $this->tracker_factory->getTrackerByid($kanban->getTrackerId());
            if ($tracker === null) {
                throw new \RuntimeException('Tracker does not exist');
            }
            $project_id = (int) $tracker->getProject()->getID();
            $is_empty   = ! $kanban;

            $user_is_kanban_admin = $this->permissions_manager->userCanAdministrate(
                $this->getCurrentUser(),
                $project_id
            );

            $kanban_presenter        = new KanbanPresenter(
                $kanban,
                $this->getCurrentUser(),
                $user_is_kanban_admin,
                $this->getCurrentUser()->getShortLocale(),
                $project_id,
                $this->dashboard_widget_id,
                $this->tracker_report_id,
            );
            $widget_kanban_presenter = new WidgetKanbanPresenter(
                $is_empty,
                '',
                $kanban_presenter
            );
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
            $widget_kanban_presenter = new WidgetKanbanPresenter(
                $is_empty,
                dgettext('tuleap-agiledashboard', 'Kanban not found.')
            );
        } catch (AgileDashboard_KanbanCannotAccessException $exception) {
            $widget_kanban_presenter = new WidgetKanbanPresenter(
                $is_empty,
                $GLOBALS['Language']->getText('global', 'error_perm_denied')
            );
        }

        return $renderer->renderToString('widget-kanban', $widget_kanban_presenter);
    }

    public function getCategory()
    {
        return dgettext('tuleap-agiledashboard', 'Agile dashboard');
    }

    public function destroy($id)
    {
        $this->widget_kanban_deletor->delete($id, $this->owner_id, $this->owner_type);
    }

    public function canBeAddedFromWidgetList()
    {
        return false;
    }

    public function isUnique()
    {
        return true;
    }

    public function getImageSource()
    {
        return '/themes/common/images/widgets/add-kanban-widget-from-kanban.png';
    }

    public function getImageTitle()
    {
        return dgettext('tuleap-agiledashboard', 'Add Kanban to dashboard');
    }

    private function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(__DIR__ . '/../../../scripts/kanban/frontend-assets', '/assets/agiledashboard/kanban');
    }

    public function getJavascriptDependencies(): array
    {
        $provider = new KanbanJavascriptDependenciesProvider($this->getIncludeAssets());
        return $provider->getDependencies();
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        return new CssAssetCollection([new CssAssetWithoutVariantDeclinaisons($this->getIncludeAssets(), 'kanban-style')]);
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        $tracker_reports_builder = new TrackerReportBuilder(
            $this->tracker_report_factory,
            $this->kanban_factory->getKanban($this->getCurrentUser(), $this->kanban_id),
            new TrackerReportDao()
        );

        $widget_tracker_reports = $tracker_reports_builder->build(
            $this->tracker_report_id
        );

        return $this->renderer->renderToString(
            'widget-kanban-report-selector',
            new WidgetKanbanReportSelectorPresenter(
                $widget_tracker_reports
            )
        );
    }

    public function updatePreferences(Codendi_Request $request)
    {
        $this->widget_kanban_config_updater->updateConfiguration(
            $this->content_id,
            $request->get('kanban-report-filter')
        );
    }

    private function getSelectedReport()
    {
        return $this->tracker_report_factory->getReportById(
            $this->tracker_report_id,
            $this->getCurrentUser()->getId()
        );
    }

    private function isCurrentReportSelectable(Tracker_Report $report)
    {
        $tracker_report_dao = new TrackerReportDao();
        $selectable_reports = $tracker_report_dao->searchReportIdsForKanban($this->kanban_id);

        return in_array($report->getId(), $selectable_reports);
    }

    /**
     * cloneContent
     *
     * Take the content of a widget, clone it and return the id of the new content
     */
    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type,
        MappingRegistry $mapping_registry,
    ) {
        $this->loadContent($id);

        $old_kanban = $this->kanban_factory->getKanban(
            $this->getCurrentUser(),
            $this->kanban_id
        );

        $new_tracker = $this->tracker_factory->getTrackerByShortnameAndProjectId(
            $this->getKanbanTrackerShortname($old_kanban),
            $new_project->getID()
        );

        $new_kanban_id = $this->kanban_factory->getKanbanIdByTrackerId($new_tracker->getId());

        $new_report_id = null;
        if ($this->tracker_report_id) {
            $old_kanban_report = $this->tracker_report_factory->getReportById($this->tracker_report_id, $this->getCurrentUser());

            $new_tracker_reports = $this->tracker_report_factory
                ->getReportsByTrackerId(
                    $new_tracker->getId(),
                    $this->getCurrentUser()->getId()
                );
            foreach ($new_tracker_reports as $new_tracker_report) {
                if ($new_tracker_report->getName() === $old_kanban_report->getName()) {
                    $new_report_id = $new_tracker_report->getId();
                    break;
                }
            }
        }

        return $this->widget_kanban_creator->createKanbanWidget(
            $owner_id,
            $owner_type,
            $new_kanban_id,
            $this->kanban_title,
            $new_report_id
        );
    }

    private function getKanbanTrackerShortname(AgileDashboard_Kanban $kanban): string
    {
        $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
        if ($tracker === null) {
            throw new \RuntimeException('Tracker does not exist');
        }
        return $tracker->getItemName();
    }
}
