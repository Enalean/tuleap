<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use PFUser;
use Tracker_FormElementFactory;

class BurnupFieldRetriever
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $factory;

    public function __construct(Tracker_FormElementFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return Burnup|null
     */
    public function getField(\Tracker_Artifact $artifact, PFUser $user)
    {
        $burnup_fields = $this->factory->getUsedFormElementsByType($artifact->getTracker(), array(Burnup::TYPE));

        if (count($burnup_fields) > 0 && $burnup_fields[0]->userCanRead($user)) {
            return $burnup_fields[0];
        }

        return null;
    }
}
