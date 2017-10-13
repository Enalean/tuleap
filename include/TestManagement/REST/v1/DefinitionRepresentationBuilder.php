<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

use Tuleap\TestManagement\ConfigConformanceValidator;
use Tracker_Artifact;
use PFUser;
use Tracker_FormElementFactory;
use UserManager;

class DefinitionRepresentationBuilder
{

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var AssignedToRepresentationBuilder
     */
    private $assigned_to_representation_builder;

    /**
     * @var Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;

    /**
     * @var ConfigConformanceValidator
     */
    private $conformance_validator;

    public function __construct(
        UserManager $user_manager,
        Tracker_FormElementFactory $tracker_form_element_factory,
        ConfigConformanceValidator $conformance_validator
    ) {
        $this->user_manager                       = $user_manager;
        $this->tracker_form_element_factory       = $tracker_form_element_factory;
        $this->conformance_validator              = $conformance_validator;
    }

    public function getDefinitionRepresentation(PFUser $user, Tracker_Artifact $artifact)
    {
        if (! $this->conformance_validator->isArtifactADefinition($artifact)) {
            return null;
        }

        $definition_representation = new DefinitionRepresentation();
        $definition_representation->build($artifact, $this->tracker_form_element_factory, $user);

        return $definition_representation;
    }

    public function getMinimalRepresentation(PFUser $user, Tracker_Artifact $artifact)
    {
        if (! $this->conformance_validator->isArtifactADefinition($artifact)) {
            return null;
        }

        $definition_representation = new MinimalDefinitionRepresentation();
        $definition_representation->build($artifact, $this->tracker_form_element_factory, $user);

        return $definition_representation;
    }
}
