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

final class XMLCardwallMappingValue
{
    /**
     * @readonly
     * @var string
     */
    private $value_id;
    /**
     * @readonly
     * @var string
     */
    private $column_id;

    public function __construct(string $value_id, string $column_id)
    {
        $this->value_id  = $value_id;
        $this->column_id = $column_id;
    }

    public function export(\SimpleXMLElement $values_node): void
    {
        $value_node = $values_node->addChild(CardwallConfigXml::NODE_VALUE);
        $value_node->addAttribute('value_id', $this->value_id);
        $value_node->addAttribute('column_id', $this->column_id);
    }
}
