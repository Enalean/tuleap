<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

use Tuleap\Authentication\Scope\AuthenticationScope;

/**
 * @psalm-immutable
 */
class AccessKeyMetadata
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var \DateTimeImmutable
     */
    private $creation_date;
    /**
     * @var string
     */
    private $description;
    /**
     * @var \DateTimeImmutable|null
     */
    private $last_used_date;
    /**
     * @var null|string
     */
    private $last_used_ip;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expiration_date;

    /**
     * @var AuthenticationScope[]
     *
     * @psalm-var non-empty-array<AuthenticationScope>
     */
    private $scopes;

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope> $scopes
     */
    public function __construct(
        int $id,
        \DateTimeImmutable $creation_date,
        string $description,
        ?\DateTimeImmutable $last_used_date,
        ?string $last_used_ip,
        ?\DateTimeImmutable $expiration_date,
        array $scopes
    ) {
        $this->id               = $id;
        $this->creation_date    = $creation_date;
        $this->expiration_date  = $expiration_date;
        $this->description      = $description;
        $this->last_used_date   = $last_used_date;
        $this->last_used_ip     = $last_used_ip;
        $this->scopes           = $scopes;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getCreationDate(): \DateTimeImmutable
    {
        return $this->creation_date;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLastUsedDate(): ?\DateTimeImmutable
    {
        return $this->last_used_date;
    }

    public function getLastUsedIP(): ?string
    {
        return $this->last_used_ip;
    }

    public function getExpirationDate(): ?\DateTimeImmutable
    {
        return $this->expiration_date;
    }

    /**
     * @return AuthenticationScope[]
     *
     * @psalm-return non-empty-array<AuthenticationScope>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }
}
