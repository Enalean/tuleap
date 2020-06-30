<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

class IssueAPIRepresentation
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var int
     */
    private $id;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var array
     */
    private $rendered_fields;

    public function __construct(string $key, int $id, array $fields, array $rendered_fields)
    {
        $this->key             = $key;
        $this->id              = $id;
        $this->fields          = $fields;
        $this->rendered_fields = $rendered_fields;
    }

    /**
     * @throws JiraConnectionException
     */
    public static function buildFromAPIResponse(?array $issue_response): self
    {
        if ($issue_response === null) {
            throw new IssueAPIResponseNotWellFormedException();
        }

        if (
            ! isset($issue_response['key']) ||
            ! isset($issue_response['id']) ||
            ! isset($issue_response['fields']) ||
            ! is_array($issue_response['fields']) ||
            ! isset($issue_response['renderedFields']) ||
            ! is_array($issue_response['renderedFields'])
        ) {
            throw new IssueAPIResponseNotWellFormedException();
        }

        $key             = $issue_response['key'];
        $id              = (int) $issue_response['id'];
        $fields          = $issue_response['fields'];
        $rendered_fields = $issue_response['renderedFields'];

        return new self(
            $key,
            $id,
            $fields,
            $rendered_fields
        );
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return mixed
     */
    public function getFieldByKey(string $key)
    {
        return $this->fields[$key] ?? null;
    }

    /**
     * @return mixed
     */
    public function getRenderedFieldByKey(string $key)
    {
        return $this->rendered_fields[$key] ?? null;
    }
}
