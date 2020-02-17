<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Test\Helpers;

use Tuleap\Project\Admin\Routing\LayoutHelper;

final class LayoutHelperPassthrough implements LayoutHelper
{
    /** @var \Project */
    private $project;
    /** @var \PFUser */
    private $current_user;

    public function setCallbackParams(\Project $project, \PFUser $user): void
    {
        $this->project      = $project;
        $this->current_user = $user;
    }

    public function renderInProjectAdministrationLayout(
        \HTTPRequest $request,
        string $project_id,
        string $page_title,
        string $current_pane_shortname,
        \Closure $callback
    ): void {
        $callback($this->project, $this->current_user);
    }
}
