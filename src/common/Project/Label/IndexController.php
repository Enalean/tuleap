<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Project\Label;

use CSRFSynchronizerToken;
use ForgeConfig;
use HTTPRequest;
use Project;
use TemplateRendererFactory;
use Tuleap\Label\CollectionOfLabelableDao;
use Tuleap\Color\ColorPresenterFactory;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;

class IndexController
{
    /**
     * @var LabelDao
     */
    private $dao;
    /**
     * @var LabelsManagementURLBuilder
     */
    private $url_builder;
    /**
     * @var ColorPresenterFactory
     */
    private $color_factory;
    /**
     * @var CollectionOfLabelableDao
     */
    private $labelable_daos;

    public function __construct(
        LabelsManagementURLBuilder $url_builder,
        LabelDao $dao,
        CollectionOfLabelableDao $labelable_daos,
        ColorPresenterFactory $color_factory
    ) {
        $this->url_builder    = $url_builder;
        $this->dao            = $dao;
        $this->labelable_daos = $labelable_daos;
        $this->color_factory  = $color_factory;
    }

    public function display(HTTPRequest $request)
    {
        $project = $request->getProject();

        $title = _('Labels');

        $this->displayHeader($title, $project);

        $templates_dir = ForgeConfig::get('codendi_dir') . '/src/templates/project/labels/';
        $renderer      = TemplateRendererFactory::build()->getRenderer($templates_dir);
        $renderer->renderToPage(
            'list-labels',
            new IndexPresenter(
                $title,
                $project,
                $this->getCollectionOfLabelPresenter($project),
                new NewLabelPresenter($this->color_factory),
                new CSRFSynchronizerToken($this->url_builder->getURL($project))
            )
        );

        $this->displayFooter();
    }

    private function getCollectionOfLabelPresenter(Project $project)
    {
        $collection = new CollectionOfLabelPresenter();
        foreach ($this->dao->searchLabelsUsedByProject($project->getID()) as $row) {
            $is_used           = false;
            $colors_presenters = $this->color_factory->getColorsPresenters($row['color']);

            $collection->add(
                new LabelPresenter(
                    $row['id'],
                    $row['name'],
                    $row['is_outline'],
                    $row['color'],
                    $is_used,
                    $colors_presenters
                )
            );
        }
        foreach ($this->labelable_daos->getAll() as $dao) {
            foreach ($dao->searchLabelsUsedInProject($project->getID()) as $row) {
                $collection->switchToUsed($row['id']);
            }
        }

        return $collection;
    }

    private function displayHeader($title, Project $project)
    {
        $assets_path    = ForgeConfig::get('tuleap_dir') . '/src/www/assets';
        $include_assets = new IncludeAssets($assets_path, '/assets');

        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('project-admin.js'));

        $navigation_displayer = new HeaderNavigationDisplayer();
        $navigation_displayer->displayBurningParrotNavigation($title, $project, 'labels');
    }

    private function displayFooter()
    {
        project_admin_footer(array());
    }
}
