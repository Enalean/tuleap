<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline;

use HTTPRequest;
use Project;
use TemplateRenderer;
use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Adapter\UserProxy;
use Tuleap\Baseline\Domain\Authorizations;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Project\ProjectPrivacyPresenter;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ServiceController implements DispatchableWithRequest, DispatchableWithBurningParrot, DispatchableWithProject
{
    public const PROJECT_NAME_VARIABLE_NAME = 'project_name';

    public function __construct(
        private \ProjectManager $project_manager,
        private TemplateRenderer $template_renderer,
        private \baselinePlugin $plugin,
        private ProjectFlagsBuilder $project_flags_builder,
        private Authorizations $authorizations,
    ) {
    }

    private function includeJavascriptFiles(BaseLayout $layout)
    {
        $layout->includeFooterJavascriptFile($this->getAssets()->getFileURL('baseline.js'));
    }

    private function includeCssFiles(BaseLayout $layout): void
    {
        $layout->addCssAsset(
            new CssAssetWithoutVariantDeclinaisons(
                $this->getAssets(),
                'baseline-style'
            )
        );
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/baseline'
        );
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment(\baselinePlugin::NAME);

        $project_name = $variables[self::PROJECT_NAME_VARIABLE_NAME];
        $project      = $this->getProjectByName($project_name);

        $project_id = $project->getID();
        if (! $this->plugin->isAllowed($project_id)) {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-baseline', 'Baseline service is disabled for this project')
            );
            $layout->redirect('/projects/' . $project_name);
        }

        $user_proxy    = UserProxy::fromUser($request->getCurrentUser());
        $project_proxy = ProjectProxy::buildFromProject($project);

        $can_read_baselines_on_project = $this->authorizations->canReadBaselinesOnProject(
            $user_proxy,
            $project_proxy
        );
        if (! $can_read_baselines_on_project) {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-baseline', 'You are not allowed to access baseline service'),
            );
            $layout->redirect('/projects/' . $project_name);
        }

        $this->includeCssFiles($layout);
        $this->includeJavascriptFiles($layout);

        $layout->header(
            HeaderConfigurationBuilder::get(dgettext('tuleap-baseline', "Baselines"))
                ->inProjectNotInBreadcrumbs($project, \baselinePlugin::SERVICE_SHORTNAME)
                ->build()
        );
        $this->template_renderer->renderToPage(
            'project-service-index',
            [
                'project_id'          => $project_id,
                'project_public_name' => $project->getPublicName(),
                'project_url'         => $project->getUrl(),
                'privacy'             => json_encode(ProjectPrivacyPresenter::fromProject($project), JSON_THROW_ON_ERROR),
                'project_flags'       => json_encode($this->project_flags_builder->buildProjectFlags($project), JSON_THROW_ON_ERROR),
                'project_icon'        => EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($project->getIconUnicodeCodepoint()),
                'is_admin'            => $this->authorizations->canUserAdministrateBaselineOnProject(
                    $user_proxy,
                    $project_proxy
                ),
                'admin_url' => ServiceAdministrationController::getAdminUrl($project),
            ]
        );
        $layout->footer(FooterConfiguration::withoutContent());
    }

    /**
     * Return the project that corresponds to current URI
     *
     * This part of controller is needed when you implement a new route without providing a $group_id.
     * It's the preferred way to deal with those kind of URLs over Event::GET_PROJECTID_FROM_URL
     *
     * @param array        $variables
     *
     * @throws NotFoundException
     */
    public function getProject(array $variables): Project
    {
        return $this->getProjectByName($variables[self::PROJECT_NAME_VARIABLE_NAME]);
    }

    /**
     * @throws NotFoundException
     */
    private function getProjectByName(string $name): Project
    {
        $project = $this->project_manager->getProjectByUnixName($name);
        if (! $project) {
            throw new NotFoundException();
        }
        return $project;
    }
}
