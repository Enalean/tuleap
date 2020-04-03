<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use PFUser;
use Tracker_Artifact;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\ConfigConformanceValidator;

class DefinitionRepresentationBuilder
{

    /**
     * @var Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;

    /**
     * @var ConfigConformanceValidator
     */
    private $conformance_validator;

    /**
     * @var RequirementRetriever
     */
    private $requirement_retriever;
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $purifier;

    public function __construct(
        Tracker_FormElementFactory $tracker_form_element_factory,
        ConfigConformanceValidator $conformance_validator,
        RequirementRetriever $requirement_retriever,
        \Codendi_HTMLPurifier $purifier
    ) {
        $this->tracker_form_element_factory = $tracker_form_element_factory;
        $this->conformance_validator        = $conformance_validator;
        $this->requirement_retriever        = $requirement_retriever;
        $this->purifier                     = $purifier;
    }

    public function getDefinitionRepresentation(PFUser $user, Tracker_Artifact $definition_artifact): ?DefinitionRepresentation
    {
        if (! $this->conformance_validator->isArtifactADefinition($definition_artifact)) {
            return null;
        }

        $requirement = $this->requirement_retriever->getRequirementForDefinition($definition_artifact, $user);
        $changeset   = null;

        $definition_representation = new DefinitionRepresentation($this->purifier);
        $definition_representation->build(
            $definition_artifact,
            $this->tracker_form_element_factory,
            $user,
            $changeset,
            $requirement
        );

        return $definition_representation;
    }

    public function getMinimalRepresentation(PFUser $user, Tracker_Artifact $artifact): ?MinimalDefinitionRepresentation
    {
        if (! $this->conformance_validator->isArtifactADefinition($artifact)) {
            return null;
        }

        $changeset = null;

        $definition_representation = new MinimalDefinitionRepresentation();
        $definition_representation->build(
            $artifact,
            $this->tracker_form_element_factory,
            $user,
            $changeset
        );

        return $definition_representation;
    }
}
