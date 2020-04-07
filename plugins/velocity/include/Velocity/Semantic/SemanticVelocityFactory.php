<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
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

namespace Tuleap\Velocity\Semantic;

use SimpleXMLElement;
use Tracker;
use Tuleap\AgileDashboard\Semantic\SemanticDone;

class SemanticVelocityFactory
{
    public function getInstanceByTracker(Tracker $tracker): SemanticVelocity
    {
        return SemanticVelocity::load($tracker);
    }

    public function getInstanceFromXML(
        SimpleXMLElement $xml,
        Tracker $tracker,
        array $mapping
    ) {
        $semantic_done = SemanticDone::load($tracker);

        $ref   = (string) $xml->field['REF'];
        $field = $mapping[$ref];

        return new SemanticVelocity($tracker, $semantic_done, $field);
    }

    public function extractAndFormatMisconfiguredVelocity(array $trackers)
    {
        $collection = new ChildrenRequiredTrackerCollection();

        foreach ($trackers as $tracker) {
            $velocity         = SemanticVelocity::load($tracker);
            $required_tracker = new ChildrenRequiredTracker(
                $tracker,
                ! $velocity->getVelocityField()
            );

            $collection->addChildrenRequiredTracker($required_tracker);
        }

        return $collection;
    }
}
