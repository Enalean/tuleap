<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\Tracker\Artifact\Artifact;

class ExecutionWithAutomatedTestDataProvider
{
    /**
     * @var ExecutionDao
     */
    private $execution_dao;
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(
        ExecutionDao $execution_dao,
        Tracker_FormElementFactory $form_element_factory
    ) {
        $this->execution_dao        = $execution_dao;
        $this->form_element_factory = $form_element_factory;
    }

    public function getExecutionWithAutomatedTestData(
        Artifact $execution,
        Artifact $definition,
        PFUser $user
    ): ?ExecutionWithAutomatedTestData {
        $definition_changeset_id = $this->execution_dao->searchDefinitionChangesetIdForExecution($execution->getId());

        if (! $definition_changeset_id) {
            return null;
        }

        $field = $this->form_element_factory->getUsedFieldByNameForUser(
            $definition->getTrackerId(),
            MinimalDefinitionRepresentation::FIELD_AUTOMATED_TESTS,
            $user
        );

        if (! $field) {
            return null;
        }

        $changeset = $definition->getChangeset($definition_changeset_id);

        if (! $changeset) {
            return null;
        }

        $automated_test = "";
        $value = $definition->getValue($field, $changeset);

        if ($value instanceof Tracker_Artifact_ChangesetValue_Text) {
            $automated_test = $value->getText();
        }

        return new ExecutionWithAutomatedTestData($execution, $automated_test);
    }
}
