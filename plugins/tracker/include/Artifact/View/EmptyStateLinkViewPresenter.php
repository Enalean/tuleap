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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\View;

use Tuleap\Tracker\Artifact\Artifact;

final class EmptyStateLinkViewPresenter
{
    public bool $is_tracker_administrator;
    public string $tracker_administration_url;

    public function __construct(\PFUser $user, Artifact $artifact)
    {
        $this->is_tracker_administrator   = $artifact->getTracker()->userIsAdmin($user);
        $this->tracker_administration_url = '/plugins/tracker/?tracker=' . urlencode((string) $artifact->getTracker()->getId()) . '&func=admin';
    }
}
