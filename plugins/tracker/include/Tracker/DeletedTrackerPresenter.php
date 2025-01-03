<?php
/**
 * Copyright (c) STMicroelectronics 2014. All rights reserved
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
class DeletedTrackerPresenter
{
    public $id;
    public $tracker;
    public $project_id;
    public $project_name;
    public $deletion_date;
    public $csrf_token;

    public function __construct($tracker_id, $tracker_name, $project_id, $project_name, $deletion_date, $restore_token)
    {
        $this->id            = $tracker_id;
        $this->tracker       = $tracker_name;
        $this->project_id    = $project_id;
        $this->project_name  = $project_name;
        $this->deletion_date = $deletion_date;
        $this->csrf_token    = $restore_token;
    }
}
