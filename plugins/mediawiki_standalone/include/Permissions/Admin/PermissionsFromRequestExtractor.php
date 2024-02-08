<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Psr\Http\Message\ServerRequestInterface;
use Tuleap\MediawikiStandalone\Permissions\ProjectPermissions;

final class PermissionsFromRequestExtractor
{
    public function __construct(private ProjectPermissions $permissions)
    {
    }

    /**
     * @throws InvalidRequestException
     */
    public static function extractPermissionsFromRequest(ServerRequestInterface $request): self
    {
        return new self(
            new ProjectPermissions(
                self::extractFromRequest($request, 'readers'),
                self::extractFromRequest($request, 'writers'),
                self::extractFromRequest($request, 'admins'),
            )
        );
    }

    /**
     * @return int[]
     */
    private static function extractFromRequest(ServerRequestInterface $request, string $key): array
    {
        $body = $request->getParsedBody();
        if (! is_array($body)) {
            throw new InvalidRequestException('Expected body to be an associative array');
        }

        if (! isset($body[$key])) {
            return [];
        }
        if (! is_array($body[$key])) {
            throw new InvalidRequestException("Expected $key to be an array");
        }

        return array_map(static fn(string $id) => (int) $id, $body[$key]);
    }

    public function getPermissions(): ProjectPermissions
    {
        return $this->permissions;
    }
}
