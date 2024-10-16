<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

use PFUser;

final readonly class ProjectCrossTrackerSearchPresenter
{
    public string $is_widget_admin;
    public string $documentation_base_url;

    public function __construct(public int $report_id, bool $is_admin, PFUser $current_user)
    {
        $this->is_widget_admin = $is_admin ? 'true' : 'false';

        $this->documentation_base_url = '/doc/' . urlencode(
            $current_user->getShortLocale()
        );
    }
}
