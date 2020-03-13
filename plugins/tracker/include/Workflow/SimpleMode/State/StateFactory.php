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

namespace Tuleap\Tracker\Workflow\SimpleMode\State;

use Project;
use SimpleXMLElement;
use TransitionFactory;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Workflow;

class StateFactory
{
    /**
     * @var TransitionFactory
     */
    private $transition_factory;

    /**
     * @var SimpleWorkflowDao
     */
    private $simple_workflow_dao;

    public function __construct(TransitionFactory $transition_factory, SimpleWorkflowDao $simple_workflow_dao)
    {
        $this->transition_factory = $transition_factory;
        $this->simple_workflow_dao = $simple_workflow_dao;
    }

    public function getStateFromValueId(Workflow $workflow, int $value_id) : State
    {
        $transitions = $this->transition_factory->getTransitionsForAGivenDestination(
            $workflow,
            $value_id
        );

        return new State($value_id, $transitions);
    }

    /**
     * @return State[]
     */
    public function getAllStatesForWorkflow(Workflow $workflow) : array
    {
        $states = [];
        foreach ($this->simple_workflow_dao->searchStatesForWorkflow((int) $workflow->getId()) as $state_sql) {
            $states[] = $this->getStateFromValueId($workflow, $state_sql['to_id']);
        }

        return $states;
    }

    public function getInstanceFromXML(SimpleXMLElement $state_xml, array &$xml_mapping, Project $project) : State
    {
        $to_value = $xml_mapping[(string) $state_xml->to_id['REF']];

        return new State(
            (int) $to_value->getId(),
            $this->transition_factory->getInstancesFromStateXML($state_xml, $xml_mapping, $project, $to_value)
        );
    }
}
