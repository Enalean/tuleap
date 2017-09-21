<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
use EventManager;
use ForgeConfig;
use HTTPRequest;
use Project;
use TemplateRendererFactory;
use Tuleap\Label\AllowedColorsCollection;
use Tuleap\Label\ColorPresenterFactory;
use Tuleap\Layout\IncludeAssets;

class IndexController
{
    /**
     * @var LabelDao
     */
    private $dao;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var LabelsManagementURLBuilder
     */
    private $url_builder;
    /**
     * @var IncludeAssets
     */
    private $assets;
    /**
     * @var ColorPresenterFactory
     */
    private $color_factory;

    public function __construct(
        LabelsManagementURLBuilder $url_builder,
        LabelDao $dao,
        EventManager $event_manager,
        IncludeAssets $assets,
        ColorPresenterFactory $color_factory
    ) {
        $this->url_builder   = $url_builder;
        $this->dao           = $dao;
        $this->event_manager = $event_manager;
        $this->assets        = $assets;
        $this->color_factory = $color_factory;
    }

    public function display(HTTPRequest $request)
    {
        $project = $request->getProject();

        $title = _('Labels');

        $this->displayHeader($title . ' - ' . $project->getUnconvertedPublicName());

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
        $collection = new CollectionOfLabelPresenter($project);
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
        $this->event_manager->processEvent($collection);

        return $collection;
    }

    private function displayHeader($title)
    {
        $GLOBALS['HTML']->includeFooterJavascriptFile($this->assets->getFileURL('project-admin-labels.js'));
        project_admin_header(array('title' => $title));
    }

    private function displayFooter()
    {
        project_admin_footer(array());
    }
}
