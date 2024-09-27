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

namespace Tuleap\OnlyOffice\DocumentServer;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\UUID;

/**
 * @psalm-immutable
 */
final readonly class DocumentServer
{
    public bool $has_existing_secret;


    /**
     * @param array<int, RestrictedProject> $project_restrictions
     */
    private function __construct(
        public UUID $id,
        public string $url,
        public ConcealedString $encrypted_secret_key,
        public bool $is_project_restricted,
        public array $project_restrictions,
    ) {
        $this->has_existing_secret = ! $this->encrypted_secret_key->isIdenticalTo(new ConcealedString(''));
    }

    /**
     * @param array<int, RestrictedProject> $project_restrictions
     */
    public static function withProjectRestrictions(
        UUID $id,
        string $url,
        ConcealedString $encrypted_secret_key,
        array $project_restrictions,
    ): self {
        return new self($id, $url, $encrypted_secret_key, true, $project_restrictions);
    }

    public static function withoutProjectRestrictions(
        UUID $id,
        string $url,
        ConcealedString $encrypted_secret_key,
    ): self {
        return new self($id, $url, $encrypted_secret_key, false, []);
    }

    public function isProjectAllowed(\Project $project): bool
    {
        return ! $this->is_project_restricted || isset($this->project_restrictions[(int) $project->getID()]);
    }
}
