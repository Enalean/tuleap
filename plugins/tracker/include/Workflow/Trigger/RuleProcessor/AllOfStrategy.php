<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsRetriever;

/**
 * Verify that all of siblings artifacts meet rule trigger conditions
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategy implements Tracker_Workflow_Trigger_RulesProcessor_Strategy
{
    /** @var Artifact */
    private $artifact;

    /** @var Tracker_Workflow_Trigger_TriggerRule */
    private $rule;

    /**
     * @var SiblingsRetriever
     */
    private $siblings_retriever;

    public function __construct(Artifact $artifact, Tracker_Workflow_Trigger_TriggerRule $rule, SiblingsRetriever $siblings_retriever)
    {
        $this->artifact           = $artifact;
        $this->rule               = $rule;
        $this->siblings_retriever = $siblings_retriever;
    }

    /**
     * @see Tracker_Workflow_Trigger_RulesProcessor_Strategy::allPrecondtionsAreMet
     * @return bool
     */
    #[\Override]
    public function allPrecondtionsAreMet()
    {
        $siblings = $this->siblings_retriever->getSiblingsWithoutPermissionChecking($this->artifact);
        if (count($siblings)) {
            return $this->allOfSiblingsHaveTriggeringValue($siblings);
        }
        return true;
    }

    private function allOfSiblingsHaveTriggeringValue(array $siblings): bool
    {
        $update_parent = true;
        foreach ($siblings as $sibling) {
            $update_parent = $update_parent && $this->artifactMatchRulesValue($sibling);
        }
        return $update_parent;
    }

    private function artifactMatchRulesValue(Artifact $sibling): bool
    {
        $update_parent = true;
        foreach ($this->rule->getTriggers() as $trigger) {
            $update_parent = $update_parent && $this->artifactMatchTriggerValue($sibling, $trigger);
        }
        return $update_parent;
    }

    private function artifactMatchTriggerValue(Artifact $sibling, Tracker_Workflow_Trigger_FieldValue $trigger): bool
    {
        if ($trigger->getField()->getTracker() == $sibling->getTracker()) {
            return $trigger->isSetForArtifact($sibling);
        }
        return true;
    }
}
