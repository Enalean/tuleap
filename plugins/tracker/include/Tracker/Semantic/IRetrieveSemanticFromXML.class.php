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

namespace Tuleap\Tracker\Semantic;

use SimpleXMLElement;
use Tracker;
use Tracker_Semantic;

/**
 * Provides the retrieval and duplication of a Tracker_Semantic
 */
interface IRetrieveSemanticFromXML
{
    /**
     * Creates a Tracker_Semantic_Contributor Object
     *
     * @param SimpleXMLElement  $xml        containing the structure of the imported semantic contributor
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Tracker           $tracker    to which the semantic is attached
     *
     * @return Tracker_Semantic The semantic object
     */
    public function getInstanceFromXML($xml, &$xmlMapping, $tracker);
}
