<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use PFUser;
use Planning_Milestone;
use Tuleap\REST\Header;
use Luracast\Restler\RestException;

class MilestonesBurndownResource
{

    public function options()
    {
        Header::allowOptionsGet();
    }

    public function get(PFUser $user, Planning_Milestone $milestone)
    {
        $artifact = $milestone->getArtifact();
        $field    = $artifact->getABurndownField($user);
        if (! $field) {
            throw new RestException(404);
        }
        $rest = $field->getRESTValue($user, $artifact->getLastChangeset());
        Header::allowOptionsGet();
        return $rest->value;
    }

    public function hasBurndown(PFUser $user, Planning_Milestone $milestone)
    {
        $artifact = $milestone->getArtifact();
        $field    = $artifact->getABurndownField($user);
        if (! $field) {
            return false;
        }
        return true;
    }
}
