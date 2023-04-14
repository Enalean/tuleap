<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic;
/**
 * @psalm-immutable
 */
final class CollectionOfCannotCreateArtifactReason
{
    /**
     * @param CannotCreateArtifactReason[] $reasons
     */
    private function __construct(private readonly array $reasons)
    {
    }

    public static function fromEmptyReason(): self
    {
        return new self([]);
    }

    public function addReason(CannotCreateArtifactReason $reason): self
    {
        return new self([...$this->reasons, $reason]);
    }

    public function addReasons(self $other_reasons): self
    {
        return new self(array_merge($this->reasons, $other_reasons->reasons));
    }

    /**
     * @return string[]
     */
    public function toStringArray(): array
    {
        return array_map(static fn(CannotCreateArtifactReason $cannot_create_reason) => $cannot_create_reason->reason, $this->reasons);
    }

    /**
     * @return CannotCreateArtifactReason[]
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }
}
