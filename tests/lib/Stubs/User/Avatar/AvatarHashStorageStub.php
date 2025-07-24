<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs\User\Avatar;

use Tuleap\Option\Option;

final class AvatarHashStorageStub implements \Tuleap\User\Avatar\AvatarHashStorage
{
    private ?string $new_stored_hash = null;

    private function __construct(private readonly Option $hash)
    {
    }

    public static function withStoredHash(string $hash): self
    {
        return new self(Option::fromValue($hash));
    }

    public static function withoutStoredHash(): self
    {
        return new self(Option::nothing(\Psl\Type\string()));
    }

    #[\Override]
    public function retrieve(\PFUser $user): Option
    {
        return $this->hash;
    }

    #[\Override]
    public function store(\PFUser $user, string $hash): void
    {
        $this->new_stored_hash = $hash;
    }

    public function getNewStoredHash(): ?string
    {
        return $this->new_stored_hash;
    }
}
