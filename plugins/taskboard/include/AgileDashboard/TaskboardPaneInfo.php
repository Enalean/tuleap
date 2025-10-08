<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Taskboard\AgileDashboard;

use Tuleap\Tracker\Milestone\PaneInfo;

class TaskboardPaneInfo extends PaneInfo
{
    public const string NAME = \taskboardPlugin::NAME;

    public function __construct(private \Planning_Milestone $milestone)
    {
        parent::__construct();
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getTitle(): string
    {
        return dgettext('tuleap-taskboard', 'Taskboard');
    }

    #[\Override]
    public function getIconName(): string
    {
        return 'fa-solid fa-tlp-taskboard';
    }

    #[\Override]
    public function getUri(): string
    {
        $project = $this->milestone->getProject();

        return sprintf(
            '/taskboard/%s/%d',
            urlencode((string) $project->getUnixNameMixedCase()),
            (int) $this->milestone->getArtifactId()
        );
    }
}
