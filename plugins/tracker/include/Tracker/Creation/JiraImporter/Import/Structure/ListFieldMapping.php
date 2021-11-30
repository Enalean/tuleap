<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);


namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

/**
 * @psalm-immutable
 */
class ListFieldMapping implements FieldMapping
{
    /**
     * @var string
     */
    private $jira_field_id;

    /**
     * @var string
     */
    private $xml_id;

    /**
     * @var string
     */
    private $field_name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $bind_type;
    /**
     * @var JiraFieldAPIAllowedValueRepresentation[]
     */
    private $bound_values;

    private string $jira_field_label;

    public function __construct(
        string $jira_field_id,
        string $jira_field_label,
        string $xml_id,
        string $field_name,
        string $type,
        string $bind_type,
        array $bound_values,
    ) {
        $this->jira_field_id    = $jira_field_id;
        $this->jira_field_label = $jira_field_label;
        $this->xml_id           = $xml_id;
        $this->field_name       = $field_name;
        $this->type             = $type;
        $this->bind_type        = $bind_type;
        $this->bound_values     = $bound_values;
    }

    public function getJiraFieldId(): string
    {
        return $this->jira_field_id;
    }

    public function getXMLId(): string
    {
        return $this->xml_id;
    }

    public function getFieldName(): string
    {
        return $this->field_name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getBindType(): ?string
    {
        return $this->bind_type;
    }

    /**
     * @return JiraFieldAPIAllowedValueRepresentation[]
     */
    public function getBoundValues(): array
    {
        return $this->bound_values;
    }

    public function getValueForId(int $id): ?JiraFieldAPIAllowedValueRepresentation
    {
        foreach ($this->bound_values as $value) {
            if ($value->getId() === $id) {
                return $value;
            }
        }
        return null;
    }

    public function getJiraFieldLabel(): string
    {
        return $this->jira_field_label;
    }
}
