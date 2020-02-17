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

namespace Tuleap\Project\Admin\Categories;

use HTTPRequest;
use Project;
use TemplateRenderer;
use TemplateRendererFactory;
use TroveCatDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Routing\AdministrationLayoutHelper;
use Tuleap\Project\Admin\Routing\LayoutHelper;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class IndexController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var TroveCatDao
     */
    private $dao;
    /**
     * @var LayoutHelper
     */
    private $layout_helper;
    /**
     * @var TemplateRenderer
     */
    private $renderer;
    /**
     * @var IncludeAssets
     */
    private $assets;

    public function __construct(
        LayoutHelper $layout_helper,
        TroveCatDao $dao,
        TemplateRenderer $renderer,
        IncludeAssets $assets
    ) {
        $this->layout_helper = $layout_helper;
        $this->dao           = $dao;
        $this->renderer      = $renderer;
        $this->assets        = $assets;
    }

    public static function buildSelf(): self
    {
        return new self(
            AdministrationLayoutHelper::buildSelf(),
            new TroveCatDao(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../../templates/project/admin'),
            new IncludeAssets(__DIR__ . '/../../../../www/assets', '/assets')
        );
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $layout->includeFooterJavascriptFile($this->assets->getFileURL('project-admin.js'));
        $callback = function (\Project $project, \PFUser $current_user): void {
            $this->renderer->renderToPage('categories', $this->getPresenter($project));
        };
        $this->layout_helper->renderInProjectAdministrationLayout(
            $request,
            $variables['id'],
            _('Project categories'),
            'categories',
            $callback
        );
    }

    private function getPresenter(Project $project): array
    {
        $categories = [];
        foreach ($this->dao->getTopCategories() as $row) {
            $nb_selected = 0;
            $values = [];
            foreach ($this->dao->getCategoriesUnderGivenRootForProject($row['trove_cat_id'], $project->getID()) as $row_value) {
                $values[] = [
                    'fullpath'    => $row_value['fullpath'],
                    'label'       => $row_value['fullname'],
                    'is_selected' => $row_value['is_selected'],
                    'id'          => $row_value['trove_cat_id']
                ];
                if ($row_value['is_selected']) {
                    $nb_selected++;
                }
            }
            $categories[] = [
                'id'                       => $row['trove_cat_id'],
                'label'                    => $row['fullname'],
                'is_mandatory'             => (bool) $row['mandatory'],
                'maximum_selection_length' => $row['nb_max_values'],
                'is_multiple'              => $row['nb_max_values'] > 1 || $nb_selected > 1,
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
