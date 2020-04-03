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

namespace Tuleap\TestManagement\Campaign\Execution;

use PFUser;
use Tracker_Artifact;
use Tuleap\TestManagement\ConfigConformanceValidator;

class DefinitionForExecutionRetriever
{
    /**
     * @var ConfigConformanceValidator
     */
    private $conformance_validator;

    public function __construct(ConfigConformanceValidator $conformance_validator)
    {
        $this->conformance_validator = $conformance_validator;
    }

    /**
     *
     * @return Tracker_Artifact
     * @throws DefinitionNotFoundException
     */
    public function getDefinitionRepresentationForExecution(
        PFUser $user,
        Tracker_Artifact $execution
    ) {
        $art_links = $execution->getLinkedArtifacts($user);
        foreach ($art_links as $art_link) {
            if ($this->conformance_validator->isArtifactAnExecutionOfDefinition($execution, $art_link)) {
                return $art_link;
            }
        }

        throw new DefinitionNotFoundException($execution);
    }
}
