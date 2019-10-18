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
 */

declare(strict_types=1);

namespace Tuleap\Project\Banner;

use HTTPRequest;
use Project;
use ProjectManager;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class BannerAdministrationController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    /**
     * @var TemplateRendererFactory
     */
    private $template_renderer_factory;
    /**
     * @var HeaderNavigationDisplayer
     */
    private $navigation_displayer;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var IncludeAssets
     */
    private $banner_assets;
    /**
     * @var BannerRetriever
     */
    private $banner_retriever;

    public function __construct(
        TemplateRendererFactory $template_renderer_factory,
        HeaderNavigationDisplayer $navigation_displayer,
        IncludeAssets $banner_assets,
        ProjectManager $project_manager,
        BannerRetriever $banner_retriever
    ) {
        $this->template_renderer_factory = $template_renderer_factory;
        $this->navigation_displayer      = $navigation_displayer;
        $this->banner_assets             = $banner_assets;
        $this->project_manager           = $project_manager;
        $this->banner_retriever          = $banner_retriever;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project      = $this->getProject($variables);
        $current_user = $request->getCurrentUser();

        if (! $current_user->isAdmin($project->getID())) {
            throw new ForbiddenException();
        }

        $layout->includeFooterJavascriptFile($this->banner_assets->getFileURL('ckeditor.js'));
        $layout->includeFooterJavascriptFile($this->banner_assets->getFileURL('project-admin-banner.js'));
        $this->navigation_displayer->displayBurningParrotNavigation(
            _('Project banner'),
            $project,
            'banner'
        );

        $banner = $this->banner_retriever->getBannerForProject($project);

        $this->template_renderer_factory
            ->getRenderer(__DIR__ . '/../../../templates/project/admin/banner/')
            ->renderToPage(
                'administration',
                [
                    'message' => $banner === null ? '' : $banner->getMessage(),
                    'project_id' => $project->getID()
                ]
            );
        project_admin_footer([]);
    }

    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProject($variables['id']);
        if (! $project || $project->isError()) {
            throw new NotFoundException();
        }
        return $project;
    }
}
