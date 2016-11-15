<?php
/**
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker;

class ArtifactPendingDeletionPresenter
{
    public function __construct($trackers)
    {
        $this->title                  = $GLOBALS['Language']->getText('tracker_include_type', 'pending_removal');
        $this->table_id               = $GLOBALS['Language']->getText('tracker_include_report', 'id');
        $this->table_project          = $GLOBALS['Language']->getText('global', 'Project');
        $this->table_tracker          = $GLOBALS['Language']->getText('tracker_import_admin', 'tracker');
        $this->table_deletion_date    = $GLOBALS['Language']->getText('tracker_include_type', 'deletion_date');
        $this->restore                = $GLOBALS['Language']->getText('tracker_include_type', 'restore');
        $this->delete                 = $GLOBALS['Language']->getText('tracker_include_type', 'delete');
        $this->tracker_v3_empty_state = $GLOBALS['Language']->getText(
            'tracker_include_type',
            'tracker_v3_empty_state'
        );

        $this->remove_tracker         = $GLOBALS['Language']->getText('tracker_include_type', 'remove_tracker');
        $this->remove_confirm_message = $GLOBALS['Language']->getText('tracker_include_type', 'warning');
        $this->cancel                 = $GLOBALS['Language']->getText('tracker_include_type', 'cancel');

        $this->has_trackers = count($trackers) > 0;
        $this->trackers     = $trackers;
    }
}
