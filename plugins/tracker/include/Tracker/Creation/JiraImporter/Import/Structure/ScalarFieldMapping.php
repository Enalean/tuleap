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

use Tuleap\Tracker\FormElement\Field\XML\XMLField;

/**
 * @psalm-immutable
 */
class ScalarFieldMapping implements FieldMapping
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

    private string $jira_field_label;

    public function __construct(
        string $jira_field_id,
        string $jira_field_label,
        private readonly ?string $jira_field_schema,
        string $xml_id,
        string $field_name,
        string $type,
    ) {
        $this->jira_field_id    = $jira_field_id;
        $this->jira_field_label = $jira_field_label;
        $this->xml_id           = $xml_id;
        $this->field_name       = $field_name;
        $this->type             = $type;
    }

    public static function buildFromJiraAndTuleapFields(JiraFieldAPIRepresentation $jira_field, XMLField $tuleap_field): self
    {
        return new self(
            $jira_field->getId(),
            $jira_field->getLabel(),
            $jira_field->getSchema(),
            $tuleap_field->id,
            $tuleap_field->name,
            $tuleap_field->type,
        );
    }

    #[\Override]
    public function getJiraFieldId(): string
    {
        return $this->jira_field_id;
    }

    #[\Override]
    public function getXMLId(): string
    {
        return $this->xml_id;
    }

    #[\Override]
    public function getFieldName(): string
    {
        return $this->field_name;
    }

    #[\Override]
    public function getType(): string
    {
        return $this->type;
    }

    #[\Override]
    public function getBindType(): ?string
    {
        return null;
    }

    #[\Override]
    public function getJiraFieldLabel(): string
    {
        return $this->jira_field_label;
    }

    #[\Override]
    public function getJiraFieldSchema(): ?string
    {
        return $this->jira_field_schema;
    }
}
