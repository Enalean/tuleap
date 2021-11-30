<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
        array $mirror_presenters,
    ) {
        $this->project_id        = $project->getID();
        $this->mirror_presenters = $mirror_presenters;

        $this->mirroring_title            = dgettext('tuleap-git', 'Mirroring');
        $this->mirroring_info             = dgettext('tuleap-git', 'Select the mirrors where the new repositories will be replicated by default:');
        $this->mirroring_mirror_url       = dgettext('tuleap-git', 'Identifier');
        $this->mirroring_mirror_used      = dgettext('tuleap-git', 'Used by default?');
        $this->mirroring_update_mirroring = dgettext('tuleap-git', 'Update default mirroring');

        $this->csrf_token = new \CSRFSynchronizerToken('?action=admin-default-settings&pane=mirroring&group_id=' . urlencode($this->project_id));
    }
}
