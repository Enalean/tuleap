<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\TrackerDeletion;

final class DeletedTrackersListPresenter
{
    public string $title;
    public array $deleted_trackers_list;
    public array $tracker_ids_warning;
    public string $id_column_header;
    public string $tracker_column_header;
    public string $project_column_header;
    public string $deletion_date_column_header;
    public string $restore_action;
    public string $no_trackers_label;
    public bool $has_trackers;
    public bool $has_warnings;
    public string $warning_message;

    /**
     * @param DeletedTrackerPresenter[] $table_content
     * @param string[] $tracker_ids_warning
     */
    public function __construct(array $table_content, array $tracker_ids_warning, bool $has_trackers)
    {
        $this->title                       = dgettext('tuleap-tracker', 'Trackers Pending for Deletion');
        $this->deleted_trackers_list       = $table_content;
        $this->tracker_ids_warning         = $tracker_ids_warning;
        $this->id_column_header            = $GLOBALS['Language']->getText('tracker_include_report', 'id');
        $this->tracker_column_header       = $GLOBALS['Language']->getText('tracker_import_admin', 'tracker');
        $this->project_column_header       = $GLOBALS['Language']->getText('global', 'Project');
        $this->deletion_date_column_header = $GLOBALS['Language']->getText('tracker_include_type', 'deletion_date');
        $this->restore_action              = dgettext('tuleap-tracker', 'Restore');
        $this->no_trackers_label           = dgettext('tuleap-tracker', 'No Tracker pending for Deletion');
        $this->has_trackers                = $has_trackers;
        $this->has_warnings                = count($this->tracker_ids_warning) > 0;
        $this->warning_message             = dgettext('tuleap-tracker', 'The following trackers cannot be displayed (data seems missing in database):') . implode(',', $this->tracker_ids_warning);
    }

    public function getTemplateDir(): string
    {
        return TRACKER_TEMPLATE_DIR;
    }
}
