<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

class ChooseNewsPresenter
{

    public $news_items;

    public $project_id;

    public function __construct($news_items, $project_id)
    {
        $this->news_items = $news_items;
        $this->project_id = $project_id;
    }

    public function news_page_header()
    {
        return $GLOBALS['Language']->getText('news_admin_index', 'news_admin');
    }

    public function news_row_label()
    {
        return $GLOBALS['Language']->getText('news_admin_index', 'news_row_label');
    }

    public function promoted_row_label()
    {
        return $GLOBALS['Language']->getText('news_admin_index', 'promoted_row_label');
    }

    public function update_news_list()
    {
        return $GLOBALS['Language']->getText('news_admin_index', 'update_news_list');
    }

    public function info_text()
    {
        return $GLOBALS['Language']->getText('news_admin_index', 'info_text');
    }

    public function news_items_exist()
    {
        return count($this->news_items) > 0;
    }

    public function no_news_items()
    {
        return $GLOBALS['Language']->getText('news_admin_index', 'no_news_items');
    }
}
