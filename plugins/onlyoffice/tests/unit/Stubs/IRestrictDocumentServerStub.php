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

namespace Tuleap\OnlyOffice\Stubs;

use Tuleap\DB\UUID;
use Tuleap\OnlyOffice\DocumentServer\IRestrictDocumentServer;
use Tuleap\OnlyOffice\DocumentServer\TooManyServersException;

final class IRestrictDocumentServerStub implements IRestrictDocumentServer
{
    /**
     * @var int[] | null
     */
    private ?array $has_been_restricted_with = null;
    private bool $has_been_unrestricted      = false;

    private function __construct(private bool $too_many_servers_for_unrestriction)
    {
    }

    public static function buildSelf(): self
    {
        return new self(false);
    }

    public static function buildWithTooManyServersForUnrestriction(): self
    {
        return new self(true);
    }

    /**
     * @param int[] $project_ids
     */
    #[\Override]
    public function restrict(UUID $id, array $project_ids): void
    {
        $this->has_been_restricted_with = $project_ids;
    }

    #[\Override]
    public function unrestrict(UUID $id): void
    {
        if ($this->too_many_servers_for_unrestriction) {
            throw new TooManyServersException();
        }
        $this->has_been_unrestricted = true;
    }

    public function hasBeenRestricted(): bool
    {
        return $this->has_been_restricted_with !== null;
    }

    /**
     * @param int[] $project_ids
     */
    public function hasBeenRestrictedWith(array $project_ids): bool
    {
        return $this->has_been_restricted_with !== null
            && empty(array_diff($this->has_been_restricted_with, $project_ids))
            && empty(array_diff($project_ids, $this->has_been_restricted_with));
    }

    public function hasBeenUnrestricted(): bool
    {
        return $this->has_been_unrestricted;
    }
}
