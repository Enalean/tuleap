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

use Tuleap\Tracker\XML\IDGenerator;

/**
 * @psalm-immutable
 */
class JiraFieldAPIRepresentation
{
    /**
     * @param JiraFieldAPIAllowedValueRepresentation[] $bound_values
     */
    public function __construct(
        private string $id,
        private string $label,
        private bool $required,
        private ?string $schema,
        private array $bound_values,
        private bool $is_submit,
    ) {
    }

    public static function buildFromAPIForSubmit(string $jira_field_id, array $jira_field, IDGenerator $id_generator): self
    {
        return self::buildFromAPI($jira_field_id, $jira_field, $id_generator, true);
    }

    public static function buildFromAPIForUpdate(string $jira_field_id, array $jira_field, IDGenerator $id_generator): self
    {
        return self::buildFromAPI($jira_field_id, $jira_field, $id_generator, false);
    }

    private static function buildFromAPI(string $jira_field_id, array $jira_field, IDGenerator $id_generator, bool $is_submit): self
    {
        $schema = null;
        if (isset($jira_field['schema']['system'])) {
            $schema = $jira_field['schema']['system'];
        } elseif (isset($jira_field['schema']['custom'])) {
            $schema = $jira_field['schema']['custom'];
        }

        $bound_values = [];
        if (isset($jira_field['allowedValues'])) {
            foreach ($jira_field['allowedValues'] as $jira_field_allowed_value) {
                $bound_values[] = JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponse($jira_field_allowed_value, $id_generator);
            }
        }

        return new self(
            $jira_field_id,
            $jira_field['name'],
            $jira_field['required'],
            $schema,
            $bound_values,
            $is_submit,
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

    /**
     * @return JiraFieldAPIAllowedValueRepresentation[]
     */
    public function getBoundValues(): array
    {
        return $this->bound_values;
    }

    public function addNewBoundValues(array $bound_values): self
    {
        return new self(
            $this->id,
            $this->label,
            $this->required,
            $this->schema,
            array_merge(
                $this->bound_values,
                $bound_values
            ),
            $this->is_submit,
        );
    }

    public function isSubmit(): bool
    {
        return $this->is_submit;
    }
}
