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

use CSRFSynchronizerToken;

class AdminWaitingPublicationPresenter extends AdminNewsListPresenter
{
    public $waiting_publication_active = true;

    public function __construct(CSRFSynchronizerToken $csrf_token, $title, array $news_list)
    {
        parent::__construct($csrf_token, $title, $news_list);

        $this->pane_name         = $GLOBALS['Language']->getText('news_admin_index', 'waiting_publication_pane_name');
        $this->no_news           = $GLOBALS['Language']->getText('news_admin_index', 'waiting_publication_no_news');
        $this->table_button_name = $GLOBALS['Language']->getText('news_admin_index', 'table_review_name');

        $this->is_table_button_review = true;
    }
}
