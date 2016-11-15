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

namespace Tuealp\trove;

class TroveCatListPresenter
{
    /**
     * @var array
     */
    public $trovecats;

    public function __construct(array $trovecats)
    {
        $this->title                    = $GLOBALS['Language']->getText('admin_trove_cat_list', 'title');
        $this->header_name              = $GLOBALS['Language']->getText('admin_trove_cat_list', 'header_name');
        $this->header_description       = $GLOBALS['Language']->getText('admin_trove_cat_list', 'header_description');
        $this->edit_button              = $GLOBALS['Language']->getText('admin_trove_cat_list', 'edit');
        $this->delete_button            = $GLOBALS['Language']->getText('admin_trove_cat_list', 'delete');
        $this->deletion_forbidden_label = $GLOBALS['Language']->getText('admin_trove_cat_list', 'deletion_forbidden_label');

        $this->trovecats = $trovecats;
    }
}
