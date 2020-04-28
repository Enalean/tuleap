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
class JiraFieldAPIRepresentation
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string|null
     */
    private $schema;

    /**
     * @var bool
     */
    private $required;

    public function __construct(
        string $id,
        string $label,
        bool $required,
        ?string $schema
    ) {
        $this->id       = $id;
        $this->label    = $label;
        $this->required = $required;
        $this->schema   = $schema;
    }

    public static function buildFromAPIResponseAndID(string $jira_field_id, array $jira_field): self
    {
        $schema = null;
        if (isset($jira_field['schema']['system'])) {
            $schema = $jira_field['schema']['system'];
        } elseif (isset($jira_field['schema']['custom'])) {
            $schema = $jira_field['schema']['custom'];
        }

        return new self(
            $jira_field_id,
            $jira_field['name'],
            $jira_field['required'],
            $schema
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
