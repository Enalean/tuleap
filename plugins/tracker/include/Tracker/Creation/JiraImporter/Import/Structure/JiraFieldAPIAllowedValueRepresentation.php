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

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue;
use Tuleap\Tracker\XML\IDGenerator;

/**
 * @psalm-immutable
 */
class JiraFieldAPIAllowedValueRepresentation
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    private $xml_id;

    private function __construct(
        int $id,
        string $name,
        int $xml_id,
    ) {
        $this->id     = $id;
        $this->name   = $name;
        $this->xml_id = $xml_id;
    }

    public static function buildFromAPIResponse(array $jira_field_allowed_value, IDGenerator $id_generator): self
    {
        $allowed_value_id   = (int) $jira_field_allowed_value['id'];
        $allowed_value_name = '';

        if (isset($jira_field_allowed_value['name'])) {
            $allowed_value_name = (string) $jira_field_allowed_value['name'];
        } elseif (isset($jira_field_allowed_value['value'])) {
            $allowed_value_name = (string) $jira_field_allowed_value['value'];
        }

        return new self(
            $allowed_value_id,
            $allowed_value_name,
            $id_generator->getNextId(),
        );
    }

    public static function buildFromAPIResponseStatuses(array $status, IDGenerator $id_generator): self
    {
        return new self(
            (int) $status['id'],
            (string) $status['name'],
            $id_generator->getNextId(),
        );
    }

    public static function buildWithJiraIdOnly(int $jira_id, IDGenerator $id_generator): self
    {
        return new self($jira_id, '', $id_generator->getNextId());
    }

    public static function buildFromTuleapXML(int $jira_id, XMLBindStaticValue $value): self
    {
        return new self(
            $jira_id,
            $value->label,
            (int) substr($value->id, 1),
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getXMLId(): string
    {
        return \Tracker_FormElement_Field_List_Bind_StaticValue::XML_ID_PREFIX . $this->xml_id;
    }

    public function getXMLIdValue(): int
    {
        return $this->xml_id;
    }
}
