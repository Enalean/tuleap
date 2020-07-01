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

namespace Tuleap\ArtifactsFolders\Folder;

class Presenter
{
    public $empty_state;
    public $id_label;
    public $project_label;
    public $tracker_label;
    public $summary_label;
    public $status_label;
    public $last_update_label;
    public $submitted_by_label;
    public $assigned_to_label;
    public $artifacts;

    public function __construct(array $artifacts)
    {
        $this->id_label           = dgettext('tuleap-tracker', 'Artifact ID');
        $this->summary_label      = dgettext('tuleap-tracker', 'Summary');
        $this->status_label       = dgettext('tuleap-tracker', 'Status');
        $this->last_update_label  = dgettext('tuleap-tracker', 'Last Update Date');
        $this->submitted_by_label = dgettext('tuleap-tracker', 'Submitted By');
        $this->assigned_to_label  = dgettext('tuleap-tracker', 'Assigned to');
        $this->folder_label       = dgettext('tuleap-artifactsfolders', 'Folder');
        $this->empty_state        = dgettext('tuleap-artifactsfolders', 'There isn\'t any artifact in this folder.');
        $this->artifacts          = $artifacts;
    }
}
