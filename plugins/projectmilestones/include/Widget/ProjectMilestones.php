<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProjectMilestones\Widget;

use Codendi_Request;
use CSRFSynchronizerToken;
use HTTPRequest;
use PlanningDao;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use ProjectManager;
use TemplateRenderer;
use TemplateRendererFactory;
use TrackerFactory;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\ProjectMilestones\Milestones\ProjectMilestonesDao;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBrokenConfigurationException;
use Widget;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use PermissionsOverrider_PermissionsOverriderManager;

class ProjectMilestones extends Widget
{
    public const NAME = 'milestone';
    /**
     * @var false|\Planning
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
     * @var int|false
     */
    private $project_id;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var HTTPRequest
     */
    private $http;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var PlanningFactory
     */
    private $planning_factory;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var ProjectAccessChecker
     */
    private $project_access_checker;

    public function __construct()
    {
        $this->planning_factory = new PlanningFactory(
            new PlanningDao(),
            TrackerFactory::instance(),
            new PlanningPermissionsManager()
        );

        $this->project_milestones_dao = new ProjectMilestonesDao();
        $this->project_retriever      = new ProjectRetriever(ProjectManager::instance());
        $this->http                   = HTTPRequest::instance();
        $this->project_id             = $this->http->getProject()->getID();
        $this->csrf_token             = new CSRFSynchronizerToken('/project/');
        $this->project_manager        = ProjectManager::instance();
        $this->project_access_checker = new ProjectAccessChecker(PermissionsOverrider_PermissionsOverriderManager::instance(), new RestrictedUserCanAccessProjectVerifier(), \EventManager::instance());

        parent::__construct(self::NAME);
    }

    public function getTitle(): string
    {
        if ($this->project) {
            try {
                $this->project_access_checker->checkUserCanAccessProject($this->http->getCurrentUser(), $this->project);
                return sprintf(dgettext('tuleap-projectmilestones', '%s Project Milestones'), $this->project->getPublicName());
            } catch (\Project_AccessException $e) {
                return dgettext('tuleap-projectmilestones', 'Project Milestones');
            }
        }
        return dgettext('tuleap-projectmilestones', 'Project Milestones');
    }

    public function getDescription(): string
    {
        return dgettext('tuleap-projectmilestones', 'A widget for milestones monitoring.');
    }

    public function getIcon()
    {
        return "fa-map-signs";
    }

    public function getContent(): string
    {
        try {
            $builder = ProjectMilestonesPresenterBuilder::build();

            $renderer = $this->getRenderer();

            return $renderer->renderToString(
                'projectmilestones',
                $builder->getProjectMilestonePresenter($this->project, $this->root_planning)
            );
        } catch (TimeframeBrokenConfigurationException $e) {
            $message_error = '<p class="tlp-alert-danger">';
            $message_error .= dgettext('tuleap-projectmilestones', 'Invalid Timeframe Semantic configuration.');
            $message_error .= '</p>';

            return $message_error;
        } catch (ProjectMilestonesException $e) {
            $message_error = '<p class="tlp-alert-danger">';
            $message_error .= $e->getTranslatedMessage();
            $message_error .= '</p>';

            return $message_error;
        }
    }

    private function getRenderer(): TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates');
    }

    public function getJavascriptDependencies(): array
    {
        return [
            ['file' => $this->getAssets()->getFileURL('projectmilestones.js')]
        ];
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        return new CssAssetCollection([new CssAsset($this->getAssets(), 'style')]);
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/projectmilestones',
            '/assets/projectmilestones'
        );
    }

    public function getCategory()
    {
        return dgettext('tuleap-projectmilestones', 'Agile dashboard');
    }

    public function loadContent($id)
    {
        $this->project_id = $this->project_milestones_dao->searchProjectIdById((int) $id);

        if (!$this->project_id) {
            return;
        }

        try {
            $this->project = $this->project_retriever->getProjectFromId((string) $this->project_id);
        } catch (NotFoundException $e) {
            return;
        }

        $this->root_planning = $this->planning_factory->getRootPlanning($this->http->getCurrentUser(), $this->project->getID());
    }

    public function isUnique()
    {
        return false;
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getPreferences($widget_id)
    {
        if (!$this->project) {
            $this->project = $this->http->getProject();
        }

        return $this->getRenderer()->renderToString(
            'projectmilestones-preferences',
            new ProjectMilestonesPreferencesPresenter((int) $widget_id, $this->project, $this->csrf_token)
        );
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
        $widget_id = (int) $request->getValidated('content_id', 'uint', 0);

        $project_id = $request->getValidated("select-project-milestones-widget", 'string');

        $project = $this->project_manager->getProjectFromAutocompleter($project_id);

        $this->project_milestones_dao->updateProjectMilestoneId($widget_id, (int) $project->getID());
    }

    public function create(Codendi_Request $request)
    {
        $project_id = $request->getValidated("select-project-milestones-widget", 'string');
        $project = $this->project_manager->getProjectFromAutocompleter($project_id);

        return (int) $this->project_milestones_dao->create((int) $project->getID());
    }

    public function destroy($widget_id)
    {
        $this->project_milestones_dao->delete((int) $widget_id);
    }
}
