<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Cardwall\XML;

use CardwallConfigXml;

final class XMLCardwall
{
    /**
     * @readonly
     * @var XMLCardwallTracker[]
     */
    private $trackers = [];

    /**
     * @psalm-mutation-free
     */
    public function withTracker(XMLCardwallTracker $tracker): self
    {
        $new             = clone $this;
        $new->trackers[] = $tracker;
        return $new;
    }

    public function export(\SimpleXMLElement $root): \SimpleXMLElement
    {
        $cardwall_node = $root->addChild(CardwallConfigXml::NODE_CARDWALL);
        $trackers_node = $cardwall_node->addChild(CardwallConfigXml::NODE_TRACKERS);
        foreach ($this->trackers as $tracker) {
            $tracker->export($trackers_node);
        }

        return $cardwall_node;
    }
}
