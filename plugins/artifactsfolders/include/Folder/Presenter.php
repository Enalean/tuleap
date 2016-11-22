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
        $this->id_label           = $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'artifactid_label');
        $this->project_label      = $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'project');
        $this->tracker_label      = $GLOBALS['Language']->getText('plugin_tracker_import_admin', 'tracker');
        $this->summary_label      = $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'summary');
        $this->status_label       = $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_label');
        $this->last_update_label  = $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'lastupdatedate_label');
        $this->submitted_by_label = $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'submittedby_label');
        $this->assigned_to_label  = $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'assignedto_label');
        $this->folder_label       = $GLOBALS['Language']->getText('plugin_folders', 'column_label');
        $this->empty_state        = $GLOBALS['Language']->getText('plugin_folders', 'empty_state');
        $this->artifacts          = $artifacts;
    }
}
