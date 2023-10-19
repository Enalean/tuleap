<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\News;

use ForgeConfig;
use HTTPRequest;
use ProjectManager;
use Service;
use TemplateRendererFactory;

require_once __DIR__ . '/../../www/forum/forum_utils.php';
require_once __DIR__ . '/../../www/project/admin/ugroup_utils.php';

class ChooseNewsItemController
{
    /**
     * @var HTTPRequest
     */
    private $request;

    /**
     * @var NewsItemForWidgetDataMapper
     */
    private $data_mapper;

    public function __construct()
    {
        $this->request     = HTTPRequest::instance();
        $this->data_mapper = new NewsItemForWidgetDataMapper(new NewsDao());
    }

    public function process()
    {
        $action = $this->request->get('action');

        switch ($action) {
            case 'update':
                $this->updatePromotedItems();
                // Updating then redirecting the user would be better but we can leave with it ¯\_(ツ)_/¯
            default:
                $this->display();
        }
    }

    public function updatePromotedItems()
    {
        $promoted_ids = $this->request->get('promoted');

        return $this->data_mapper->updatePromotedItems($this->getProjectFromRequest(), $promoted_ids);
    }

    public function display()
    {
        $this->checkAccess();

        $this->displayHeader();
        $this->displayBody();
        $this->displayFooter();
    }

    private function checkAccess()
    {
        try {
            $project = $this->getProjectFromRequest();
        } catch (\Exception $e) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('include_html', 'g_not_exist'));
        }

        // admin pages can be reached by news admin (N2) or project admin (A)
        if (! user_ismember($project->getID(), 'A') && ! user_ismember($project->getID(), 'N2')) {
            exit_error($GLOBALS['Language']->getText('news_admin_index', 'permission_denied'), $GLOBALS['Language']->getText('news_admin_index', 'need_to_be_admin'));
        }
    }

    private function displayBody()
    {
        $items     = $this->data_mapper->fetchAll($this->getProjectFromRequest());
        $presenter = new ChooseNewsPresenter($items, $this->request->get('project_id'));
        $renderer  = TemplateRendererFactory::build()->getRenderer(
            ForgeConfig::get('codendi_dir') . '/src/templates/news/'
        );

        $renderer->renderToPage('choose_news', $presenter);
    }

    private function displayHeader(): void
    {
        $project = (ProjectManager::instance())->getProject($this->request->get('project_id'));
        news_header(
            \Tuleap\Layout\HeaderConfigurationBuilder::get($GLOBALS['Language']->getText('news_admin_index', 'title'))
                ->inProject($project, Service::NEWS)
                ->build()
        );
    }

    private function displayFooter()
    {
        news_footer([]);
    }

    private function getProjectFromRequest()
    {
        $project_id      = $this->request->get('project_id');
        $project_manager = ProjectManager::instance();

        return $project_manager->getValidProject($project_id);
    }
}
