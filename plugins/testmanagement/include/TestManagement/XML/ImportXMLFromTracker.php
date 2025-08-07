<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\XML;

use SimpleXMLElement;
use Tracker_FormElement_Field;
use Tuleap\TestManagement\Step\Definition\Field\StepsDefinition;
use Tuleap\TestManagement\Step\Execution\Field\StepExecution;
use XML_RNGValidator;

class ImportXMLFromTracker
{
    /**
     * @var XML_RNGValidator
     */
    private $rng_validator;

    public function __construct(
        XML_RNGValidator $rng_validator,
    ) {
        $this->rng_validator = $rng_validator;
    }

    public function validateXMLImport(SimpleXMLElement $xml): void
    {
        $this->rng_validator->validate(
            $xml,
            realpath(__DIR__ . '/../../../resources/testmanagement_external_fields.rng')
        );
    }

    public function validateChangesetXMLImport(SimpleXMLElement $xml): void
    {
        $this->rng_validator->validate(
            $xml,
            realpath(__DIR__ . '/../../../resources/testmanagement_external_changeset.rng')
        );
    }

    public function getInstanceFromXML(SimpleXMLElement $testmanagement): ?Tracker_FormElement_Field
    {
        $att = $testmanagement->attributes();
        assert($att !== null);
        $row = [
            'name'              => (string) $testmanagement->name,
            'label'             => (string) $testmanagement->label,
            'type'              => (string) $att['type'],
            'rank'              => (int) $att['rank'],
            'use_it'            => isset($att['use_it']) ? (int) $att['use_it'] : 1,
            'scope'             => isset($att['scope']) ? (string) $att['scope'] : 'P',
            'required'          => isset($att['required']) ? (int) $att['required'] : 0,
            'notifications'     => isset($att['notifications']) ? (int) $att['notifications'] : 0,
            'description'       => (string) $testmanagement->description,
            'id'                => 0,
            'tracker_id'        => 0,
            'parent_id'         => 0,
            'original_field_id' => null,
        ];

        return $this->createStepFormElement($row);
    }

    /**
     * @return StepsDefinition|StepExecution|null
     */
    private function createStepFormElement(array $row): ?Tracker_FormElement_Field
    {
        switch ($row['type']) {
            case StepsDefinition::TYPE:
                return $this->createStepDefinition($row);
            case StepExecution::TYPE:
                return $this->createStepExecution($row);
        }
        return null;
    }

    private function createStepDefinition(array $row): StepsDefinition
    {
        return new StepsDefinition(
            $row['id'],
            $row['tracker_id'],
            $row['parent_id'],
            $row['name'],
            $row['label'],
            $row['description'],
            (bool) $row['use_it'],
            $row['scope'],
            (bool) $row['required'],
            $row['notifications'],
            $row['rank'],
            $row['original_field_id']
        );
    }

    private function createStepExecution(array $row): StepExecution
    {
        return new StepExecution(
            $row['id'],
            $row['tracker_id'],
            $row['parent_id'],
            $row['name'],
            $row['label'],
            $row['description'],
            (bool) $row['use_it'],
            $row['scope'],
            (bool) $row['required'],
            $row['notifications'],
            $row['rank'],
            $row['original_field_id']
        );
    }
}
