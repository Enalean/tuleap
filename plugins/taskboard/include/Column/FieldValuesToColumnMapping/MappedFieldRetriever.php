<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

class MappedFieldRetriever
{
    /**
     * @var \Cardwall_FieldProviders_SemanticStatusFieldRetriever
     */
    private $semantic_status_provider;

    public function __construct(\Cardwall_FieldProviders_SemanticStatusFieldRetriever $semantic_status_provider)
    {
        $this->semantic_status_provider = $semantic_status_provider;
    }

    public function getField(
        \Cardwall_OnTop_Config $cardwall_config,
        \Tracker $tracker
    ): ?\Tracker_FormElement_Field_List {
        $mapping = $cardwall_config->getMappingFor($tracker);
        if ($mapping) {
            $mapped_field = $mapping->getField();
            if ($mapped_field instanceof \Tracker_FormElement_Field_List) {
                return $mapped_field;
            }
            return null;
        }
        return $this->semantic_status_provider->getField($tracker);
    }
}
