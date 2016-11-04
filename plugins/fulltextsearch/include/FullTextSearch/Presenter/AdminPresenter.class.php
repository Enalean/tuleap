<?php

/**
 * Copyright (c) Enalean, 2014 - 2016. All Rights Reserved.
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
class FullTextSearch_Presenter_AdminPresenter
{

    public function title()
    {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'admin_title');
    }

    public function subtitle()
    {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'admin_subtitle');
    }

    public function reindex_project_label()
    {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'reindex_project_label');
    }

    public function reindex_help()
    {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'reindex_help');
    }

    public function submit_project_label()
    {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'submit_project_label');
    }

    public function label_placeholder_project()
    {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'label_placeholder_project');
    }
}
