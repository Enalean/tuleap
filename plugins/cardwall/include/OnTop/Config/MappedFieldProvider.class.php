<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__) . '/../../constants.php';

/**
 * Provides the Cardwall_OnTop configured field for an artifact, and fallbacks on the status field if
 * there is no mapping
 */
class Cardwall_OnTop_Config_MappedFieldProvider implements Cardwall_FieldProviders_IProvideFieldGivenAnArtifact
{

    /**
     * @var Cardwall_FieldProviders_SemanticStatusFieldRetriever
     */
    private $semantic_status_provider;

    /**
     * @var Cardwall_OnTop_Config
     */
    private $config;

    public function __construct(
        Cardwall_OnTop_Config $config,
        Cardwall_FieldProviders_SemanticStatusFieldRetriever $semantic_status_provider
    ) {
        $this->semantic_status_provider = $semantic_status_provider;
        $this->config                   = $config;
    }

    public function getField(Tracker $tracker)
    {
        $mapping = $this->config->getMappingFor($tracker);
        if ($mapping) {
            return $mapping->getField();
        }
        return $this->semantic_status_provider->getField($tracker);
    }
}
