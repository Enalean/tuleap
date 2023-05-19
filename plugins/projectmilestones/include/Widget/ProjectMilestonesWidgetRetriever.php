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

declare(strict_types=1);

namespace Tuleap\ProjectMilestones\Widget;

use Codendi_Request;
use CSRFSynchronizerToken;
use PFUser;
use Planning;
use Project;
use ProjectManager;
use TemplateRenderer;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\ProjectMilestones\Milestones\ProjectMilestonesDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBrokenConfigurationException;

class ProjectMilestonesWidgetRetriever
{
    public const PARAM_SELECTED_PROJECT      = 'select-project-milestones-widget';
    public const VALUE_SELECTED_PROJECT_SELF = 'self';

    /**
     * @var ProjectAccessChecker
     */
    private $project_access_checker;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var ProjectMilestonesDao
     */
    private $project_milestones_dao;
    /**
     * @var TemplateRenderer
     */
    private $renderer;
    /**
     * @var ProjectMilestonesPresenterBuilder
     */
    private $presenter_builder;

    public function __construct(
        ProjectAccessChecker $project_access_checker,
        ProjectManager $project_manager,
        ProjectMilestonesDao $project_milestones_dao,
        TemplateRenderer $renderer,
        ProjectMilestonesPresenterBuilder $presenter_builder,
    ) {
        $this->project_access_checker = $project_access_checker;
        $this->project_manager        = $project_manager;
        $this->project_milestones_dao = $project_milestones_dao;
        $this->renderer               = $renderer;
        $this->presenter_builder      = $presenter_builder;
    }

    public function getTitle(?Project $project, PFUser $user): string
    {
        if ($project) {
            try {
                $this->project_access_checker->checkUserCanAccessProject($user, $project);
                return sprintf(dgettext('tuleap-projectmilestones', '%s Milestones'), $project->getPublicName());
            } catch (\Project_AccessException $e) {
                return dgettext('tuleap-projectmilestones', 'Project Milestones');
            }
        }
        return dgettext('tuleap-projectmilestones', 'Project Milestones');
    }

    public function getContent(?Project $project, ?Planning $root_planning): string
    {
        try {
            return $this->renderer->renderToString(
                'projectmilestones',
                $this->presenter_builder->getProjectMilestonePresenter($project, $root_planning)
            );
        } catch (TimeframeBrokenConfigurationException $e) {
            $message_error  = '<p class="tlp-alert-danger">';
            $message_error .= dgettext('tuleap-projectmilestones', 'Invalid Timeframe Semantic configuration.');
            $message_error .= '</p>';

            return $message_error;
        } catch (ProjectMilestonesException $e) {
            $message_error  = '<p class="tlp-alert-danger">';
            $message_error .= $e->getTranslatedMessage();
            $message_error .= '</p>';

            return $message_error;
        }
    }

    public function getJavascriptDependencies(): array
    {
        return [
            ['file' => $this->getAssets()->getFileURL('projectmilestones.js')],
        ];
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        return new CssAssetCollection(
            [new CssAssetWithoutVariantDeclinaisons($this->getAssets(), 'projectmilestones-style')]
        );
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../frontend-assets',
            '/assets/projectmilestones'
        );
    }

    public function getPreferences(int $widget_id, Project $project, PFUser $user, CSRFSynchronizerToken $csrf_token): string
    {
        try {
            $this->project_access_checker->checkUserCanAccessProject($user, $project);
            return $this->renderer->renderToString(
                'projectmilestones-preferences',
                new ProjectMilestonesPreferencesPresenter($widget_id, $project, $csrf_token)
            );
        } catch (\Project_AccessException $e) {
            return $this->renderer->renderToString(
                'projectmilestones-preferences',
                new ProjectMilestonesPreferencesPresenter(0, null, $csrf_token)
            );
        }
    }

    public function updatePreferences(Codendi_Request $request): void
    {
        $widget_id = (int) $request->getValidated('content_id', 'uint', 0);

        $project_id = $request->getValidated(self::PARAM_SELECTED_PROJECT, 'string');

        $project = $this->project_manager->getProjectFromAutocompleter($project_id);
        if ($project === false) {
            return;
        }

        $this->project_milestones_dao->updateProjectMilestoneId($widget_id, (int) $project->getID());
    }

    public function getInstallPreferences(Project $project, CSRFSynchronizerToken $csrf_token): string
    {
        return $this->renderer->renderToString(
            'projectmilestones-preferences',
            new ProjectMilestonesPreferencesPresenter(0, $project, $csrf_token)
        );
    }

    public function create(Codendi_Request $request): ?int
    {
        $project_name = $request->getValidated(self::PARAM_SELECTED_PROJECT, 'string');
        if ($project_name === self::VALUE_SELECTED_PROJECT_SELF) {
            $project = $request->get('project');
        } else {
            $project = $this->project_manager->getProjectFromAutocompleter($project_name);
        }

        if ($project === false) {
            return null;
        }

        return (int) $this->project_milestones_dao->create((int) $project->getID());
    }
}
