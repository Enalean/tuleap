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

/**
 * @psalm-immutable
 */
class FieldMapping
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
     * @var string|null
     */
    private $bind_type;

    public function __construct(string $jira_field_id, string $xml_id, string $field_name, string $type, ?string $bind_type)
    {
        $this->jira_field_id = $jira_field_id;
        $this->xml_id        = $xml_id;
        $this->field_name    = $field_name;
        $this->type          = $type;
        $this->bind_type     = $bind_type;
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
}
