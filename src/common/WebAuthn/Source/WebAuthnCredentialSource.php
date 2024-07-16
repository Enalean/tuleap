<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\WebAuthn\Source;

use Webauthn\PublicKeyCredentialSource;

/**
 * @psalm-immutable
 */
final class WebAuthnCredentialSource
{
    public function __construct(
        private readonly PublicKeyCredentialSource $source,
        private readonly string $name,
        private readonly \DateTimeImmutable $created_at,
        private readonly \DateTimeImmutable $last_use,
    ) {
    }

    public function getSource(): PublicKeyCredentialSource
    {
        return $this->source;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getLastUse(): \DateTimeImmutable
    {
        return $this->last_use;
    }

    public function getUserId(): int
    {
        return (int) $this->source->userHandle;
    }
}
