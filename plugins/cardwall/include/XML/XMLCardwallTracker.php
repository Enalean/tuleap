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

final class XMLCardwallTracker
{
    /**
     * @readonly
     * @var string
     */
    private $id;
    /**
     * @readonly
     * @var XMLCardwallColumn[]
     */
    private $columns = [];
    /**
     * @readonly
     * @var XMLCardwallMapping[]
     */
    private $mappings = [];

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @psalm-mutation-free
     */
    public function withColumn(XMLCardwallColumn $column): self
    {
        $new            = clone $this;
        $new->columns[] = $column;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withMapping(XMLCardwallMapping $mapping): self
    {
        $new             = clone $this;
        $new->mappings[] = $mapping;
        return $new;
    }

    public function export(\SimpleXMLElement $trackers_node): void
    {
        $tracker_node = $trackers_node->addChild(CardwallConfigXml::NODE_TRACKER);
        $tracker_node->addAttribute(CardwallConfigXml::ATTRIBUTE_TRACKER_ID, $this->id);

        if (count($this->columns) > 0) {
            $columns_node = $tracker_node->addChild(CardwallConfigXml::NODE_COLUMNS);
            foreach ($this->columns as $column) {
                $column->export($columns_node);
            }
        }

        if (count($this->mappings) > 0) {
            $mappings_node = $tracker_node->addChild(CardwallConfigXml::NODE_MAPPINGS);
            foreach ($this->mappings as $mapping) {
                $mapping->export($mappings_node);
            }
        }
    }
}
