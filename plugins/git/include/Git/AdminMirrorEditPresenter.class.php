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

class Git_AdminMirrorEditPresenter extends Git_AdminMirrorPresenter
{

    public $btn_submit;

    public $id;

    public $url;

    private $hostname;

    public $name;

    public $owner_name;

    public $owner_id;

    public $modify_a_mirror = true;

    private $owner;

    public function __construct($title, CSRFSynchronizerToken $csrf, Git_Mirror_Mirror $mirror)
    {
        parent::__construct($title, $csrf);

        $this->id         = $mirror->id;
        $this->url        = $mirror->url;
        $this->hostname   = $mirror->hostname;
        $this->name       = $mirror->name;

        $this->owner      = $mirror->owner;
        $this->owner_name = $mirror->owner_name;
        $this->owner_id   = $mirror->owner_id;
    }

    public function hostname()
    {
        if ($this->hostname) {
            return $this->hostname;
        }

        return "";
    }

    public function update_button()
    {
        return dgettext('tuleap-git', 'Update');
    }

    public function delete_button()
    {
        return dgettext('tuleap-git', 'Delete');
    }

    public function or_action()
    {
        return dgettext('tuleap-git', 'or');
    }

    public function ssh_key()
    {
        return ($this->owner->getAuthorizedKeysRaw()) ? $this->owner->getAuthorizedKeysRaw() : '';
    }

    public function change()
    {
        return dgettext('tuleap-git', 'click here to change');
    }
}
