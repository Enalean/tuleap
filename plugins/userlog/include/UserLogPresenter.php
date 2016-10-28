<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Userlog;

use Tuleap\Layout\PaginationPresenter;

class UserLogPresenter
{
    public $title_user_logging;
    public $subtitle_user_logging;
    public $label_time;
    public $label_project;
    public $label_user;
    public $label_method;
    public $label_uri;
    public $label_remote_adress;
    public $label_referrer;

    /**
     * @var array
     */
    public $logs;

    public function __construct(array $logs, $limit, $offset, $nb_logs)
    {
        $this->logs = $logs;

        $this->title_user_logging    = $GLOBALS['Language']->getText('plugin_userlog', 'title_user_logging');
        $this->subtitle_user_logging = $GLOBALS['Language']->getText('plugin_userlog', 'subtitle_user_logging');
        $this->label_time            = $GLOBALS['Language']->getText('plugin_userlog', 'label_time');
        $this->label_project         = $GLOBALS['Language']->getText('plugin_userlog', 'label_project');
        $this->label_user            = $GLOBALS['Language']->getText('plugin_userlog', 'label_user');
        $this->label_method          = $GLOBALS['Language']->getText('plugin_userlog', 'label_method');
        $this->label_uri             = $GLOBALS['Language']->getText('plugin_userlog', 'label_uri');
        $this->label_remote_adress   = $GLOBALS['Language']->getText('plugin_userlog', 'label_adress');
        $this->label_referrer        = $GLOBALS['Language']->getText('plugin_userlog', 'label_referrer');

        $nb_displayed = $offset + $limit > $nb_logs ? $nb_logs - $offset : $limit;
        $this->pagination = new PaginationPresenter(
            $limit,
            $offset,
            $nb_displayed,
            $nb_logs,
            '/plugins/userlog/',
            array()
        );
    }
}
