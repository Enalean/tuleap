<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Categories;

use ForgeConfig;
use HTTPRequest;
use Project;
use ProjectManager;
use TemplateRendererFactory;
use TroveCatDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class IndexController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    public const TROVE_MAXPERROOT = 3;

    /**
     * @var TroveCatDao
     */
    private $dao;

    public function __construct(TroveCatDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @throws NotFoundException
     */
    public function getProject(\HTTPRequest $request, array $variables): Project
    {
        $project = ProjectManager::instance()->getProject($variables['id']);
        if (!$project || $project->isError()) {
            throw new NotFoundException(dgettext('tuleap-document', "Project does not exist"));
        }

        return $project;
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($request, $variables);
        if (! $request->getCurrentUser()->isAdmin($project->getId())) {
            throw new ForbiddenException(_("You don't have permission to access administration of this project."));
        }

        $assets_path    = ForgeConfig::get('tuleap_dir') . '/src/www/assets';
        $include_assets = new IncludeAssets($assets_path, '/assets');

        $layout->includeFooterJavascriptFile($include_assets->getFileURL('project-admin.js'));

        $navigation_displayer = new HeaderNavigationDisplayer();
        $navigation_displayer->displayBurningParrotNavigation(_('Project categories'), $project, 'categories');

        $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../../templates/project/admin');
        $renderer->renderToPage('categories', $this->getPresenter($project));
        site_project_footer([]);
    }

    private function getPresenter(Project $project): array
    {
        $categories = [];
        foreach ($this->dao->getTopCategories() as $row) {
            $values = [];
            foreach ($this->dao->getCategoriesUnderGivenRootForProject($row['trove_cat_id'], $project->getID()) as $row_value) {
                $values[] = [
                    'fullpath'    => $row_value['fullpath'],
                    'label'       => $row_value['fullname'],
                    'is_selected' => $row_value['is_selected'],
                    'id'          => $row_value['trove_cat_id']
                ];
            }
            $categories[] = [
                'id'                       => $row['trove_cat_id'],
                'label'                    => $row['fullname'],
                'is_mandatory'             => (bool) $row['mandatory'],
                'maximum_selection_length' => self::TROVE_MAXPERROOT,
                'values'                   => $values
            ];
        }

        $url  = '/project/' . (int) $project->getID() . '/admin/categories';
        $csrf = new \CSRFSynchronizerToken($url);

        return [
            'categories' => $categories,
            'csrf'       => $csrf
        ];
    }
}
