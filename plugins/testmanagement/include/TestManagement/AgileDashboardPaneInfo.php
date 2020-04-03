<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use Planning_Milestone;
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfo;

class AgileDashboardPaneInfo extends PaneInfo
{
    /** @var int */
    private $project_id;

    /** @var int */
    private $milestone_id;

    public function __construct(Planning_Milestone $milestone)
    {
        parent::__construct($milestone);

        $artifact = $milestone->getArtifact();
        $this->project_id = $artifact->getTracker()->getProject()->getId();
        $this->milestone_id = $artifact->getId();
    }

    public function isExternalLink()
    {
        return true;
    }

    /** @see PaneInfo::getIdentifier */
    public function getIdentifier()
    {
        return 'testmgmt';
    }

    /** @see PaneInfo::getTitle */
    public function getTitle()
    {
        return dgettext('tuleap-testmanagement', 'Test Campaigns');
    }

    public function getUri()
    {
        $uri = TESTMANAGEMENT_BASE_URL . '/?group_id=' . (int) $this->project_id
             . '&milestone_id=' . (int) $this->milestone_id;
        return $uri;
    }

    public function getIconName()
    {
        return 'fa-check';
    }
}
