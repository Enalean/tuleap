<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Testing\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use UserManager;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\Testing\ConfigConformanceValidator;
use Tuleap\Testing\Config;
use Tuleap\Testing\Dao;

class DefinitionsResource {

    /** @var UserManager */
    private $user_manager;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $tracker_form_element_factory;

    /** @var DefinitionRepresentationBuilder */
    private $definition_representation_builder;

    public function __construct() {
        $this->user_manager                      = UserManager::instance();
        $this->tracker_artifact_factory          = Tracker_ArtifactFactory::instance();
        $this->tracker_form_element_factory      = Tracker_FormElementFactory::instance();
        $this->definition_representation_builder = new DefinitionRepresentationBuilder(
            $this->user_manager,
            $this->tracker_form_element_factory,
            new ConfigConformanceValidator(
                new Config(
                    new Dao()
                )
            )
        );

    }

    /**
     * @url OPTIONS {id}
     */
    protected function optionsId($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get a definition
     *
     * Get a definition by id
     *
     * @url GET {id}
     *
     * @param int $id Id of the definition
     *
     * @return {@type Tuleap\Testing\REST\v1\DefinitionRepresentation}
     */
    protected function getId($id) {
        $user       = $this->user_manager->getCurrentUser();
        $definition = $this->tracker_artifact_factory->getArtifactByIdUserCanView($user, $id);

        if (! $definition) {
            throw new RestException(404, 'The test definition does not exist or is not visible');
        }

        return $this->definition_representation_builder->getDefinitionRepresentation($user, $definition);
    }
}
