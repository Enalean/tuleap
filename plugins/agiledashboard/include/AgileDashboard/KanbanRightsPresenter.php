<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
namespace Tuleap\AgileDashboard;

use Tracker;
use Tuleap\RealTime\MessageRightsPresenter;
use Tracker_Permission_PermissionsSerializer;

class KanbanRightsPresenter implements MessageRightsPresenter
{

    public $submitter_id;
    public $submitter_can_view;
    public $submitter_only;
    public $artifact;
    public $tracker;

    public function __construct(
        Tracker $tracker,
        Tracker_Permission_PermissionsSerializer $permission_serializer
    ) {
        $this->submitter_id       = null;
        $this->submitter_can_view = false;
        $this->submitter_only     = array();
        $this->artifact           = array();
        $this->tracker            = $permission_serializer->getLiteralizedAllUserGroupsThatCanViewTracker($tracker);
    }
}
