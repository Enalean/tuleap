<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
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

class Git_AdminMirrorAddPresenter extends Git_AdminMirrorPresenter
{

    public $btn_submit;

    public $id;

    public $url;

    public $owner_name;

    public $owner_id;

    public $ssh_key;

    public $add_a_mirror = true;

    public function __construct($title, CSRFSynchronizerToken $csrf)
    {
        parent::__construct($title, $csrf);

        $this->btn_submit = $GLOBALS['Language']->getText('global', 'btn_submit');
    }

    public function add_mirror()
    {
        return dgettext('tuleap-git', 'Add mirror');
    }
}
