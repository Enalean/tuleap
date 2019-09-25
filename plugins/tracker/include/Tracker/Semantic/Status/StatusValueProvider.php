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

namespace Tuleap\Tracker\Semantic\Status;

use PFUser;
use Tracker_Artifact;
use Tracker_FormElement_Field_List_BindValue;

class StatusValueProvider
{
    /**
     * @var StatusValueForChangesetProvider
     */
    private $for_changeset_provider;

    public function __construct(StatusValueForChangesetProvider $for_changeset_provider)
    {
        $this->for_changeset_provider = $for_changeset_provider;
    }

    public function getStatusValue(Tracker_Artifact $artifact, PFUser $user): ?Tracker_FormElement_Field_List_BindValue
    {
        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return null;
        }

        return $this->for_changeset_provider->getStatusValueForChangeset($last_changeset, $user);
    }
}
