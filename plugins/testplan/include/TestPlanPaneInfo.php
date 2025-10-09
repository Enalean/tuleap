<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestPlan;

use Planning_Milestone;
use Tuleap\Tracker\Milestone\PaneInfo;

final class TestPlanPaneInfo extends PaneInfo
{
    public const string NAME = 'testplan';
    public const string URL  = '/testplan';

    /**
     * @var int
     */
    private $milestone_id;

    /**
     * @var \Project
     */
    private $project;

    public function __construct(Planning_Milestone $milestone)
    {
        parent::__construct();

        $artifact           = $milestone->getArtifact();
        $this->project      = $artifact->getTracker()->getProject();
        $this->milestone_id = (int) $artifact->getId();
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getTitle(): string
    {
        return dgettext('tuleap-testplan', 'Tests');
    }

    #[\Override]
    public function getUri(): string
    {
        return self::URL
            . '/'
            . urlencode($this->project->getUnixNameMixedCase())
            . '/'
            . $this->milestone_id;
    }

    #[\Override]
    public function getIconName(): string
    {
        return 'fa-solid fa-check-double';
    }
}
