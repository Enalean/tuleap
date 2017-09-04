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

use EventManager;
use ForgeConfig;
use HTTPRequest;
use Project;
use TemplateRendererFactory;

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

    public function __construct(LabelDao $dao, EventManager $event_manager)
    {
        $this->dao           = $dao;
        $this->event_manager = $event_manager;
    }

    public function display(HTTPRequest $request)
    {

        $title = _('Labels');

        $this->displayHeader($title);

        $templates_dir = ForgeConfig::get('codendi_dir') . '/src/templates/project/labels/';
        $renderer      = TemplateRendererFactory::build()->getRenderer($templates_dir);
        $renderer->renderToPage(
            'list-labels',
            new IndexPresenter($title, $this->getCollectionOfLabelPresenter($request))
        );

        $this->displayFooter();
    }

    private function getCollectionOfLabelPresenter(HTTPRequest $request)
    {
        $project = $request->getProject();

        $collection = new CollectionOfLabelPresenter($project);
        foreach ($this->dao->searchLabelsUsedByProject($project->getID()) as $row) {
            $is_used = false;
            $collection->add(new LabelPresenter($row['id'], $row['name'], $is_used));
        }
        $this->event_manager->processEvent($collection);

        return $collection;
    }

    private function displayHeader($title)
    {
        $GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/project-admin-labels.js');
        project_admin_header(array('title' => $title));
    }

    private function displayFooter()
    {
        project_admin_footer(array());
    }
}
