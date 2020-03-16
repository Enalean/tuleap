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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\CIToken;

use CSRFSynchronizerToken;

class Presenter
{
    public $title;
    public $token;
    public $project_id;
    public $repository_id;
    public $generate_button;
    public $csrf;
    public $description;

    public function __construct($token, $project_id, $repository_id)
    {
        $this->title           = dgettext('tuleap-git', 'Token for Continuous Integration');
        $this->token           = $token;
        $this->project_id      = $project_id;
        $this->repository_id   = $repository_id;
        $this->generate_button = dgettext('tuleap-git', 'Reset token');
        $csrf_synchro          = new CSRFSynchronizerToken('plugins/git/?group_id=' . $project_id . '&pane=citoken');
        $this->csrf            = $csrf_synchro->fetchHTMLInput();
        $this->description     = dgettext('tuleap-git', 'This token is used to authenticate CIs like Jenkins when calling the build status update APIs');
    }
}
