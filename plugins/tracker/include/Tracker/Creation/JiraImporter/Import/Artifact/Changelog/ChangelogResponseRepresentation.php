<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog;

use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

class ChangelogResponseRepresentation
{
    /**
     * @var array
     */
    private $values;

    /**
     * @var int
     */
    private $max_results;

    /**
     * @var int
     */
    private $total;

    public function __construct(
        array $values,
        int $max_results,
        int $total
    ) {
        $this->values = $values;
        $this->max_results = $max_results;
        $this->total = $total;
    }

    /**
     * @throws JiraConnectionException
     */
    public static function buildFromAPIResponse(?array $changelog_response): self
    {
        if ($changelog_response === null) {
            throw JiraConnectionException::canNotRetrieveFullCollectionOfIssueChangelogsException();
        }

        if (
            ! isset($changelog_response['values']) ||
            ! is_array($changelog_response['values']) ||
            ! isset($changelog_response['maxResults']) ||
            ! isset($changelog_response['total'])
        ) {
            throw JiraConnectionException::canNotRetrieveFullCollectionOfIssueChangelogsException();
        }

        $values      = $changelog_response['values'];
        $max_results = (int) $changelog_response['maxResults'];
        $total       = (int) $changelog_response['total'];

        return new self(
            $values,
            $max_results,
            $total
        );
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getMaxResults(): int
    {
        return $this->max_results;
    }
}
