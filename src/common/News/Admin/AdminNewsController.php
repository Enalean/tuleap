<?php
/**
* Copyright (c) Enalean, 2016. All rights reserved
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
namespace Tuleap\News\Admin;

use ForgeConfig;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;

class AdminNewsController
{
    /**
     * @var AdminNewsDao
     */
    private $admin_news_dao;
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var AdminNewsBuilder
     */
    private $admin_news_builder;

    public function __construct(
        AdminNewsDao $admin_news_dao,
        AdminPageRenderer $admin_page_renderer,
        AdminNewsBuilder $admin_news_builder
    ) {
        $this->admin_news_dao      = $admin_news_dao;
        $this->admin_page_renderer = $admin_page_renderer;
        $this->admin_news_builder  = $admin_news_builder;
    }

    public function displayPublishedNewsPresenter()
    {
        $presenter = $this->admin_news_builder->getPublishedNewsPresenter();

        $title = $GLOBALS['Language']->getText('news_admin_index', 'title');
        $this->admin_page_renderer->renderANoFramedPresenter(
            $title,
            ForgeConfig::get('codendi_dir') . '/src/templates/admin/news/',
            'admin-news',
            $presenter
        );
    }

    public function displayRejectedNewsPresenter()
    {
        $presenter = $this->admin_news_builder->getRejectedNewsPresenter();

        $title = $GLOBALS['Language']->getText('news_admin_index', 'title');
        $this->admin_page_renderer->renderANoFramedPresenter(
            $title,
            ForgeConfig::get('codendi_dir') . '/src/templates/admin/news/',
            'admin-news',
            $presenter
        );
    }

    public function displayWaitingPublicationNewsPresenter()
    {
        $presenter = $this->admin_news_builder->getWaitingPublicationNewsPresenter();

        $title = $GLOBALS['Language']->getText('news_admin_index', 'title');
        $this->admin_page_renderer->renderANoFramedPresenter(
            $title,
            ForgeConfig::get('codendi_dir') . '/src/templates/admin/news/',
            'admin-news',
            $presenter
        );
    }

    public function displayDetailsNewsPresenter($id, $current_tab)
    {
        $result = $this->admin_news_dao->getNewsById($id);

        if (! $result) {
            throw new AdminNewsFindException();
        }

        $presenter = $this->admin_news_builder->getNewsDetailsPresenter($result, $current_tab);
        $this->admin_page_renderer->renderAPresenter(
            $presenter->title,
            ForgeConfig::get('codendi_dir') . '/src/templates/admin/news/',
            'admin-news-details',
            $presenter
        );
    }

    public function update(HTTPRequest $request)
    {
        if (! $request->get('title') && $request->get('status') !== NewsRetriever::NEWS_STATUS_REJECTED) {
            throw new AdminNewsMissingTitleException();
        }

        $id      = $request->get('id');
        $title   = $request->get('title');
        $content = $request->get('content');
        $status  = $request->get('status');
        $date    = time();

        if (! $this->admin_news_dao->updateNews($id, $title, $content, $status, $date)) {
            throw new AdminNewsUpdateException();
        }
    }
}
