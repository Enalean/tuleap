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

class AdminDeletedNewsPresenter extends AdminNewsListPresenter
{
    public $deleted_news_active = true;

    public function __construct($title, array $news_list)
    {
        parent::__construct($title, $news_list);

        $this->pane_name = $GLOBALS['Language']->getText('news_admin_index','deleted_news_pane_name');
        $this->no_news   = $GLOBALS['Language']->getText('news_admin_index','deleted_news_no_news');
    }
}