<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\Git\DefaultSettings\Pane;

use Project;

class MirroringPresenter
{
    public $project_id;
    public $mirror_presenters;
    public $mirroring_title;
    public $mirroring_info;
    public $mirroring_mirror_url;
    public $mirroring_mirror_used;
    public $mirroring_update_mirroring;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    public function __construct(
        Project $project,
        array $mirror_presenters
    ) {
        $this->project_id        = $project->getID();
        $this->mirror_presenters = $mirror_presenters;

        $this->mirroring_title            = $GLOBALS['Language']->getText('plugin_git', 'mirroring_title');
        $this->mirroring_info             = $GLOBALS['Language']->getText('plugin_git', 'mirroring_default_info');
        $this->mirroring_mirror_url       = $GLOBALS['Language']->getText('plugin_git', 'identifier');
        $this->mirroring_mirror_used      = $GLOBALS['Language']->getText(
            'plugin_git',
            'mirroring_mirror_default_used'
        );
        $this->mirroring_update_mirroring = $GLOBALS['Language']->getText(
            'plugin_git',
            'mirroring_update_default_mirroring'
        );

        $this->csrf_token = new \CSRFSynchronizerToken('?action=admin-default-settings&pane=mirroring&group_id=' . urlencode($this->project_id));
    }
}
