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
namespace Tuealp\News\Admin;

class AdminNewsListPresenter
{

    public $approval_queue_active = false;
    public $rejected_news_active  = false;
    public $approved_news_active  = false;

    public $approval_queue_tab_name;
    public $rejected_news_tab_name;
    public $approved_news_tab_name;
    public $pane_name;
    public $table_title_name;
    public $table_content_name;
    public $table_details_name;

    /**
     * @var AdminNewsPresenter[]
     */
    public $news_list;
    public $title;

    public function __construct($title, array $news_list)
    {
        $this->news_list = $news_list;
        $this->title     = $title;

        $this->approval_queue_tab_name = $GLOBALS['Language']->getText('news_admin_index', 'approval_queue_tab_name');
        $this->rejected_news_tab_name  = $GLOBALS['Language']->getText('news_admin_index', 'rejected_news_tab_name');
        $this->approved_news_tab_name  = $GLOBALS['Language']->getText('news_admin_index', 'approved_news_tab_name');
        $this->pane_name               = $GLOBALS['Language']->getText('news_admin_index', 'title');
        $this->table_title_name        = $GLOBALS['Language']->getText('news_admin_index', 'table_title_name');
        $this->table_content_name      = $GLOBALS['Language']->getText('news_admin_index', 'table_content_name');
        $this->table_details_name      = $GLOBALS['Language']->getText('news_admin_index', 'table_details_name');
        $this->no_news                 = $GLOBALS['Language']->getText('news_admin_index', 'no_news');
    }
}