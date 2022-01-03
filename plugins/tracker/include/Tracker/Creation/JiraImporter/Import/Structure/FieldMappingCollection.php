<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLListField;
use Tuleap\Tracker\FormElement\Field\XML\XMLField;

class FieldMappingCollection
{
    /**
     * @var FieldMapping[]
     */
    private array $mapping = [];

    public function addMappingBetweenTuleapAndJiraField(JiraFieldAPIRepresentation $jira_field, XMLField $tuleap_field): XMLField
    {
        if ($tuleap_field instanceof XMLListField) {
            $this->addMapping(ListFieldMapping::buildFromJiraAndTuleapFields($jira_field, $tuleap_field));
        } else {
            $this->addMapping(ScalarFieldMapping::buildFromJiraAndTuleapFields($jira_field, $tuleap_field));
        }
        return $tuleap_field;
    }

    public function addMapping(FieldMapping $field_mapping): void
    {
        if (isset($this->mapping[$field_mapping->getJiraFieldId()])) {
            throw new \LogicException('There is already a field registered in mapping with ID ' . $field_mapping->getJiraFieldId() . '. It\'s likely that you try to add the same field two times in the configuration');
        }
        $this->mapping[$field_mapping->getJiraFieldId()] = $field_mapping;
    }

    public function getMappingFromJiraField(string $key): ?FieldMapping
    {
        if (isset($this->mapping[$key])) {
            return $this->mapping[$key];
        }

        return null;
    }

    public function getAllMappings(): array
    {
        return $this->mapping;
    }
}
