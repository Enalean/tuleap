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
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\ProjectRetriever;

final class BannerAdministrationController implements DispatchableWithRequest, DispatchableWithBurningParrot
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
     * @var ProjectRetriever
     */
    private $project_retriever;
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
        ProjectRetriever $project_retriever,
        BannerRetriever $banner_retriever
    ) {
        $this->template_renderer_factory = $template_renderer_factory;
        $this->navigation_displayer      = $navigation_displayer;
        $this->banner_assets             = $banner_assets;
        $this->project_retriever         = $project_retriever;
        $this->banner_retriever          = $banner_retriever;
    }

    public static function buildSelf(): self
    {
        return new self(
            TemplateRendererFactory::build(),
            new HeaderNavigationDisplayer(),
            new IncludeAssets(__DIR__ . '/../../../www/assets/', '/assets'),
            ProjectRetriever::buildSelf(),
            new BannerRetriever(new BannerDao())
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project      = $this->project_retriever->getProjectFromId($variables['id']);
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
}
