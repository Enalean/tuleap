<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\ProjectMilestones\Widget;

use Tuleap\Project\MappingRegistry;
use Widget;
use Tuleap\Layout\CssAssetCollection;
use Codendi_Request;
use Project;
use Planning;
use Tuleap\Request\NotFoundException;
use Tuleap\ProjectMilestones\Milestones\ProjectMilestonesDao;
use Tuleap\Request\ProjectRetriever;
use PlanningFactory;
use TemplateRenderer;
use TemplateRendererFactory;
use CSRFSynchronizerToken;
use HTTPRequest;

class DashboardProjectMilestones extends Widget
{
    public const NAME = 'dashboardprojectmilestone';
    /**
     * @var ProjectMilestonesWidgetRetriever
     */
    private $project_milestones_widget_retriever;
    /**
     * @var Project|null
     */
    private $project;
    /**
     * @var Planning|null
     */
    private $root_planning;
    /**
     * @var ProjectMilestonesDao
     */
    private $project_milestones_dao;
    /**
     * @var ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var PlanningFactory
     */
    private $planning_factory;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var HTTPRequest
     */
    private $http;

    public function __construct(
        ProjectMilestonesWidgetRetriever $project_milestones_widget_retriever,
        ProjectMilestonesDao $project_milestones_dao,
        ProjectRetriever $project_retriever,
        PlanningFactory $planning_factory,
        HTTPRequest $http,
        CSRFSynchronizerToken $csrf_token,
    ) {
        $this->project_milestones_widget_retriever = $project_milestones_widget_retriever;
        $this->project_milestones_dao              = $project_milestones_dao;
        $this->project_retriever                   = $project_retriever;
        $this->planning_factory                    = $planning_factory;
        $this->csrf_token                          = $csrf_token;
        $this->http                                = $http;
        parent::__construct(self::NAME);
    }

    public function getTitle(): string
    {
        return $this->project_milestones_widget_retriever->getTitle($this->project, $this->http->getCurrentUser());
    }

    public function getDescription(): string
    {
        return dgettext('tuleap-projectmilestones', 'A widget for milestones monitoring.');
    }

    public function getIcon(): string
    {
        return "fa-map-signs";
    }

    public function getJavascriptAssets(): array
    {
        return $this->project_milestones_widget_retriever->getJavascriptDependencies();
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        return $this->project_milestones_widget_retriever->getStylesheetDependencies();
    }

    public function getCategory()
    {
        return dgettext('tuleap-projectmilestones', 'Backlog');
    }

    public function isUnique()
    {
        return false;
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function loadContent($id)
    {
        $project_id = $this->project_milestones_dao->searchProjectIdById((int) $id);

        if (! $project_id) {
            return;
        }

        try {
            $this->project = $this->project_retriever->getProjectFromId((string) $project_id);
        } catch (NotFoundException $e) {
            return;
        }

        $root_planning = $this->planning_factory->getRootPlanning($this->http->getCurrentUser(), $this->project->getID());

        if ($root_planning !== false) {
            $this->root_planning = $root_planning;
        }
    }

    public function getContent()
    {
        return $this->project_milestones_widget_retriever->getContent($this->project, $this->root_planning);
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        if (! $this->project) {
            $this->project = $this->http->getProject();
        }

        return $this->project_milestones_widget_retriever->getPreferences($widget_id, $this->project, $this->http->getCurrentUser(), $this->csrf_token);
    }

    public function getInstallPreferences()
    {
        return $this->getRenderer()->renderToString(
            'projectmilestones-preferences',
            new ProjectMilestonesPreferencesPresenter(0, $this->http->getProject(), $this->csrf_token)
        );
    }

    public function updatePreferences(Codendi_Request $request)
    {
        $this->project_milestones_widget_retriever->updatePreferences($request);
    }

    /**
     * @param int|string $id
     * @param int|string $owner_id
     * @param string     $owner_type
     * @return int|string
     */
    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type,
        MappingRegistry $mapping_registry,
    ) {
        $widget_project_id = $this->project_milestones_dao->searchProjectIdById((int) $id);

        if ($widget_project_id && $widget_project_id !== (int) $template_project->getID()) {
            return $this->project_milestones_dao->create($widget_project_id);
        } else {
            return $this->project_milestones_dao->create((int) $new_project->getID());
        }
    }

    public function create(Codendi_Request $request)
    {
        return $this->project_milestones_widget_retriever->create($request);
    }

    /**
     * @param string $id
     */
    public function destroy($id)
    {
        $this->project_milestones_dao->delete((int) $id);
    }

    private function getRenderer(): TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates');
    }

    public function exportAsXML(): \SimpleXMLElement
    {
        $widget = new \SimpleXMLElement('<widget />');
        $widget->addAttribute('name', $this->id);

        return $widget;
    }
}
