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

class AdminNewsPresenter
{
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    public $id;
    public $title;
    public $content;
    public $group_id;
    public $is_requested;
    public $submitted_for_group;
    public $submitted_by;
    public $submitted_by_url;
    public $submitted_on;
    public $current_tab;

    public $title_label;
    public $content_label;
    public $submitted_for_group_label;
    public $submitted_by_label;
    public $submitted_on_label;
    public $pane_information_title;
    public $pane_news_title;
    public $news_already_published;
    public $news_already_rejected;
    public $publish_button;
    public $reject_button;

    public function __construct(
        CSRFSynchronizerToken $csrf_token,
        $id,
        $title,
        $content,
        $group_id,
        $is_requested,
        $submitted_for_group,
        $submitted_by,
        $submitted_by_url,
        $submitted_on,
        $current_tab
    ) {
        $this->csrf_token          = $csrf_token;
        $this->id                  = $id;
        $this->title               = $title;
        $this->content             = $content;
        $this->group_id            = $group_id;
        $this->is_requested        = $is_requested;
        $this->submitted_for_group = $submitted_for_group;
        $this->submitted_by        = $submitted_by;
        $this->submitted_by_url    = $submitted_by_url;
        $this->submitted_on        = $submitted_on;
        $this->current_tab         = $current_tab;

        $this->title_label               = $GLOBALS['Language']->getText('news_admin_index', 'title_label');
        $this->content_label             = $GLOBALS['Language']->getText('news_admin_index', 'content_label');
        $this->submitted_for_group_label = $GLOBALS['Language']->getText('news_admin_index', 'submitted_for_group_label');
        $this->submitted_by_label        = $GLOBALS['Language']->getText('news_admin_index', 'submitted_by_label');
        $this->submitted_on_label        = $GLOBALS['Language']->getText('news_admin_index', 'submitted_on_label');
        $this->pane_news_title           = $GLOBALS['Language']->getText('news_admin_index', 'pane_news_title');
        $this->pane_information_title    = $GLOBALS['Language']->getText('news_admin_index', 'pane_information_title');
        $this->news_already_published    = $GLOBALS['Language']->getText('news_admin_index', 'news_already_published');
        $this->news_already_rejected     = $GLOBALS['Language']->getText('news_admin_index', 'news_already_rejected');
        $this->publish_button            = $GLOBALS['Language']->getText('news_admin_index', 'publish_button');
        $this->reject_button             = $this->getRejectButtonLabel();
    }

    public function publish_button_disabled()
    {
        return $this->current_tab === 'published_news';
    }

    public function reject_button_disabled()
    {
        return $this->current_tab === 'rejected_news';
    }

    private function getRejectButtonLabel()
    {
        if ($this->current_tab === 'published_news') {
            return $GLOBALS['Language']->getText('news_admin_index', 'reject_button_from_published');
        } else {
            return $GLOBALS['Language']->getText('news_admin_index', 'reject_button');
        }
    }
}
