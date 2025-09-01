<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter\Administration;

use Override;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Baseline\Domain\RoleAssignmentsToUpdate;

final class RoleAssignmentFromRequestExtractor implements RoleAssignmentsToUpdate
{
    /**
     * @param int[] $baseline_administrators_user_groups_ids
     * @param int[] $baseline_readers_user_groups_ids
     */
    public function __construct(
        private array $baseline_administrators_user_groups_ids,
        private array $baseline_readers_user_groups_ids,
    ) {
    }

    public static function extractRoleAssignmentsFromRequest(ServerRequestInterface $request): self
    {
        return new self(
            self::extractFromRequest($request, 'administrators'),
            self::extractFromRequest($request, 'readers'),
        );
    }

    /**
     * @return int[]
     */
    #[Override]
    public function getBaselineAdministratorsUserGroupsIds(): array
    {
        return $this->baseline_administrators_user_groups_ids;
    }

    /**
     * @return int[]
     */
    #[Override]
    public function getBaselineReadersUserGroupsIds(): array
    {
        return $this->baseline_readers_user_groups_ids;
    }

    /**
     * @return int[]
     */
    private static function extractFromRequest(ServerRequestInterface $request, string $role_key): array
    {
        $body = $request->getParsedBody();
        if (! is_array($body)) {
            throw new \LogicException('Expected body to be an associative array');
        }

        if (! isset($body[$role_key])) {
            return [];
        }

        return array_map(static fn(string $id) => (int) $id, $body[$role_key]);
    }
}
