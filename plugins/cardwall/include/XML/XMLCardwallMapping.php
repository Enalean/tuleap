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

final class XMLCardwallMapping
{
    /**
     * @readonly
     * @var string
     */
    private $tracker_id;
    /**
     * @readonly
     * @var string
     */
    private $field_id;
    /**
     * @readonly
     * @var XMLCardwallMappingValue[]
     */
    private $mapping_values = [];

    public function __construct(string $tracker_id, string $field_id)
    {
        $this->tracker_id = $tracker_id;
        $this->field_id   = $field_id;
    }

    /**
     * @psalm-mutation-free
     */
    public function withMappingValue(XMLCardwallMappingValue $mapping_value): self
    {
        $new                   = clone $this;
        $new->mapping_values[] = $mapping_value;
        return $new;
    }

    public function export(\SimpleXMLElement $mappings_node): \SimpleXMLElement
    {
        $mapping_node = $mappings_node->addChild(CardwallConfigXml::NODE_MAPPING);
        $mapping_node->addAttribute('tracker_id', $this->tracker_id);
        $mapping_node->addAttribute('field_id', $this->field_id);

        $values_node = $mapping_node->addChild(CardwallConfigXml::NODE_VALUES);
        foreach ($this->mapping_values as $value_mapping) {
            $value_mapping->export($values_node);
        }

        return $mapping_node;
    }
}
